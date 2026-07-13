<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConversationTranscriptMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{role: string, text: string, time: string}> $transcript
     * @param string $patientName
     */
    public function __construct(
        public array $transcript,
        public string $patientName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your PolyClinic Lipa Conversation Transcript',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.conversation-transcript',
            with: [
                'transcript' => $this->transcript,
                'patientName' => $this->patientName,
            ],
        );
    }
}