<?php

namespace App\Mail;

use App\Enums\LoginIntent;
use App\Models\LoginLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public LoginLink $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->link->intent === LoginIntent::Signup ? 'Activate your Rasoi account' : 'Your Rasoi login link',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.login-link',
            with: [
                'signup' => $this->link->intent === LoginIntent::Signup,
                'url' => $this->link->url(),
                'minutes' => LoginLink::VALID_MINUTES,
            ],
        );
    }
}
