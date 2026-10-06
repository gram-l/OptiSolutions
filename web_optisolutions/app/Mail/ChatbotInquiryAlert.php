<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ChatbotInquiryAlert extends Mailable
{
    public function __construct(
        public string $alertTitle,
        public string $alertMessage,
        public ?int $inquiryId,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'PolyClinic: ' . $this->alertTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.chatbot_inquiry_alert', text: 'emails.chatbot_inquiry_alert_text');
    }
}
