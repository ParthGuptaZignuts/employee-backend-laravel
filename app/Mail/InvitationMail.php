<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;
class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;
    public $first_name;
    public $last_name;
    public $email;
    public $company;
    public $website;
    public $resetLink;

    /**
     * Create a new message instance.
     */
    public function __construct(string $first_name,string $last_name, string $website,  string $email,string $company , $resetLink)
    {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->website = $website;
        $this->email = $email;
        $this->company=$company;
        $this->resetLink = $resetLink;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('example@example.com', 'Test Sender'),
            subject: 'Company Admin Invitation Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'admin_invitation_email',
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