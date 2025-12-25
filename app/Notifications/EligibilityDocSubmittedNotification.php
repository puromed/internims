<?php

namespace App\Notifications;

use App\Models\EligibilityDoc;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EligibilityDocSubmittedNotification extends Notification
{
    public function __construct(
        public User $student,
        public EligibilityDoc $doc,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'eligibility_doc_submitted',
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'doc_id' => $this->doc->id,
            'doc_type' => $this->doc->type,
            'message' => "{$this->student->name} submitted {$this->docLabel()} for review.",
            'action_url' => route('admin.eligibility.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject('New eligibility document submitted')
            ->greeting("Hello {$notifiable->name},")
            ->line(
                "{$this->student->name} submitted {$this->docLabel()} for eligibility review.",
            )
            ->action(
                'Open Eligibility Review',
                route('admin.eligibility.index'),
            );
    }

    protected function docLabel(): string
    {
        return match ($this->doc->type) {
            'resume' => 'Resume',
            'transcript' => 'Transcript',
            'offer_letter' => 'Offer Letter',
            default => 'a document',
        };
    }
}
