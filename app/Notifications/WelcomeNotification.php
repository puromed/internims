<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to InternIMS!')
            ->greeting("Hello {$notifiable->name},")
            ->line('Welcome to **InternIMS** - your internship management portal!')
            ->line('We\'re excited to have you on board. Here\'s what you can do next:')
            ->line('1. **Upload your eligibility documents** (Resume, Transcript, Offer Letter)')
            ->line('2. **Register your internship placement** once approved')
            ->line('3. **Track your progress** through weekly logbooks')
            ->action('Get Started', route('dashboard'))
            ->line('If you have any questions, feel free to reach out to your faculty supervisor.')
            ->salutation('Regards, Admin');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'welcome',
            'title' => 'Welcome to InternIMS!',
            'message' => 'Your account has been created. Start by uploading your eligibility documents.',
            'action_url' => route('eligibility.index'),
        ];
    }
}
