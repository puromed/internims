<?php

namespace App\Notifications;

use App\Models\LogbookEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewLogbookSubmittedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public LogbookEntry $entry
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'entry_id' => $this->entry->id,
            'week' => $this->entry->week_number,
            'status' => 'submitted',
            'student_name' => $this->entry->user->name,
            'message' => "{$this->entry->user->name} submitted Week {$this->entry->week_number} logbook for review.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
           ->subject('New Logbook Submitted - ' . $this->entry->user->name)
           ->greeting('New Submission')
           ->line("{$this->entry->user->name} submitted Week {$this->entry->week_number} logbook for review.")
           ->action('Review Logbook', url('/faculty/logbooks/' . $this->entry->id))
           ->line('Please review the submission.');
    }
}
