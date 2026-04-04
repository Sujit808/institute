<?php

namespace App\Console\Commands;

use App\Models\AdmissionLead;
use App\Models\SchoolNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class NotifyAdmissionFollowups extends Command
{
    protected $signature = 'admission:followups:notify {--dry-run : Show counts only without writing notifications}';

    protected $description = 'Create daily in-app notifications for due and overdue admission lead follow-ups.';

    public function handle(): int
    {
        if (! Schema::hasTable('admission_leads') || ! Schema::hasTable('school_notifications')) {
            $this->warn('Required tables are missing. Run migrations first, then re-run this command.');

            return self::SUCCESS;
        }

        $today = Carbon::now()->startOfDay();
        $dryRun = (bool) $this->option('dry-run');

        $query = AdmissionLead::query()
            ->with('assignedToStaff')
            ->where('status', 'active')
            ->whereNotIn('stage', ['converted', 'lost'])
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', $today->copy()->endOfDay())
            ->orderBy('next_follow_up_at');

        $leads = $query->get();

        if ($leads->isEmpty()) {
            $this->info('No due or overdue admission follow-ups found.');

            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;

        foreach ($leads as $lead) {
            $followUpAt = $lead->next_follow_up_at;
            $type = $followUpAt && $followUpAt->copy()->startOfDay()->lt($today) ? 'overdue' : 'due_today';
            $title = $type === 'overdue' ? 'Admission Follow-up Overdue' : 'Admission Follow-up Due Today';

            $message = sprintf(
                '%s (%s) needs follow-up. Stage: %s. Counselor: %s. Next follow-up: %s.',
                $lead->student_name,
                $lead->phone,
                str_replace('_', ' ', $lead->stage),
                $lead->assignedToStaff?->full_name ?? 'Unassigned',
                $followUpAt?->format('d M Y h:i A') ?? 'N/A'
            );

            if ($dryRun) {
                $this->line('[dry-run] '.$title.' -> Lead #'.$lead->id.' '.$lead->student_name);
                continue;
            }

            $existing = SchoolNotification::query()
                ->where('source_type', 'admission_follow_up')
                ->where('source_id', $lead->id)
                ->whereDate('publish_date', $today->toDateString())
                ->first();

            if ($existing) {
                $existing->update([
                    'title' => $title,
                    'message' => $message,
                    'audience' => 'admin',
                    'status' => 'published',
                    'publish_date' => $today->toDateString(),
                    'updated_by' => null,
                ]);
                $updated++;
                continue;
            }

            SchoolNotification::query()->create([
                'title' => $title,
                'message' => $message,
                'audience' => 'admin',
                'academic_class_id' => $lead->academic_class_id,
                'section_id' => null,
                'source_type' => 'admission_follow_up',
                'source_id' => $lead->id,
                'publish_date' => $today->toDateString(),
                'status' => 'published',
                'created_by' => null,
                'updated_by' => null,
            ]);

            $created++;
        }

        $this->info('Admission follow-up reminder run completed.');
        $this->line('Total leads scanned: '.$leads->count());
        $this->line('Notifications created: '.$created);
        $this->line('Notifications updated: '.$updated);

        return self::SUCCESS;
    }
}
