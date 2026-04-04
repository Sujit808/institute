<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SchoolModuleRegistry;
use Illuminate\Console\Command;

class SyncRolePermissions extends Command
{
    protected $signature = 'permissions:sync
        {--role= : Only sync one role (admin, hr, teacher)}
        {--email=* : Only sync selected user email(s)}
        {--dry-run : Show changes without saving them}';

    protected $description = 'Merge recommended module permissions into existing admin, HR, and teacher accounts.';

    public function handle(): int
    {
        $allowedRoles = ['admin', 'hr', 'teacher'];
        $selectedRole = (string) $this->option('role');
        $emails = collect((array) $this->option('email'))->filter()->values();
        $dryRun = (bool) $this->option('dry-run');

        if ($selectedRole !== '' && ! in_array($selectedRole, $allowedRoles, true)) {
            $this->error('Invalid role. Allowed values: admin, hr, teacher');

            return self::FAILURE;
        }

        $query = User::query()->whereIn('role', $selectedRole !== '' ? [$selectedRole] : $allowedRoles);

        if ($emails->isNotEmpty()) {
            $query->whereIn('email', $emails->all());
        }

        $users = $query->orderBy('role')->orderBy('email')->get();

        if ($users->isEmpty()) {
            $this->warn('No matching users found.');

            return self::SUCCESS;
        }

        $updatedCount = 0;

        foreach ($users as $user) {
            $current = array_values(array_unique(array_filter($user->permissions ?? [])));
            $defaults = SchoolModuleRegistry::defaultPermissionsForRole((string) $user->role);
            $merged = array_values(array_unique(array_merge($current, $defaults)));

            sort($current);
            sort($merged);

            if ($current === $merged) {
                $this->line("OK  {$user->email} ({$user->role})");

                continue;
            }

            $updatedCount++;
            $this->line(($dryRun ? 'DRY' : 'UPD')." {$user->email} ({$user->role}) => ".implode(', ', $merged));

            if (! $dryRun) {
                $user->permissions = $merged;
                $user->save();
            }
        }

        $this->info(($dryRun ? 'Dry run complete.' : 'Permission sync complete.')." {$updatedCount} user(s) needed updates.");

        return self::SUCCESS;
    }
}
