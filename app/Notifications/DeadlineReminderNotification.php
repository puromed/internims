<?php

namespace App\Notifications;

use App\Models\ImportantDate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public ImportantDate $importantDate, public string $reminderType) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysLeft = now()->diffInDays($this->importantDate->date, false);
        $daysText = $daysLeft === 0 ? 'today' : "in {$daysLeft} days";

        return (new MailMessage)
            ->subject("Reminder: {$this->importantDate->title} is approaching")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that the deadline for **{$this->importantDate->title}** is {$daysText} ({$this->importantDate->date->format('M d, Y')}).")
            ->line($this->getInstruction())
            ->action('Take Action Now', $this->getActionUrl())
            ->line('Please ensure you complete this task before the deadline to avoid any delays in your internship process.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'deadline_reminder',
            'important_date_id' => $this->importantDate->id,
            'title' => $this->importantDate->title,
            'message' => "Deadline for {$this->importantDate->title} is approaching on {$this->importantDate->date->format('M d, Y')}.",
            'action_url' => $this->getActionUrl(),
        ];
    }

    protected function getInstruction(): string
    {
        return $this->reminderType === 'eligibility'
            ? 'You still have pending eligibility documents to upload or be approved.'
            : 'You have not yet confirmed your internship placement.';
    }

    protected function getActionUrl(): string
    {
        return $this->reminderType === 'eligibility'
            ? route('eligibility.index')
            : route('placement.index');
    }
}
