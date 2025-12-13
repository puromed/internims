<?php

namespace App\Notifications;

use App\Models\LogbookEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LogbookEntryApprovedNotification extends Notification
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
            'status' => 'approved',
            'message' => "Your week {$this->entry->week_number} logbook entry has been approved."
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new  \Illuminate\Notifications\Messages\MailMessage)
             ->subject('Logbook Approved - Week ' . $this->entry->week_number)
             ->greeting('Good news!')
             ->line("Your Week {$this->entry->week_number} logbook has been approved.")
             ->action('View Logbook', url('/logbooks'))
             ->line('Keep up the great work!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
