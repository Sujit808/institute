<?php

namespace App\Services\Billing;

class BillingWebhookSignatureVerifier
{
    /**
     * @return array{valid: bool, reason: string}
     */
    public function verify(string $provider, string $payload, string $secret, array $headers): array
    {
        if ($secret === '') {
            return [
                'valid' => app()->environment('local', 'testing'),
                'reason' => 'No webhook secret configured.',
            ];
        }

        return match (strtolower($provider)) {
            'stripe' => $this->verifyStripe($payload, $secret, $headers),
            'razorpay' => $this->verifyRazorpay($payload, $secret, $headers),
            default => $this->verifyGeneric($payload, $secret, $headers),
        };
    }

    /**
     * @param array<string, string> $headers
     * @return array{valid: bool, reason: string}
     */
    private function verifyGeneric(string $payload, string $secret, array $headers): array
    {
        $signature = (string) ($headers['x-billing-signature'] ?? '');
        if ($signature === '') {
            return ['valid' => false, 'reason' => 'Missing X-Billing-Signature header.'];
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return [
            'valid' => hash_equals($expected, $signature),
            'reason' => 'Generic signature mismatch.',
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array{valid: bool, reason: string}
     */
    private function verifyRazorpay(string $payload, string $secret, array $headers): array
    {
        $signature = (string) ($headers['x-razorpay-signature'] ?? $headers['x-billing-signature'] ?? '');
        if ($signature === '') {
            return ['valid' => false, 'reason' => 'Missing X-Razorpay-Signature header.'];
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return [
            'valid' => hash_equals($expected, $signature),
            'reason' => 'Razorpay signature mismatch.',
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return array{valid: bool, reason: string}
     */
    private function verifyStripe(string $payload, string $secret, array $headers): array
    {
        $signatureHeader = (string) ($headers['stripe-signature'] ?? $headers['x-billing-signature'] ?? '');
        if ($signatureHeader === '') {
            return ['valid' => false, 'reason' => 'Missing Stripe-Signature header.'];
        }

        $parts = $this->parseStripeSignatureHeader($signatureHeader);
        $timestamp = isset($parts['t']) ? (int) $parts['t'] : 0;
        $signatures = $parts['v1'] ?? [];

        if ($timestamp <= 0 || $signatures === []) {
            return ['valid' => false, 'reason' => 'Malformed Stripe-Signature header.'];
        }

        $tolerance = max(30, (int) config('services.billing.webhook_tolerance', 300));
        if (abs(time() - $timestamp) > $tolerance) {
            return ['valid' => false, 'reason' => 'Stripe signature timestamp outside tolerance.'];
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return ['valid' => true, 'reason' => 'OK'];
            }
        }

        return ['valid' => false, 'reason' => 'Stripe signature mismatch.'];
    }

    /**
     * @return array{t?: string, v1?: array<int, string>}
     */
    private function parseStripeSignatureHeader(string $header): array
    {
        $parsed = [];

        foreach (explode(',', $header) as $segment) {
            $pair = explode('=', trim($segment), 2);
            if (count($pair) !== 2) {
                continue;
            }

            [$key, $value] = $pair;
            if ($key === 'v1') {
                $parsed['v1'] ??= [];
                $parsed['v1'][] = $value;
                continue;
            }

            if ($key === 't') {
                $parsed['t'] = $value;
            }
        }

        return $parsed;
    }
}
