<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $resetLink;
    public $expiresIn;
    public $userName;

    /**
     * Create a new message instance.
     */
   public function __construct($resetLink, $userName = 'User', $expiresIn = 60)
{
    $this->resetLink = $resetLink;
    $this->userName = $userName;
    $this->expiresIn = $expiresIn;
}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password - Linkud Hub',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
{
    return new Content(
        view: 'emails.password-reset',
        with: [
            'resetLink' => $this->resetLink,
            'userName' => $this->userName,
            'expiresIn' => $this->expiresIn,
            'currentYear' => date('Y'),
        ],
    );
}

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}