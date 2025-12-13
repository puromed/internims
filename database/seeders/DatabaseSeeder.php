<?php

namespace Database\Seeders;

use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\User;
use App\Notifications\LogbookEntryApprovedNotification;
use App\Notifications\LogbookEntryRevisionRequestedNotification;
use App\Notifications\NewLogbookSubmittedNotification;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding test accounts and data...');

        // ─────────────────────────────────────────────────────────────
        // 1. Create Test Users
        // ─────────────────────────────────────────────────────────────
        $student = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Alex Student',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'student',
            ]
        );
        $this->command->info("✓ Student: {$student->email}");

        $faculty = User::firstOrCreate(
            ['email' => 'faculty@example.com'],
            [
                'name' => 'Dr. Sarah Faculty',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'faculty',
            ]
        );
        $this->command->info("✓ Faculty: {$faculty->email}");

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'System Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );
        $this->command->info("✓ Admin: {$admin->email}");

        // ─────────────────────────────────────────────────────────────
        // 2. Create Internship (links student ↔ faculty supervisor)
        // ─────────────────────────────────────────────────────────────
        $internship = Internship::firstOrCreate(
            ['user_id' => $student->id],
            [
                'company_name' => 'TechCorp Solutions',
                'supervisor_name' => 'John Manager',
                'start_date' => now()->subWeeks(4),
                'end_date' => now()->addWeeks(8),
                'status' => 'active',
                'faculty_supervisor_id' => $faculty->id,
            ]
        );
        $this->command->info("✓ Internship: {$internship->company_name} (Student → Faculty linked)");

        // ─────────────────────────────────────────────────────────────
        // 3. Create Logbook Entries in various states
        // ─────────────────────────────────────────────────────────────
        $logbookData = [
            [
                'week_number' => 1,
                'entry_text' => "This week I onboarded at TechCorp Solutions. Met my team and set up my development environment. Attended orientation sessions covering company policies and project workflows. Started familiarizing myself with the codebase.",
                'status' => 'approved',
                'supervisor_status' => 'verified',
                'supervisor_comment' => 'Great start! Keep up the momentum.',
                'reviewed_at' => now()->subWeeks(3),
                'reviewed_by' => $faculty->id,
                'submitted_at' => now()->subWeeks(3)->subDays(2),
                'ai_analysis_json' => [
                    'summary' => 'Successful onboarding week with team integration and environment setup.',
                    'skills' => ['Onboarding', 'Team collaboration', 'Environment setup'],
                    'sentiment' => 'positive',
                    'analyzed_at' => now()->subWeeks(3)->toISOString(),
                ],
            ],
            [
                'week_number' => 2,
                'entry_text' => "Completed initial training modules on the company's tech stack including Laravel and Vue.js. Pair-programmed with senior developer on a bug fix. Submitted my first pull request which was reviewed and merged.",
                'status' => 'approved',
                'supervisor_status' => 'verified',
                'supervisor_comment' => 'Excellent progress with the PR!',
                'reviewed_at' => now()->subWeeks(2),
                'reviewed_by' => $faculty->id,
                'submitted_at' => now()->subWeeks(2)->subDays(2),
                'ai_analysis_json' => [
                    'summary' => 'Strong technical progress with first code contribution merged.',
                    'skills' => ['Laravel', 'Vue.js', 'Git workflow', 'Code review'],
                    'sentiment' => 'positive',
                    'analyzed_at' => now()->subWeeks(2)->toISOString(),
                ],
            ],
            [
                'week_number' => 3,
                'entry_text' => "Worked on implementing a new feature for the dashboard. Encountered challenges with state management but resolved them after discussions with the team. Attended a workshop on API design patterns.",
                'status' => 'revision_requested',
                'supervisor_status' => 'revision_requested',
                'supervisor_comment' => 'Please add more detail about the specific challenges you faced and how you resolved them.',
                'reviewed_at' => now()->subDays(5),
                'reviewed_by' => $faculty->id,
                'submitted_at' => now()->subWeeks(1),
                'ai_analysis_json' => null, // Not analyzed yet
            ],
            [
                'week_number' => 4,
                'entry_text' => "Continued feature development. Implemented unit tests for the new components. Participated in sprint planning and learned about agile methodologies. Started working on documentation for the module I'm building.",
                'status' => 'pending_review',
                'supervisor_status' => 'pending',
                'supervisor_comment' => null,
                'reviewed_at' => null,
                'reviewed_by' => null,
                'submitted_at' => now()->subDays(2),
                'ai_analysis_json' => null, // Pending AI analysis - good for testing!
            ],
        ];

        foreach ($logbookData as $data) {
            LogbookEntry::firstOrCreate(
                ['user_id' => $student->id, 'week_number' => $data['week_number']],
                $data
            );
        }
        $this->command->info('✓ Logbook entries: 4 weeks (approved, approved, revision_requested, pending_review)');

        // ─────────────────────────────────────────────────────────────
        // 4. Create Sample Notifications (direct DB insert to avoid mail)
        // ─────────────────────────────────────────────────────────────
        // Clear old notifications for demo consistency
        $student->notifications()->delete();
        $faculty->notifications()->delete();

        $approvedEntry = LogbookEntry::where('user_id', $student->id)
            ->where('week_number', 2)
            ->first();
        
        $revisionEntry = LogbookEntry::where('user_id', $student->id)
            ->where('week_number', 3)
            ->first();
        
        $pendingEntry = LogbookEntry::where('user_id', $student->id)
            ->where('week_number', 4)
            ->first();

        // Student notification: logbook approved
        if ($approvedEntry) {
            $student->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => LogbookEntryApprovedNotification::class,
                'data' => [
                    'entry_id' => $approvedEntry->id,
                    'week' => $approvedEntry->week_number,
                    'status' => 'approved',
                    'message' => "Your week {$approvedEntry->week_number} logbook entry has been approved.",
                ],
                'read_at' => null,
            ]);
        }

        // Student notification: revision requested
        if ($revisionEntry) {
            $student->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => LogbookEntryRevisionRequestedNotification::class,
                'data' => [
                    'entry_id' => $revisionEntry->id,
                    'week' => $revisionEntry->week_number,
                    'status' => 'revision_requested',
                    'comment' => 'Please add more detail about the specific challenges you faced.',
                    'message' => "Week {$revisionEntry->week_number} logbook needs revision.",
                ],
                'read_at' => null,
            ]);
        }

        // Faculty notification: new logbook submitted
        if ($pendingEntry) {
            $faculty->notifications()->create([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => NewLogbookSubmittedNotification::class,
                'data' => [
                    'entry_id' => $pendingEntry->id,
                    'week' => $pendingEntry->week_number,
                    'status' => 'submitted',
                    'student_name' => $student->name,
                    'message' => "{$student->name} submitted Week {$pendingEntry->week_number} logbook for review.",
                ],
                'read_at' => null,
            ]);
        }

        $this->command->info('✓ Notifications: seeded for both student and faculty');

        // ─────────────────────────────────────────────────────────────
        // Summary
        // ─────────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════════════╗');
        $this->command->info('║  🎉 Database seeded successfully!                            ║');
        $this->command->info('╠══════════════════════════════════════════════════════════════╣');
        $this->command->info('║  Login credentials (password: "password"):                   ║');
        $this->command->info('║    • Student:  test@example.com                              ║');
        $this->command->info('║    • Faculty:  faculty@example.com                           ║');
        $this->command->info('║    • Admin:    admin@example.com                             ║');
        $this->command->info('╚══════════════════════════════════════════════════════════════╝');
    }
}
