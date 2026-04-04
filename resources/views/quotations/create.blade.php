@extends('layouts.app')

@section('content')
@php
    $quotationForm = $quotationForm ?? [];
    $items = old('items', $defaultItems ?? [
        ['description' => '', 'details' => '', 'qty' => 1, 'unit_price' => 0],
    ]);
    $existingQuotation = $existingQuotation ?? null;
@endphp

<div class="container-fluid px-4 py-4">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
        <div>
            <span class="eyebrow">Sales Documents</span>
            <h1 class="h2 mb-1">{{ $pageTitle ?? 'Quotation Generator' }}</h1>
            <p class="text-body-secondary mb-0">{{ $pageDescription ?? 'Create branded quotations and download them as PDF directly from the admin panel.' }}</p>
        </div>
        <div>
            <div class="d-flex gap-2">
                @if ($existingQuotation)
                    <a href="{{ route('quotations.reuse', $existingQuotation->id) }}" class="btn btn-outline-secondary">Reuse as New</a>
                @endif
                <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">Quotation History</a>
            </div>
        </div>
    </div>

    <div class="card app-card border-0 shadow-sm">
        <div class="card-body p-4">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Please fix the following:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('quotations.preview') }}" class="row g-3" id="quotationForm">
                @csrf
                <input type="hidden" name="quotation_id" value="{{ old('quotation_id', $quotationForm['quotation_id'] ?? '') }}">

                <div class="col-md-3">
                    <label class="form-label">Document Type</label>
                    <select name="document_type" class="form-select">
                        @foreach (['Quotation', 'Proposal', 'Invoice'] as $type)
                            <option value="{{ $type }}" {{ old('document_type', $quotationForm['document_type'] ?? 'Quotation') === $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Quotation No</label>
                    <div class="input-group">
                        <input type="text" name="quotation_no" id="quotationNoInput" class="form-control" value="{{ old('quotation_no', $quotationForm['quotation_no'] ?? $defaultQuotationNo ?? 'QT-'.now()->format('Ym').'-001') }}">
                        <button type="button" class="btn btn-outline-secondary" id="generateQuotationNoBtn">Auto Generate</button>
                    </div>
                    <input type="hidden" id="generateQuotationNoUrl" value="{{ route('quotations.generate-number') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Quotation Date</label>
                    <input type="date" name="quotation_date" class="form-control" value="{{ old('quotation_date', $quotationForm['quotation_date'] ?? now()->format('Y-m-d')) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Valid Until</label>
                    <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $quotationForm['valid_until'] ?? now()->addDays(15)->format('Y-m-d')) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                        <option value="PKR" {{ old('currency', $quotationForm['currency'] ?? 'PKR') === 'PKR' ? 'selected' : '' }}>PKR</option>
                        <option value="INR" {{ old('currency', $quotationForm['currency'] ?? 'PKR') === 'INR' ? 'selected' : '' }}>INR</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Prepared By</label>
                    <input type="text" name="prepared_by" class="form-control" value="{{ old('prepared_by', $quotationForm['prepared_by'] ?? auth()->user()->name) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" value="{{ old('subject', $quotationForm['subject'] ?? 'Institute Management Software Proposal') }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Intro / Scope Summary</label>
                    <textarea name="intro_text" rows="3" class="form-control">{{ old('intro_text', $quotationForm['intro_text'] ?? 'This quotation covers software setup, organization branding, branch configuration, user mapping, training, and go-live support for the client institute.') }}</textarea>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                <div class="col-12">
                    <h2 class="h5 mb-0">Client Information</h2>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Client Name <span class="text-danger">*</span></label>
                    <input type="text" name="client[name]" class="form-control @error('client.name') is-invalid @enderror" value="{{ old('client.name', $quotationForm['client']['name'] ?? '') }}" placeholder="Enter client name" required>
                    @error('client.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Institute Name <span class="text-danger">*</span></label>
                    <input type="text" name="client[institute_name]" class="form-control @error('client.institute_name') is-invalid @enderror" value="{{ old('client.institute_name', $quotationForm['client']['institute_name'] ?? '') }}" placeholder="Enter institute name" required>
                    @error('client.institute_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="client[contact_person]" class="form-control" value="{{ old('client.contact_person', $quotationForm['client']['contact_person'] ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="client[phone]" class="form-control" value="{{ old('client.phone', $quotationForm['client']['phone'] ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="client[email]" class="form-control" value="{{ old('client.email', $quotationForm['client']['email'] ?? '') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Address</label>
                    <input type="text" name="client[address]" class="form-control" value="{{ old('client.address', $quotationForm['client']['address'] ?? '') }}">
                </div>

                <div class="col-12"><hr class="my-1"></div>

                <div class="col-12 d-flex align-items-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Line Items</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="addItemRowBtn">Add Item</button>
                </div>

                <div class="col-12">
                    <div class="table-responsive">
                        <table class="table align-middle" id="quotationItemsTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 220px;">Description</th>
                                    <th style="min-width: 220px;">Details</th>
                                    <th style="width: 110px;">Qty</th>
                                    <th style="width: 150px;">Unit Price</th>
                                    <th style="width: 150px;">Amount</th>
                                    <th style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $index => $item)
                                    <tr>
                                        <td><input type="text" name="items[{{ $index }}][description]" class="form-control" value="{{ $item['description'] ?? '' }}"></td>
                                        <td><input type="text" name="items[{{ $index }}][details]" class="form-control" value="{{ $item['details'] ?? '' }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][qty]" class="form-control" value="{{ $item['qty'] ?? 1 }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" class="form-control" value="{{ $item['unit_price'] ?? 0 }}"></td>
                                        <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][amount]" class="form-control bg-light" value="{{ number_format(((float) ($item['qty'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 2, '.', '') }}" readonly></td>
                                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-item-row">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @error('items')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Discount %</label>
                    <input type="number" step="0.01" min="0" max="100" name="discount_rate" id="discountRateInput" class="form-control" value="{{ old('discount_rate', $quotationForm['discount_rate'] ?? 0) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tax %</label>
                    <input type="number" step="0.01" min="0" max="100" name="tax_rate" id="taxRateInput" class="form-control" value="{{ old('tax_rate', $quotationForm['tax_rate'] ?? 0) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes', $quotationForm['notes'] ?? '') }}" placeholder="Optional note for the client">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Subtotal</label>
                    <div class="input-group">
                        <span class="input-group-text" data-currency-symbol>Rs</span>
                        <input type="number" step="0.01" min="0" id="subtotalDisplay" class="form-control bg-light" value="0.00" readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Discount Amount</label>
                    <div class="input-group">
                        <span class="input-group-text" data-currency-symbol>Rs</span>
                        <input type="number" step="0.01" min="0" name="discount" id="discountAmountInput" class="form-control bg-light" value="{{ old('discount', $quotationForm['discount'] ?? 0) }}" readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Tax Amount</label>
                    <div class="input-group">
                        <span class="input-group-text" data-currency-symbol>Rs</span>
                        <input type="number" step="0.01" min="0" name="tax" id="taxAmountInput" class="form-control bg-light" value="{{ old('tax', $quotationForm['tax'] ?? 0) }}" readonly>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Grand Total</label>
                    <div class="input-group">
                        <span class="input-group-text" data-currency-symbol>Rs</span>
                        <input type="number" step="0.01" min="0" id="grandTotalDisplay" class="form-control bg-light fw-semibold" value="0.00" readonly>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                <div class="col-12">
                    <h2 class="h5 mb-0">Terms and Bank Details</h2>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Terms and Conditions</label>
                    <textarea name="terms_text" rows="6" class="form-control">{{ old('terms_text', $quotationForm['terms_text'] ?? "50% advance before project start.\n30% payable after setup completion.\n20% payable after training and handover.\nQuotation validity is subject to the mentioned deadline.") }}</textarea>
                </div>

                <div class="col-md-6">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Account Name</label>
                            <input type="text" name="bank_details[account_name]" class="form-control" value="{{ old('bank_details.account_name', $quotationForm['bank_details']['account_name'] ?? $organization?->name ?? 'Your Company Name') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_details[bank_name]" class="form-control" value="{{ old('bank_details.bank_name', $quotationForm['bank_details']['bank_name'] ?? 'Your Bank') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Account No</label>
                            <input type="text" name="bank_details[account_no]" class="form-control" value="{{ old('bank_details.account_no', $quotationForm['bank_details']['account_no'] ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="bank_details[iban]" class="form-control" value="{{ old('bank_details.iban', $quotationForm['bank_details']['iban'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Footer Text</label>
                    <input type="text" name="footer_text" class="form-control" value="{{ old('footer_text', $quotationForm['footer_text'] ?? 'This is a system-ready branded quotation template for client proposals and invoice-style offers.') }}">
                </div>

                <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                    <button type="submit" class="btn btn-outline-primary" formaction="{{ route('quotations.preview') }}" formtarget="_blank">Preview HTML</button>
                    <button type="submit" class="btn btn-primary" formaction="{{ route('quotations.download') }}">Download PDF</button>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                <div class="col-12">
                    <h2 class="h5 mb-0">Share Generated Quotation</h2>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Send Email To</label>
                    <input type="email" name="share_email_to" class="form-control" value="{{ old('share_email_to', old('client.email')) }}" placeholder="client@example.com">
                </div>

                <div class="col-md-7">
                    <label class="form-label">Email Message</label>
                    <input type="text" name="share_email_message" class="form-control" value="{{ old('share_email_message', 'Please find the attached quotation PDF for your review.') }}">
                </div>

                <div class="col-md-5">
                    <label class="form-label">WhatsApp Number</label>
                    <input type="text" name="share_whatsapp_number" class="form-control" value="{{ old('share_whatsapp_number', old('client.phone')) }}" placeholder="923001234567">
                </div>

                <div class="col-md-7">
                    <label class="form-label">WhatsApp Message</label>
                    <input type="text" name="share_whatsapp_message" class="form-control" value="{{ old('share_whatsapp_message', 'Please review the attached quotation details.') }}">
                </div>

                <div class="col-12 d-flex flex-wrap justify-content-end gap-2">
                    <button type="submit" class="btn btn-outline-success" formaction="{{ route('quotations.share-email') }}">Send Email</button>
                    <button type="submit" class="btn btn-outline-dark" formaction="{{ route('quotations.share-whatsapp') }}" formtarget="_blank">Share on WhatsApp</button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="quotationItemRowTemplate">
    <tr>
        <td><input type="text" class="form-control" data-field="description"></td>
        <td><input type="text" class="form-control" data-field="details"></td>
        <td><input type="number" step="0.01" min="0" class="form-control" data-field="qty" value="1"></td>
        <td><input type="number" step="0.01" min="0" class="form-control" data-field="unit_price" value="0"></td>
        <td><input type="number" step="0.01" min="0" class="form-control bg-light" data-field="amount" value="0.00" readonly></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-item-row">Remove</button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const addItemRowBtn = document.getElementById('addItemRowBtn');
    const itemsTableBody = document.querySelector('#quotationItemsTable tbody');
    const rowTemplate = document.getElementById('quotationItemRowTemplate');
    const generateQuotationNoBtn = document.getElementById('generateQuotationNoBtn');
    const quotationNoInput = document.getElementById('quotationNoInput');
    const generateQuotationNoUrl = document.getElementById('generateQuotationNoUrl');
    const documentTypeInput = document.querySelector('select[name="document_type"]');
    const discountRateInput = document.getElementById('discountRateInput');
    const taxRateInput = document.getElementById('taxRateInput');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const discountAmountInput = document.getElementById('discountAmountInput');
    const taxAmountInput = document.getElementById('taxAmountInput');
    const grandTotalDisplay = document.getElementById('grandTotalDisplay');
    const currencyInput = document.querySelector('select[name="currency"]');
    const currencySymbolTargets = document.querySelectorAll('[data-currency-symbol]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    if (!addItemRowBtn || !itemsTableBody || !rowTemplate) {
        return;
    }

    const reindexRows = function () {
        itemsTableBody.querySelectorAll('tr').forEach(function (row, index) {
            row.querySelectorAll('[data-field], input[name*="items["]').forEach(function (input) {
                const field = input.getAttribute('data-field') || input.name.match(/\[(description|details|qty|unit_price|amount)\]$/)?.[1];

                if (!field) {
                    return;
                }

                input.name = 'items[' + index + '][' + field + ']';
            });
        });
    };

    const toNumber = function (value) {
        const parsed = parseFloat(value);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    const currentCurrencySymbol = function () {
        return currencyInput && currencyInput.value === 'INR' ? '₹' : 'Rs';
    };

    const updateCurrencySymbols = function () {
        const symbol = currentCurrencySymbol();

        currencySymbolTargets.forEach(function (target) {
            target.textContent = symbol;
        });
    };

    const updateTotals = function () {
        let subtotal = 0;

        itemsTableBody.querySelectorAll('tr').forEach(function (row) {
            const qtyInput = row.querySelector('input[name*="[qty]"]');
            const unitPriceInput = row.querySelector('input[name*="[unit_price]"]');
            const amountInput = row.querySelector('input[name*="[amount]"]');
            const amount = toNumber(qtyInput ? qtyInput.value : 0) * toNumber(unitPriceInput ? unitPriceInput.value : 0);

            if (amountInput) {
                amountInput.value = amount.toFixed(2);
            }

            subtotal += amount;
        });

        const discountRate = Math.min(100, Math.max(0, toNumber(discountRateInput ? discountRateInput.value : 0)));
        const discountAmount = subtotal * (discountRate / 100);
        const taxableAmount = Math.max(0, subtotal - discountAmount);
        const taxRate = Math.min(100, Math.max(0, toNumber(taxRateInput ? taxRateInput.value : 0)));
        const taxAmount = taxableAmount * (taxRate / 100);
        const grandTotal = Math.max(0, taxableAmount + taxAmount);

        if (subtotalDisplay) {
            subtotalDisplay.value = subtotal.toFixed(2);
        }

        if (discountAmountInput) {
            discountAmountInput.value = discountAmount.toFixed(2);
        }

        if (taxAmountInput) {
            taxAmountInput.value = taxAmount.toFixed(2);
        }

        if (grandTotalDisplay) {
            grandTotalDisplay.value = grandTotal.toFixed(2);
        }
    };

    addItemRowBtn.addEventListener('click', function () {
        const fragment = rowTemplate.content.cloneNode(true);
        itemsTableBody.appendChild(fragment);
        reindexRows();
        updateTotals();
    });

    itemsTableBody.addEventListener('click', function (event) {
        const trigger = event.target.closest('.remove-item-row');
        if (!trigger) {
            return;
        }

        if (itemsTableBody.querySelectorAll('tr').length === 1) {
            itemsTableBody.querySelector('input[name*="[description]"]').value = '';
            itemsTableBody.querySelector('input[name*="[details]"]').value = '';
            itemsTableBody.querySelector('input[name*="[qty]"]').value = '1';
            itemsTableBody.querySelector('input[name*="[unit_price]"]').value = '0';
            itemsTableBody.querySelector('input[name*="[amount]"]').value = '0.00';
            updateTotals();
            return;
        }

        trigger.closest('tr')?.remove();
        reindexRows();
        updateTotals();
    });

    itemsTableBody.addEventListener('input', function (event) {
        if (event.target.matches('input[name*="[qty]"], input[name*="[unit_price]"]')) {
            updateTotals();
        }
    });

    discountRateInput?.addEventListener('input', updateTotals);
    taxRateInput?.addEventListener('input', updateTotals);
    currencyInput?.addEventListener('change', updateCurrencySymbols);

    reindexRows();
    updateTotals();
    updateCurrencySymbols();

    if (generateQuotationNoBtn && quotationNoInput && generateQuotationNoUrl) {
        generateQuotationNoBtn.addEventListener('click', async function () {
            generateQuotationNoBtn.disabled = true;
            generateQuotationNoBtn.textContent = 'Generating...';

            try {
                const response = await fetch(generateQuotationNoUrl.value, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        document_type: documentTypeInput ? documentTypeInput.value : 'Quotation',
                    }),
                });

                if (response.ok) {
                    const payload = await response.json();
                    if (payload.quotation_no) {
                        quotationNoInput.value = payload.quotation_no;
                        return;
                    }
                }
            } catch (error) {
                const typeMap = {
                    Proposal: 'PRP',
                    Invoice: 'INV',
                    Quotation: 'QTN',
                };
                const selectedType = documentTypeInput ? documentTypeInput.value : 'Quotation';
                const prefix = typeMap[selectedType] || 'QTN';
                const now = new Date();
                const pad = function (value) {
                    return String(value).padStart(2, '0');
                };
                quotationNoInput.value = ['MEER', prefix, now.getFullYear() + pad(now.getMonth() + 1) + pad(now.getDate()), pad(now.getHours()) + pad(now.getMinutes()) + pad(now.getSeconds())].join('-');
            } finally {
                generateQuotationNoBtn.disabled = false;
                generateQuotationNoBtn.textContent = 'Auto Generate';
            }
        });
    }
});
</script>
@endpush