<?php

namespace App\Console\Commands;

use App\Models\ImportantDate;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    protected $signature = 'app:send-deadline-reminders';

    protected $description = 'Send reminders to students for upcoming deadlines';

    public function handle(): void
    {
        $upcomingDates = ImportantDate::whereDate('date', now()->addDays(3))
            ->orWhereDate('date', now())
            ->get();

        foreach ($upcomingDates as $date) {
            $this->info("Processing reminders for: {$date->title}");

            if ($date->type === 'eligibility') {
                $this->sendEligibilityReminders($date);
            } elseif ($date->type === 'placement') {
                $this->sendPlacementReminders($date);
            }
        }
    }

    protected function sendEligibilityReminders(ImportantDate $date): void
    {
        $requiredDocTypes = ['resume', 'transcript', 'offer_letter'];

        // Find students who don't have all 3 docs approved
        $students = User::where('role', 'student')
            ->where(function ($query) use ($requiredDocTypes) {
                foreach ($requiredDocTypes as $type) {
                    $query->orWhereDoesntHave('eligibilityDocs', function ($q) use ($type) {
                        $q->where('type', $type)->where('status', 'approved');
                    });
                }
            })
            ->get();

        foreach ($students as $student) {
            $student->notify(new DeadlineReminderNotification($date, 'eligibility'));
            $this->line("Notified student: {$student->email} for eligibility");
        }
    }

    protected function sendPlacementReminders(ImportantDate $date): void
    {
        // Find students who don't have an internship record
        $students = User::where('role', 'student')
            ->whereDoesntHave('internships')
            ->get();

        foreach ($students as $student) {
            $student->notify(new DeadlineReminderNotification($date, 'placement'));
            $this->line("Notified student: {$student->email} for placement");
        }
    }
}
