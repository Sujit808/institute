<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateBillingWebhookPayload extends Command
{
    protected $signature = 'billing:webhook:sample
        {provider : generic|stripe|razorpay}
        {--event=subscription.updated : Event type to generate}
        {--output=payload.json : Output JSON file path}
        {--pretty : Pretty-print JSON output}';

    protected $description = 'Generate sample billing webhook payload JSON files for QA testing.';

    public function handle(): int
    {
        $provider = strtolower(trim((string) $this->argument('provider')));
        $event = trim((string) $this->option('event'));
        $output = trim((string) $this->option('output'));

        if (! in_array($provider, ['generic', 'stripe', 'razorpay'], true)) {
            $this->error('Invalid provider. Allowed: generic, stripe, razorpay');

            return self::FAILURE;
        }

        if ($output === '') {
            $this->error('Output path cannot be empty.');

            return self::FAILURE;
        }

        $payload = $this->payloadFor($provider, $event);
        $flags = JSON_UNESCAPED_SLASHES;

        if ((bool) $this->option('pretty')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($payload, $flags);
        if (! is_string($json)) {
            $this->error('Failed to encode payload JSON.');

            return self::FAILURE;
        }

        $directory = dirname($output);
        if ($directory !== '.' && ! File::exists($directory)) {
            File::makeDirectory($directory, 0777, true);
        }

        File::put($output, $json.PHP_EOL);

        $this->info('Sample payload generated: '.$output);
        $this->line('Provider: '.strtoupper($provider));
        $this->line('Event: '.$event);
        $this->line('Next step: php artisan billing:webhook:sign '.$provider.' --payload-file='.$output.' --secret=YOUR_SECRET');

        return self::SUCCESS;
    }

    private function payloadFor(string $provider, string $event): array
    {
        $now = now();

        return match ($provider) {
            'stripe' => [
                'id' => 'evt_stripe_sample_001',
                'type' => $event,
                'data' => [
                    'object' => [
                        'id' => 'sub_stripe_sample_001',
                        'customer' => 'cus_stripe_sample_001',
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
                            'source' => 'qa-sample',
                        ],
                    ],
                ],
            ],
            'razorpay' => [
                'event_id' => 'evt_rzp_sample_001',
                'event' => $event,
                'payload' => [
                    'subscription' => [
                        'entity' => [
                            'id' => 'sub_rzp_sample_001',
                            'customer_id' => 'cust_rzp_sample_001',
                            'plan_id' => 'professional',
                            'status' => 'active',
                            'currency' => 'INR',
                            'current_end' => $now->copy()->addMonth()->timestamp,
                            'charge_at' => $now->copy()->addWeek()->timestamp,
                            'ended_at' => null,
                            'notes' => [
                                'plan_key' => 'professional',
                                'source' => 'qa-sample',
                            ],
                        ],
                    ],
                    'invoice' => [
                        'entity' => [
                            'id' => 'inv_rzp_sample_001',
                            'subscription_id' => 'sub_rzp_sample_001',
                            'invoice_number' => 'RZP-INV-001',
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
                            'id' => 'pay_rzp_sample_001',
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
                'id' => 'evt_generic_sample_001',
                'type' => $event,
                'data' => [
                    'subscription' => [
                        'id' => 'sub_generic_sample_001',
                        'customer_id' => 'cus_generic_sample_001',
                        'plan_key' => 'professional',
                        'status' => 'active',
                        'amount' => 1999,
                        'currency' => 'INR',
                        'trial_ends_at' => $now->copy()->addWeek()->toIso8601String(),
                        'renews_at' => $now->copy()->addMonth()->toIso8601String(),
                        'canceled_at' => null,
                        'metadata' => [
                            'source' => 'qa-sample',
                        ],
                    ],
                    'invoice' => [
                        'id' => 'inv_generic_sample_001',
                        'subscription_id' => 'sub_generic_sample_001',
                        'invoice_number' => 'INV-GEN-001',
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
                        'id' => 'txn_generic_sample_001',
                        'invoice_id' => 'inv_generic_sample_001',
                        'gateway' => 'card',
                        'status' => 'succeeded',
                        'amount' => 1999,
                        'currency' => 'INR',
                        'paid_at' => $now->toIso8601String(),
                    ],
                ],
            ],
        };
    }
}
