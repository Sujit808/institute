<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateBillingWebhookSignature extends Command
{
    protected $signature = 'billing:webhook:sign
        {provider : generic|stripe|razorpay}
        {--payload= : Raw JSON payload string}
        {--payload-file= : Path to JSON payload file}
        {--secret= : Webhook secret (falls back to config/env)}
        {--timestamp= : Stripe timestamp override (unix)}
        {--event-id=evt_local_001 : Optional event id for sample headers}';

    protected $description = 'Generate billing webhook signature headers for QA testing.';

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

        $secret = $this->resolveSecret($provider);
        if ($secret === '') {
            $this->error('Secret is empty. Provide --secret or configure billing webhook secrets in config/services.php.');

            return self::FAILURE;
        }

        [$headerName, $headerValue] = $this->signatureHeader($provider, $payload, $secret);

        $this->info('Provider: '.strtoupper($provider));
        $this->line('Header: '.$headerName.': '.$headerValue);
        $this->newLine();
        $this->line('Payload SHA256: '.hash('sha256', $payload));
        $this->line('Event ID hint: '.$this->option('event-id'));
        $this->newLine();
        $this->line('Sample cURL:');
        $this->line($this->sampleCurl($provider, $headerName, $headerValue));

        return self::SUCCESS;
    }

    private function resolvePayload(): ?string
    {
        $inline = (string) ($this->option('payload') ?? '');
        $file = (string) ($this->option('payload-file') ?? '');

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

        $this->warn('No payload provided, using minimal sample payload.');

        return json_encode([
            'id' => (string) $this->option('event-id'),
            'type' => 'subscription.updated',
            'data' => [
                'subscription' => [
                    'id' => 'sub_local_001',
                    'status' => 'active',
                    'plan_key' => 'starter',
                    'currency' => 'INR',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);
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

    private function sampleCurl(string $provider, string $headerName, string $headerValue): string
    {
        $providerHeader = $provider !== 'generic' ? '-H "X-Billing-Provider: '.$provider.'" ' : '';

        return 'curl -X POST http://127.0.0.1:8000/api/billing/webhook '
            .$providerHeader
            .'-H "'.$headerName.': '.$headerValue.'" '
            .'-H "Content-Type: application/json" '
            .'-d @payload.json';
    }
}
