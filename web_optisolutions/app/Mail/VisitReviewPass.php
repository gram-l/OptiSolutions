<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VisitReviewPass extends Mailable
{
    public function __construct(
        public string $patientName,
        public string $doctorName,
        public string $service,
        public string $visitDate,
        public string $transactionCode,
        public int $visitId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'PolyClinic: Your visit review pass');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.visit_review_pass', text: 'emails.visit_review_pass_text');
    }
}
