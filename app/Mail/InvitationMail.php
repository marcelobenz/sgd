<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invitation $invitation;
    public string $link;

    public function __construct(Invitation $invitation, string $link)
    {
        $this->invitation = $invitation;
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject('Invitación para registrarte en el sistema')
            ->view('emails.invitation');
    }
}