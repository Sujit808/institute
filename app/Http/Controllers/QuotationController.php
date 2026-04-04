<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Quotation;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class QuotationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeQuotationAccess($request->user());

        $filters = $request->validate([
            'document_type' => ['nullable', 'string', 'max:30'],
            'client' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'include_archived' => ['nullable', 'boolean'],
        ]);

        $includeArchived = $request->boolean('include_archived');

        $query = Quotation::query()
            ->with(['organization', 'creator']);

        if ($includeArchived) {
            $query->withTrashed();
        }

        $clientSearch = trim((string) ($filters['client'] ?? ''));

        $quotations = $query
            ->when(! empty($filters['document_type']), fn ($builder) => $builder->where('document_type', $filters['document_type']))
            ->when($clientSearch !== '', function ($builder) use ($clientSearch): void {
                $builder->where(function ($nested) use ($clientSearch): void {
                    $nested->where('client->name', 'like', '%'.$clientSearch.'%')
                        ->orWhere('client->institute_name', 'like', '%'.$clientSearch.'%');
                });
            })
            ->when(! empty($filters['date_from']), fn ($builder) => $builder->whereDate('quotation_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($builder) => $builder->whereDate('quotation_date', '<=', $filters['date_to']))
            ->latest('generated_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', [
            'quotations' => $quotations,
            'filters' => [
                'document_type' => $filters['document_type'] ?? '',
                'client' => $clientSearch,
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'include_archived' => $includeArchived,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeQuotationAccess($request->user());

        $organization = Organization::current();

        return view('quotations.create', $this->buildFormViewData($organization));
    }

    public function edit(Request $request, int $quotation): View
    {
        $this->authorizeQuotationAccess($request->user());

        $record = $this->findQuotation($quotation);

        return view('quotations.create', $this->buildFormViewData($record->organization, $record, false));
    }

    public function reuse(Request $request, int $quotation): View
    {
        $this->authorizeQuotationAccess($request->user());

        $record = $this->findQuotation($quotation);

        return view('quotations.create', $this->buildFormViewData($record->organization, $record, true));
    }

    public function show(Request $request, int $quotation): View
    {
        $this->authorizeQuotationAccess($request->user());

        $record = $this->findQuotation($quotation);

        return view('quotations.branded', $this->payloadFromQuotation($record, false));
    }

    public function generateNumber(Request $request): JsonResponse
    {
        $this->authorizeQuotationAccess($request->user());

        $validated = $request->validate([
            'document_type' => ['nullable', 'string', 'max:30'],
        ]);

        $organization = Organization::current();
        $documentType = (string) ($validated['document_type'] ?? 'Quotation');

        return response()->json([
            'quotation_no' => $this->generateQuotationNumber($organization, $documentType),
        ]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $this->authorizeQuotationAccess($request->user());

        $payload = $this->buildDocumentPayload($request, false);
        $payload = $this->persistQuotationHistory($payload, $request->user(), 'preview');

        return view('quotations.branded', $payload);
    }

    public function download(Request $request): Response|RedirectResponse
    {
        $this->authorizeQuotationAccess($request->user());

        $payload = $this->buildDocumentPayload($request, true);
        $payload = $this->persistQuotationHistory($payload, $request->user(), 'download');

        $filename = strtolower((string) preg_replace('/[^a-z0-9\-]+/i', '-', ($payload['client']['institute_name'] ?? 'client').'-'.($payload['quotationNo'] ?? 'quotation')));
        $filename = trim($filename, '-') ?: 'quotation';

        return Pdf::loadView('quotations.branded', $payload)
            ->setPaper('a4')
            ->download($filename.'.pdf');
    }

    public function downloadSaved(Request $request, int $quotation): Response
    {
        $this->authorizeQuotationAccess($request->user());

        $record = $this->findQuotation($quotation);

        $payload = $this->payloadFromQuotation($record, true);
        $filename = strtolower((string) preg_replace('/[^a-z0-9\-]+/i', '-', ($payload['client']['institute_name'] ?? 'client').'-'.($payload['quotationNo'] ?? 'quotation')));
        $filename = trim($filename, '-') ?: 'quotation';

        return Pdf::loadView('quotations.branded', $payload)
            ->setPaper('a4')
            ->download($filename.'.pdf');
    }

    public function archive(Request $request, int $quotation): RedirectResponse
    {
        $this->authorizeQuotationAccess($request->user());

        $record = Quotation::query()->findOrFail($quotation);
        $record->deleted_by = $request->user()?->id;
        $record->save();
        $record->delete();

        return back()->with('status', 'Quotation archived successfully.');
    }

    public function shareEmail(Request $request): RedirectResponse
    {
        $this->authorizeQuotationAccess($request->user());

        $validated = $request->validate([
            'share_email_to' => ['required', 'email', 'max:150'],
            'share_email_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $payload = $this->buildDocumentPayload($request, true);
        $payload = $this->persistQuotationHistory($payload, $request->user(), 'email-shared');
        $subject = $payload['subject'] ?: (($payload['documentType'] ?? 'Quotation').' '.$payload['quotationNo']);
        $pdfOutput = Pdf::loadView('quotations.branded', $payload)
            ->setPaper('a4')
            ->output();
        $attachmentName = Str::slug((string) (($payload['client']['institute_name'] ?? 'client').'-'.($payload['quotationNo'] ?? 'quotation'))).'.pdf';

        try {
            Mail::send('emails.quotation-share', [
                'payload' => $payload,
                'customMessage' => $validated['share_email_message'] ?? null,
            ], function ($message) use ($validated, $subject, $pdfOutput, $attachmentName): void {
                $message->to($validated['share_email_to'])
                    ->subject($subject)
                    ->attachData($pdfOutput, $attachmentName, ['mime' => 'application/pdf']);
            });
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['share_email_to' => 'Unable to send quotation email right now. Please verify mail configuration and try again.']);
        }

        return back()->withInput()->with('status', 'Quotation emailed successfully.');
    }

    public function shareWhatsapp(Request $request): RedirectResponse
    {
        $this->authorizeQuotationAccess($request->user());

        $validated = $request->validate([
            'share_whatsapp_number' => ['nullable', 'string', 'max:30'],
            'share_whatsapp_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = $this->buildDocumentPayload($request, true);
        $payload = $this->persistQuotationHistory($payload, $request->user(), 'whatsapp-shared');
        $pdfUrl = $this->storeShareablePdf($payload);
        $phoneNumber = preg_replace('/\D+/', '', (string) ($validated['share_whatsapp_number'] ?? ''));
        $message = trim(implode("\n", array_filter([
            (string) ($validated['share_whatsapp_message'] ?? ''),
            ($payload['documentType'] ?? 'Quotation').' No: '.($payload['quotationNo'] ?? '-'),
            'Client: '.($payload['client']['institute_name'] ?? ($payload['client']['name'] ?? 'Client')),
            'Amount: '.($payload['currency'] ?? 'PKR').' '.number_format((float) ($payload['grandTotal'] ?? 0), 2),
            $pdfUrl ? 'PDF: '.$pdfUrl : null,
        ])));

        $baseUrl = $phoneNumber !== '' ? 'https://wa.me/'.$phoneNumber : 'https://wa.me/';
        $shareUrl = $baseUrl.'?text='.rawurlencode($message);

        return redirect()->away($shareUrl);
    }

    private function authorizeQuotationAccess(?User $user): void
    {
        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDocumentPayload(Request $request, bool $forPdf): array
    {
        $organization = Organization::current();

        $validated = Validator::make(
            $request->all(),
            [
                'document_type' => ['required', 'string', 'max:30'],
                'quotation_id' => ['nullable', 'integer'],
                'quotation_no' => ['required', 'string', 'max:50'],
                'quotation_date' => ['required', 'date'],
                'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
                'currency' => ['required', 'string', 'in:PKR,INR'],
                'prepared_by' => ['nullable', 'string', 'max:120'],
                'subject' => ['nullable', 'string', 'max:255'],
                'intro_text' => ['nullable', 'string', 'max:1000'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'footer_text' => ['nullable', 'string', 'max:500'],
                'client.name' => ['required', 'string', 'max:150'],
                'client.institute_name' => ['required', 'string', 'max:150'],
                'client.contact_person' => ['nullable', 'string', 'max:150'],
                'client.phone' => ['nullable', 'string', 'max:40'],
                'client.email' => ['nullable', 'email', 'max:150'],
                'client.address' => ['nullable', 'string', 'max:500'],
                'terms_text' => ['nullable', 'string', 'max:2000'],
                'bank_details.account_name' => ['nullable', 'string', 'max:150'],
                'bank_details.bank_name' => ['nullable', 'string', 'max:150'],
                'bank_details.account_no' => ['nullable', 'string', 'max:80'],
                'bank_details.iban' => ['nullable', 'string', 'max:80'],
                'discount_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'tax' => ['nullable', 'numeric', 'min:0'],
                'items' => ['required', 'array'],
                'items.*.description' => ['nullable', 'string', 'max:255'],
                'items.*.details' => ['nullable', 'string', 'max:1000'],
                'items.*.qty' => ['nullable', 'numeric', 'min:0'],
                'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            ],
            [
                'client.name.required' => 'Client name is required.',
                'client.institute_name.required' => 'Institute name is required.',
            ],
            [
                'document_type' => 'document type',
                'quotation_no' => 'quotation number',
                'quotation_date' => 'quotation date',
                'valid_until' => 'valid until date',
                'prepared_by' => 'prepared by',
                'intro_text' => 'scope summary',
                'footer_text' => 'footer text',
                'discount_rate' => 'discount percentage',
                'tax_rate' => 'tax percentage',
                'client.name' => 'client name',
                'client.institute_name' => 'institute name',
                'client.contact_person' => 'contact person',
                'client.phone' => 'client phone',
                'client.email' => 'client email',
                'client.address' => 'client address',
                'terms_text' => 'terms and conditions',
                'bank_details.account_name' => 'account name',
                'bank_details.bank_name' => 'bank name',
                'bank_details.account_no' => 'account number',
                'bank_details.iban' => 'IBAN',
            ]
        )->validate();

        $items = collect($validated['items'] ?? [])
            ->map(function (array $item): array {
                return [
                    'description' => trim((string) ($item['description'] ?? '')),
                    'details' => trim((string) ($item['details'] ?? '')),
                    'qty' => (float) ($item['qty'] ?? 0),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                ];
            })
            ->filter(function (array $item): bool {
                return $item['description'] !== '' || $item['qty'] > 0 || $item['unit_price'] > 0;
            })
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'At least one quotation item is required.',
            ]);
        }

        $items = $items->map(function (array $item): array {
            $amount = $item['qty'] * $item['unit_price'];

            return $item + ['amount' => $amount];
        });

        $subtotal = (float) $items->sum('amount');
        $discountRate = (float) ($validated['discount_rate'] ?? 0);
        $discount = round($subtotal * ($discountRate / 100), 2);
        $taxRate = (float) ($validated['tax_rate'] ?? 0);
        $taxableAmount = max(0, $subtotal - $discount);
        $tax = round($taxableAmount * ($taxRate / 100), 2);
        $grandTotal = max(0, $subtotal - $discount + $tax);

        return [
            'organization' => $organization,
            'quotationId' => isset($validated['quotation_id']) ? (int) $validated['quotation_id'] : null,
            'logoUrl' => $forPdf ? $this->resolveLogoDataUri($organization) : $this->resolveLogoWebPath($organization),
            'documentTitle' => ($validated['document_type'] ?? 'Quotation').' Preview',
            'documentType' => $validated['document_type'],
            'quotationNo' => $validated['quotation_no'],
            'quotationDate' => $validated['quotation_date'],
            'validUntil' => $validated['valid_until'] ?? null,
            'currency' => $validated['currency'],
            'preparedBy' => $validated['prepared_by'] ?? $request->user()?->name,
            'subject' => $validated['subject'] ?? 'Institute Management Software Proposal',
            'introText' => $validated['intro_text'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'footerText' => $validated['footer_text'] ?? null,
            'client' => $validated['client'],
            'items' => $items,
            'subtotal' => $subtotal,
            'discountRate' => $discountRate,
            'discount' => $discount,
            'taxRate' => $taxRate,
            'tax' => $tax,
            'grandTotal' => $grandTotal,
            'generatedAt' => now(),
            'terms' => $this->normalizeTerms($validated['terms_text'] ?? null),
            'bankDetails' => $this->normalizeBankDetails($validated['bank_details'] ?? []),
            'company' => [
                'name' => $organization?->name ?? config('app.name', 'MEERAHR'),
                'type' => $organization?->type ?? 'institute',
                'phone' => $organization?->phone,
                'email' => $organization?->email,
                'address' => $organization?->address,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function normalizeTerms(?string $termsText): array
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $termsText) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();

        if ($lines !== []) {
            return $lines;
        }

        return [
            '50% advance before project start.',
            '30% payable after setup completion.',
            '20% payable after training and handover.',
            'Quotation validity is subject to the mentioned deadline.',
        ];
    }

    /**
     * @param  array<string, mixed>  $bankDetails
     * @return array<string, string>
     */
    private function normalizeBankDetails(array $bankDetails): array
    {
        return [
            'Account Name' => (string) ($bankDetails['account_name'] ?? 'Your Company Name'),
            'Bank Name' => (string) ($bankDetails['bank_name'] ?? 'Your Bank'),
            'Account No' => (string) ($bankDetails['account_no'] ?? '0000000000'),
            'IBAN' => (string) ($bankDetails['iban'] ?? 'PK00BANK0000000000000000'),
        ];
    }

    private function resolveLogoWebPath(?Organization $organization): ?string
    {
        if ($organization?->logo_path) {
            return asset('storage/'.$organization->logo_path);
        }

        return null;
    }

    private function resolveLogoDataUri(?Organization $organization): ?string
    {
        $path = $organization?->logo_path ? storage_path('app/public/'.$organization->logo_path) : null;

        if (! $path || ! is_file($path)) {
            return null;
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function generateQuotationNumber(?Organization $organization, string $documentType): string
    {
        $orgCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) ($organization?->short_name ?: $organization?->name ?: config('app.name', 'MEERAHR'))) ?: 'MEER', 0, 5));
        $prefix = match (strtolower($documentType)) {
            'proposal' => 'PRP',
            'invoice' => 'INV',
            default => 'QTN',
        };
        $datePart = now()->format('Ymd');
        $numberPrefix = sprintf('%s-%s-%s-', $orgCode, $prefix, $datePart);

        $latestNumber = Quotation::withTrashed()
            ->where('quotation_no', 'like', $numberPrefix.'%')
            ->orderByDesc('quotation_no')
            ->value('quotation_no');

        $sequence = 1;

        if (is_string($latestNumber) && str_starts_with($latestNumber, $numberPrefix)) {
            $lastChunk = substr($latestNumber, strlen($numberPrefix));
            if (ctype_digit($lastChunk)) {
                $sequence = ((int) $lastChunk) + 1;
            }
        }

        return $numberPrefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function persistQuotationHistory(array $payload, ?User $user, string $lastAction): array
    {
        $generatedAt = $payload['generatedAt'] instanceof Carbon
            ? $payload['generatedAt']
            : Carbon::parse((string) ($payload['generatedAt'] ?? now()));

        $attributes = [
            'organization_id' => $payload['organization']?->id,
            'updated_by' => $user?->id,
            'deleted_by' => null,
            'document_type' => (string) $payload['documentType'],
            'quotation_no' => (string) $payload['quotationNo'],
            'quotation_date' => $payload['quotationDate'],
            'valid_until' => $payload['validUntil'],
            'currency' => (string) $payload['currency'],
            'prepared_by' => $payload['preparedBy'],
            'subject' => $payload['subject'],
            'intro_text' => $payload['introText'],
            'notes' => $payload['notes'],
            'footer_text' => $payload['footerText'],
            'client' => $payload['client'],
            'items' => collect($payload['items'])->values()->all(),
            'terms' => $payload['terms'],
            'bank_details' => $payload['bankDetails'],
            'subtotal' => $payload['subtotal'],
            'discount_rate' => $payload['discountRate'] ?? 0,
            'discount_amount' => $payload['discount'],
            'tax_rate' => $payload['taxRate'] ?? 0,
            'tax_amount' => $payload['tax'],
            'grand_total' => $payload['grandTotal'],
            'last_action' => $lastAction,
            'generated_at' => $generatedAt,
        ];

        $quotation = null;
        $quotationId = (int) ($payload['quotationId'] ?? 0);

        if ($quotationId > 0) {
            $quotation = Quotation::withTrashed()->find($quotationId);
        }

        if ($quotation) {
            if ($quotation->trashed()) {
                $quotation->restore();
            }

            $quotation->fill($attributes);
            $quotation->save();
        } else {
            $quotation = Quotation::withTrashed()->updateOrCreate(
                ['quotation_no' => (string) $payload['quotationNo']],
                $attributes + ['created_by' => $user?->id]
            );
        }

        $payload['quotationHistory'] = $quotation;
        $payload['generatedAt'] = $quotation->generated_at ?? $generatedAt;
        $payload['quotationId'] = $quotation->id;

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFromQuotation(Quotation $quotation, bool $forPdf): array
    {
        $organization = $quotation->organization;

        return [
            'organization' => $organization,
            'quotationHistory' => $quotation,
            'logoUrl' => $forPdf ? $this->resolveLogoDataUri($organization) : $this->resolveLogoWebPath($organization),
            'documentTitle' => $quotation->document_type.' Preview',
            'documentType' => $quotation->document_type,
            'quotationNo' => $quotation->quotation_no,
            'quotationDate' => $quotation->quotation_date,
            'validUntil' => $quotation->valid_until,
            'currency' => $quotation->currency,
            'preparedBy' => $quotation->prepared_by,
            'subject' => $quotation->subject,
            'introText' => $quotation->intro_text,
            'notes' => $quotation->notes,
            'footerText' => $quotation->footer_text,
            'client' => $quotation->client ?? [],
            'items' => collect($quotation->items ?? []),
            'subtotal' => (float) $quotation->subtotal,
            'discountRate' => (float) $quotation->discount_rate,
            'discount' => (float) $quotation->discount_amount,
            'taxRate' => (float) $quotation->tax_rate,
            'tax' => (float) $quotation->tax_amount,
            'grandTotal' => (float) $quotation->grand_total,
            'generatedAt' => optional($quotation->generated_at)->timezone(config('app.timezone')),
            'terms' => $quotation->terms ?? [],
            'bankDetails' => $quotation->bank_details ?? [],
            'company' => [
                'name' => $organization?->name ?? config('app.name', 'MEERAHR'),
                'type' => $organization?->type ?? 'institute',
                'phone' => $organization?->phone,
                'email' => $organization?->email,
                'address' => $organization?->address,
            ],
        ];
    }

    private function storeShareablePdf(array $payload): ?string
    {
        $filename = Str::slug((string) (($payload['client']['institute_name'] ?? 'client').'-'.($payload['quotationNo'] ?? 'quotation')));
        $filename = trim($filename, '-');
        $filename = ($filename !== '' ? $filename : 'quotation').'-share.pdf';
        $relativePath = 'quotation-shares/'.$filename;

        Storage::disk('public')->put(
            $relativePath,
            Pdf::loadView('quotations.branded', $payload)->setPaper('a4')->output()
        );

        return asset('storage/'.$relativePath);
    }

    private function findQuotation(int $quotation): Quotation
    {
        return Quotation::withTrashed()
            ->with(['organization', 'creator'])
            ->findOrFail($quotation);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFormViewData(?Organization $organization, ?Quotation $quotation = null, bool $reuse = false): array
    {
        $defaultTerms = [
            '50% advance before project start.',
            '30% payable after setup completion.',
            '20% payable after training and handover.',
            'Quotation validity is subject to the mentioned deadline.',
        ];

        $documentType = $quotation?->document_type ?? 'Quotation';
        $quotationForm = [
            'quotation_id' => $reuse ? null : $quotation?->id,
            'document_type' => $documentType,
            'quotation_no' => $reuse || ! $quotation ? $this->generateQuotationNumber($organization, $documentType) : $quotation->quotation_no,
            'quotation_date' => optional($quotation?->quotation_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
            'valid_until' => optional($quotation?->valid_until)->format('Y-m-d') ?? now()->addDays(15)->format('Y-m-d'),
            'currency' => $quotation?->currency ?? 'PKR',
            'prepared_by' => $quotation?->prepared_by,
            'subject' => $quotation?->subject ?? 'Institute Management Software Proposal',
            'intro_text' => $quotation?->intro_text ?? 'This quotation covers software setup, organization branding, branch configuration, user mapping, training, and go-live support for the client institute.',
            'notes' => $quotation?->notes,
            'discount_rate' => $quotation?->discount_rate ?? 0,
            'discount' => $quotation?->discount_amount ?? 0,
            'tax_rate' => $quotation?->tax_rate ?? 0,
            'tax' => $quotation?->tax_amount ?? 0,
            'footer_text' => $quotation?->footer_text ?? 'This is a system-ready branded quotation template for client proposals and invoice-style offers.',
            'client' => $quotation?->client ?? [],
            'bank_details' => $quotation?->bank_details ?? [],
            'terms_text' => implode("\n", $quotation?->terms ?? $defaultTerms),
        ];

        return [
            'organization' => $organization,
            'existingQuotation' => $quotation,
            'quotationForm' => $quotationForm,
            'pageTitle' => $reuse ? 'Reuse Quotation' : ($quotation ? 'Edit Quotation' : 'Quotation Generator'),
            'pageDescription' => $reuse
                ? 'Create a fresh quotation by reusing details from a previous record.'
                : ($quotation ? 'Update an existing saved quotation and regenerate its output.' : 'Create branded quotations and download them as PDF directly from the admin panel.'),
            'defaultQuotationNo' => $quotationForm['quotation_no'],
            'defaultItems' => $quotation?->items ?? [
                ['description' => 'Institute Management System Setup', 'details' => 'Core deployment and organization configuration', 'qty' => 1, 'unit_price' => 45000],
                ['description' => 'Branch and User Mapping', 'details' => 'Multi-branch setup with role mapping', 'qty' => 1, 'unit_price' => 15000],
                ['description' => 'Training and Go-Live Support', 'details' => 'Admin onboarding and launch assistance', 'qty' => 1, 'unit_price' => 12000],
            ],
        ];
    }
}
