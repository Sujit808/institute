<?php

namespace App\Services\Billing;

use App\Models\BillingSubscription;
use App\Models\LicenseConfig;

class SyncLicenseFromSubscription
{
    public function sync(LicenseConfig $license, BillingSubscription $subscription): void
    {
        $planKey = LicenseConfig::normalizedPlanKey($subscription->plan_key);
        $planPreset = LicenseConfig::availablePlanPresets()[$planKey] ?? LicenseConfig::availablePlanPresets()['starter'];

        $status = strtolower((string) $subscription->status);
        $isActive = in_array($status, ['trialing', 'active', 'past_due', 'grace_period'], true);

        $license->forceFill([
            'plan_name' => $planPreset['label'],
            'is_active' => $isActive,
            'expires_at' => $subscription->renews_at?->toDateString(),
        ])->save();
    }
}
