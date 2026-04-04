<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessBillingWebhookEvent;
use App\Models\BillingWebhookEvent;
use App\Services\Billing\BillingWebhookAdapterFactory;
use App\Services\Billing\BillingWebhookSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        BillingWebhookAdapterFactory $adapterFactory,
        BillingWebhookSignatureVerifier $signatureVerifier
    ): JsonResponse
    {
        $provider = (string) $request->header('X-Billing-Provider', config('services.billing.provider', 'generic'));
        $secret = (string) match (strtolower($provider)) {
            'stripe' => config('services.billing.stripe_webhook_secret', config('services.billing.webhook_secret', '')),
            'razorpay' => config('services.billing.razorpay_webhook_secret', config('services.billing.webhook_secret', '')),
            default => config('services.billing.webhook_secret', ''),
        };
        $payloadRaw = $request->getContent();
        $headerBag = collect($request->headers->all())
            ->map(fn ($values) => is_array($values) ? (string) ($values[0] ?? '') : (string) $values)
            ->all();
        $verification = $signatureVerifier->verify($provider, $payloadRaw, $secret, $headerBag);
        $signatureValid = (bool) ($verification['valid'] ?? false);
        $payload = json_decode($payloadRaw, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Invalid JSON payload.'], 422);
        }

        $adapter = $adapterFactory->forProvider($provider);
        $eventId = (string) ($adapter->eventId($payload) ?? '');
        $eventType = (string) ($adapter->eventType($payload) ?? '');

        if ($eventId === '' || $eventType === '') {
            return response()->json(['message' => 'Missing required event fields.'], 422);
        }

        $event = BillingWebhookEvent::query()->firstOrCreate(
            ['provider_event_id' => $eventId],
            [
                'provider' => $provider,
                'event_type' => $eventType,
                'signature_valid' => $signatureValid,
                'payload' => $payloadRaw,
                'processing_status' => 'pending',
            ]
        );

        if ($event->wasRecentlyCreated === false) {
            return response()->json(['message' => 'Already processed.']);
        }

        if (! $signatureValid) {
            $event->forceFill([
                'processing_status' => 'rejected',
                'error_message' => 'Invalid webhook signature. '.($verification['reason'] ?? ''),
            ])->save();

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        ProcessBillingWebhookEvent::dispatch($event->id);

        return response()->json(['message' => 'Webhook accepted for processing.'], 202);
    }
}
