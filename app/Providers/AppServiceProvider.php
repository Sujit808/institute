<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\LicenseConfig;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // if ((bool) config('security.force_https', false) && ! app()->environment('local')) {
        //     URL::forceScheme('https');
        // }

        if (!app()->environment('local')) {
        URL::forceScheme('https');
    }

        

        View::composer('layouts.app', function ($view): void {
            $licenseWarning = null;
            $organizationContext = null;
            $organization = Organization::query()->with('branches')->latest('id')->first();

            if ($organization) {
                $organizationContext = [
                    'organization' => $organization,
                    'active_branch' => null,
                    'branches' => collect(),
                ];
            }

            if (! Auth::check()) {
                $view->with('licenseWarning', $licenseWarning);
                $view->with('organizationContext', $organizationContext);

                return;
            }

            /** @var User $user */
            $user = Auth::user();

            if ($organization) {
                $availableBranches = $user->isSuperAdmin()
                    ? $organization->branches->where('is_active', true)->values()
                    : $user->branches()->where('branches.organization_id', $organization->id)->where('branches.is_active', true)->get();

                $sessionBranchId = (int) session('active_branch_id', 0);
                $activeBranch = $availableBranches->firstWhere('id', $sessionBranchId);

                if (! $activeBranch) {
                    $activeBranch = $availableBranches->firstWhere('pivot.is_primary', true) ?? $availableBranches->first();
                    if ($activeBranch) {
                        session(['active_branch_id' => $activeBranch->id]);
                    }
                }

                $organizationContext = [
                    'organization' => $organization,
                    'active_branch' => $activeBranch,
                    'branches' => $availableBranches,
                ];
            }

            $license = LicenseConfig::current();

            if (! $license || ! $license->is_active || ! $license->expires_at) {
                $view->with('licenseWarning', $licenseWarning);
                $view->with('organizationContext', $organizationContext);

                return;
            }

            $today = Carbon::today();
            $expiryDate = Carbon::parse($license->expires_at)->startOfDay();
            $daysRemaining = $today->diffInDays($expiryDate, false);

            if ($daysRemaining >= 0 && $daysRemaining <= 10) {
                $licenseWarning = [
                    'show' => true,
                    'days_remaining' => $daysRemaining,
                    'expires_at' => $expiryDate->toDateString(),
                    'expires_at_label' => $expiryDate->format('d M Y'),
                    'plan_name' => $license->plan_name,
                ];
            }

            $view->with('licenseWarning', $licenseWarning);
            $view->with('organizationContext', $organizationContext);
        });

        // Track login events
        Event::listen(Login::class, function (Login $event): void {
            try {
                AuditLog::create([
                    'user_id' => $event->user->id,
                    'module' => 'auth',
                    'action' => 'login',
                    'description' => ($event->user->name ?? 'Unknown').' logged in',
                    'ip_address' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'created_by' => $event->user->id,
                    'updated_by' => $event->user->id,
                ]);
            } catch (\Throwable) {
                // Never break the login flow
            }
        });

        // Track logout events
        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user) {
                return;
            }
            try {
                AuditLog::create([
                    'user_id' => $event->user->id,
                    'module' => 'auth',
                    'action' => 'logout',
                    'description' => ($event->user->name ?? 'Unknown').' logged out',
                    'ip_address' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'created_by' => $event->user->id,
                    'updated_by' => $event->user->id,
                ]);
            } catch (\Throwable) {
                // Never break the logout flow
            }
        });
    }
}
