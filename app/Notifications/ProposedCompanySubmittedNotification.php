<?php

namespace App\Notifications;

use App\Models\Application;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposedCompanySubmittedNotification extends Notification
{
    /**
     * @param  array<int, string>  $companyNames
     */
    public function __construct(
        public User $student,
        public Application $application,
        public array $companyNames,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'company_proposals_submitted',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'application_id' => $this->application->id,
            'company_names' => $this->companyNames,
            'message' => "{$this->student->name} submitted company proposals for review.",
            'action_url' => route('admin.companies.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $companies = collect($this->companyNames)
            ->filter()
            ->take(3)
            ->implode(', ');

        $line = filled($companies)
            ? "{$this->student->name} submitted company proposals for review: {$companies}"
            : "{$this->student->name} submitted company proposals for review.";

        return (new MailMessage)
            ->subject('New company proposals submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line($line)
            ->action('Review proposals', route('admin.companies.index'));
    }
}
