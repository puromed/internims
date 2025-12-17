<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EligibilityStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $student,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->status === 'approved'
            ? 'Your eligibility has been approved!'
            : 'Eligibility review update';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line($this->getMessage())
            ->action('View Details', url('/eligibility'))
            ->line('Thank you for using our system!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'student_id' => $this->student->id,
            'status' => $this->status,
        ];
    }

    protected function getMessage(): string
    {
        return $this->status === 'approved'
            ? 'Your eligibility documents have been approved. You can now proceed to the placement stage.'
            : 'Your eligibility documents require attention. Please review and resubmit if needed.';
    }
}
