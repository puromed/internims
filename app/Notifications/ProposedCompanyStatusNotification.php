<?php

namespace App\Notifications;

use App\Models\ProposedCompany;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProposedCompanyStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ProposedCompany $proposal,
        public string $status,
        public ?string $remark = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'placement_proposal',
            'proposal_id' => $this->proposal->id,
            'application_id' => $this->proposal->application_id,
            'company_name' => $this->proposal->name,
            'status' => $this->status,
            'message' => $this->status === 'approved'
                ? "Your company proposal \"{$this->proposal->name}\" was approved. You can now confirm your placement."
                : "Your company proposal \"{$this->proposal->name}\" was rejected.",
            'comment' => $this->status === 'rejected' ? $this->remark : null,
            'action_url' => route('placement.index'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->status === 'approved' ? 'Company Proposal Approved' : 'Company Proposal Rejected')
            ->line($this->toDatabase($notifiable)['message']);

        if ($this->status === 'rejected' && filled($this->remark)) {
            $mail->line("Remarks: {$this->remark}");
        }

        return $mail->action('View placement', route('placement.index'));
    }
}
