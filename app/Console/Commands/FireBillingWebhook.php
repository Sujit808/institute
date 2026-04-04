<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class FireBillingWebhook extends Command
{
    protected $signature = 'billing:webhook:fire
        {provider : generic|stripe|razorpay}
        {--url= : Webhook URL (defaults to APP_URL/api/billing/webhook)}
        {--allow-host=* : One-time additional allowed host(s), comma or repeated option}
        {--payload= : Raw JSON payload string}
        {--payload-file= : Path to JSON payload file}
        {--from-sample : Auto-generate provider sample payload and use it for this request}
        {--event=subscription.updated : Event type when --from-sample is used}
        {--sample-output= : Optional path to save generated sample payload JSON}
        {--secret= : Webhook secret (falls back to config/env)}
        {--timestamp= : Stripe timestamp override (unix)}
        {--timeout=15 : HTTP timeout in seconds}
        {--force : Skip interactive confirmation before sending}
        {--dry-run : Print request details without sending}
        {--show-body : Print response body when request is sent}';

    protected $description = 'Send a signed billing webhook request to an endpoint for QA simulation.';

    public function handle(): int
    {
        $provider = strtolower(trim((string) $this->argument('provider')));
        if (! in_array($provider, ['generic', 'stripe', 'razorpay'], true)) {
            $this->error('Invalid provider. Allowed: generic, stripe, razorpay');

            return self::FAILURE;
        }

        $payload = $this->resolvePayload();
        if ($payload === null) {
            return self::FAILURE;
        }

        if (json_decode($payload, true) === null) {
            $this->error('Payload must be valid JSON.');

            return self::FAILURE;
        }

        $url = trim((string) ($this->option('url') ?? ''));
        if ($url === '') {
            $url = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/').'/api/billing/webhook';
        }

        $secret = $this->resolveSecret($provider);
        if ($secret === '') {
            $this->error('Secret is empty. Provide --secret or configure billing webhook secrets in config/services.php.');

            return self::FAILURE;
        }

        [$signatureHeader, $signatureValue] = $this->signatureHeader($provider, $payload, $secret);

        $headers = [
            'Content-Type' => 'application/json',
            $signatureHeader => $signatureValue,
        ];

        if ($provider !== 'generic') {
            $headers['X-Billing-Provider'] = $provider;
        }

        $this->info('Prepared request');
        $this->line('URL: '.$url);
        $this->line('Provider: '.strtoupper($provider));
        $this->line('Signature Header: '.$signatureHeader);
        $this->line('Payload SHA256: '.hash('sha256', $payload));
        $this->newLine();
        $this->line('cURL Preview:');
        $this->line($this->curlPreview($url, $headers));

        if ((bool) $this->option('dry-run')) {
            $this->warn('Dry run enabled. Request not sent.');

            return self::SUCCESS;
        }

        if (! $this->allowHostUsageAllowedForSend()) {
            return self::FAILURE;
        }

        if (! $this->confirmBeforeSend($url, $provider)) {
            return self::FAILURE;
        }

        $timeout = max(1, (int) $this->option('timeout'));

        try {
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->withBody($payload, 'application/json')
                ->post($url);
        } catch (\Throwable $exception) {
            $this->error('Request failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Response received');
        $this->line('Status: '.$response->status());

        if ((bool) $this->option('show-body')) {
            $this->newLine();
            $this->line('Response body:');
            $this->line($response->body());
        }

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }

    private function confirmBeforeSend(string $url, string $provider): bool
    {
        if (! $this->isHostAllowed($url) && ! (bool) $this->option('force')) {
            $host = $this->normalizedHostFromUrl($url);
            $allowedHosts = implode(', ', $this->effectiveAllowedHosts());

            $this->error('Blocked send to non-allowlisted host: '.$host);
            $this->line('Allowed hosts: '.$allowedHosts);
            $this->line('Use --allow-host='.$host.' for one-time explicit allow, or --force if this is intentional.');

            return false;
        }

        if ((bool) $this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Non-interactive mode requires --force to send webhook requests.');

            return false;
        }

        $host = (string) parse_url($url, PHP_URL_HOST);
        $isLocalHost = in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);

        if (! $isLocalHost) {
            $this->warn('Target host appears non-local: '.$host);
        }

        return $this->confirm(
            'Send signed '.strtoupper($provider).' webhook request to '.$url.'?',
            false
        );
    }

    private function allowHostUsageAllowedForSend(): bool
    {
        if ($this->oneTimeAllowedHosts() === []) {
            return true;
        }

        if ((bool) $this->option('force')) {
            return true;
        }

        $this->error('For safety, --allow-host is restricted to --dry-run unless --force is provided.');

        return false;
    }

    private function isHostAllowed(string $url): bool
    {
        $host = $this->normalizedHostFromUrl($url);
        if ($host === '') {
            return false;
        }

        return in_array($host, $this->effectiveAllowedHosts(), true);
    }

    /**
     * @return array<int, string>
     */
    private function allowedHosts(): array
    {
        $hosts = config('services.billing.fire_allowed_hosts', ['localhost', '127.0.0.1', '::1']);

        return collect(is_array($hosts) ? $hosts : [])
            ->map(fn ($host) => strtolower(trim((string) $host)))
            ->filter(fn (string $host): bool => $host !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function oneTimeAllowedHosts(): array
    {
        $raw = (array) $this->option('allow-host');

        return collect($raw)
            ->flatMap(fn ($entry) => explode(',', (string) $entry))
            ->map(fn ($host) => strtolower(trim((string) $host)))
            ->map(fn ($host) => trim($host, '[] '))
            ->filter(fn (string $host): bool => $host !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function effectiveAllowedHosts(): array
    {
        return collect(array_merge($this->allowedHosts(), $this->oneTimeAllowedHosts()))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizedHostFromUrl(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return strtolower(trim($host, '[] '));
    }

    private function resolvePayload(): ?string
    {
        $inline = (string) ($this->option('payload') ?? '');
        $file = (string) ($this->option('payload-file') ?? '');
        $fromSample = (bool) $this->option('from-sample');

        if ($fromSample && ($inline !== '' || $file !== '')) {
            $this->error('Use --from-sample by itself, without --payload or --payload-file.');

            return null;
        }

        if ($fromSample) {
            return $this->samplePayloadJson();
        }

        if ($inline !== '' && $file !== '') {
            $this->error('Use either --payload or --payload-file, not both.');

            return null;
        }

        if ($file !== '') {
            if (! File::exists($file)) {
                $this->error('Payload file not found: '.$file);

                return null;
            }

            $content = (string) File::get($file);
            if (trim($content) === '') {
                $this->error('Payload file is empty.');

                return null;
            }

            return $content;
        }

        if ($inline !== '') {
            return $inline;
        }

        return json_encode([
            'id' => 'evt_fire_local_001',
            'type' => 'subscription.updated',
            'data' => [
                'subscription' => [
                    'id' => 'sub_fire_local_001',
                    'status' => 'active',
                    'plan_key' => 'starter',
                    'currency' => 'INR',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);
    }

    private function samplePayloadJson(): ?string
    {
        $provider = strtolower(trim((string) $this->argument('provider')));
        $event = trim((string) $this->option('event'));
        $now = now();

        $payload = match ($provider) {
            'stripe' => [
                'id' => 'evt_stripe_fire_001',
                'type' => $event,
                'data' => [
                    'object' => [
                        'id' => 'sub_stripe_fire_001',
                        'customer' => 'cus_stripe_fire_001',
                        'status' => 'active',
                        'currency' => 'usd',
                        'current_period_end' => $now->copy()->addMonth()->timestamp,
                        'trial_end' => $now->copy()->addWeek()->timestamp,
                        'items' => [
                            'data' => [[
                                'price' => [
                                    'unit_amount' => 4900,
                                ],
                            ]],
                        ],
                        'metadata' => [
                            'plan_key' => 'enterprise',
                            'source' => 'qa-fire-sample',
                        ],
                    ],
                ],
            ],
            'razorpay' => [
                'event_id' => 'evt_rzp_fire_001',
                'event' => $event,
                'payload' => [
                    'subscription' => [
                        'entity' => [
                            'id' => 'sub_rzp_fire_001',
                            'customer_id' => 'cust_rzp_fire_001',
                            'plan_id' => 'professional',
                            'status' => 'active',
                            'currency' => 'INR',
                            'current_end' => $now->copy()->addMonth()->timestamp,
                            'charge_at' => $now->copy()->addWeek()->timestamp,
                            'ended_at' => null,
                            'notes' => [
                                'plan_key' => 'professional',
                                'source' => 'qa-fire-sample',
                            ],
                        ],
                    ],
                    'invoice' => [
                        'entity' => [
                            'id' => 'inv_rzp_fire_001',
                            'subscription_id' => 'sub_rzp_fire_001',
                            'invoice_number' => 'RZP-FIRE-001',
                            'amount_due' => 199900,
                            'amount_paid' => 199900,
                            'currency' => 'INR',
                            'status' => 'paid',
                            'period_start' => $now->copy()->subDays(5)->timestamp,
                            'period_end' => $now->copy()->addDays(25)->timestamp,
                            'due_date' => $now->copy()->addDays(2)->timestamp,
                            'paid_at' => $now->timestamp,
                        ],
                    ],
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_rzp_fire_001',
                            'method' => 'card',
                            'status' => 'captured',
                            'amount' => 199900,
                            'currency' => 'INR',
                            'created_at' => $now->timestamp,
                        ],
                    ],
                ],
            ],
            default => [
                'id' => 'evt_generic_fire_001',
                'type' => $event,
                'data' => [
                    'subscription' => [
                        'id' => 'sub_generic_fire_001',
                        'customer_id' => 'cus_generic_fire_001',
                        'plan_key' => 'professional',
                        'status' => 'active',
                        'amount' => 1999,
                        'currency' => 'INR',
                        'trial_ends_at' => $now->copy()->addWeek()->toIso8601String(),
                        'renews_at' => $now->copy()->addMonth()->toIso8601String(),
                        'canceled_at' => null,
                        'metadata' => [
                            'source' => 'qa-fire-sample',
                        ],
                    ],
                    'invoice' => [
                        'id' => 'inv_generic_fire_001',
                        'subscription_id' => 'sub_generic_fire_001',
                        'invoice_number' => 'INV-FIRE-001',
                        'amount_due' => 1999,
                        'amount_paid' => 1999,
                        'currency' => 'INR',
                        'status' => 'paid',
                        'period_start' => $now->copy()->subDays(5)->toDateString(),
                        'period_end' => $now->copy()->addDays(25)->toDateString(),
                        'due_date' => $now->copy()->addDays(2)->toDateString(),
                        'paid_at' => $now->toIso8601String(),
                    ],
                    'transaction' => [
                        'id' => 'txn_generic_fire_001',
                        'invoice_id' => 'inv_generic_fire_001',
                        'gateway' => 'card',
                        'status' => 'succeeded',
                        'amount' => 1999,
                        'currency' => 'INR',
                        'paid_at' => $now->toIso8601String(),
                    ],
                ],
            ],
        };

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (! is_string($json)) {
            $this->error('Failed to encode sample payload JSON.');

            return null;
        }

        $sampleOutput = trim((string) ($this->option('sample-output') ?? ''));
        if ($sampleOutput !== '') {
            $directory = dirname($sampleOutput);
            if ($directory !== '.' && ! File::exists($directory)) {
                File::makeDirectory($directory, 0777, true);
            }

            File::put($sampleOutput, $json.PHP_EOL);
            $this->line('Sample payload saved: '.$sampleOutput);
        }

        return $json;
    }

    private function resolveSecret(string $provider): string
    {
        $provided = (string) ($this->option('secret') ?? '');
        if ($provided !== '') {
            return $provided;
        }

        return (string) match ($provider) {
            'stripe' => config('services.billing.stripe_webhook_secret', ''),
            'razorpay' => config('services.billing.razorpay_webhook_secret', ''),
            default => config('services.billing.webhook_secret', ''),
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function signatureHeader(string $provider, string $payload, string $secret): array
    {
        if ($provider === 'stripe') {
            $timestamp = (int) ($this->option('timestamp') ?: time());
            $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

            return ['Stripe-Signature', 't='.$timestamp.',v1='.$signature];
        }

        $signature = hash_hmac('sha256', $payload, $secret);

        if ($provider === 'razorpay') {
            return ['X-Razorpay-Signature', $signature];
        }

        return ['X-Billing-Signature', $signature];
    }

    /**
     * @param array<string, string> $headers
     */
    private function curlPreview(string $url, array $headers): string
    {
        $headerString = collect($headers)
            ->map(fn (string $value, string $name): string => '-H "'.$name.': '.$value.'"')
            ->implode(' ');

        return 'curl -X POST '.$url.' '.$headerString.' -d @payload.json';
    }
}
