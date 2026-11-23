<?php

namespace App\Mail;

use App\Models\ClinicInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sent about 30 minutes before a visit, with a button to confirm attendance.
class VisitReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $patientName,
        public string $service,
        public string $doctor,
        public string $schedule,
        public int $visitId,
        public string $confirmUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'PolyClinic Lipa: Please confirm you are coming to your visit');
    }

    public function content(): Content
    {
        $clinic = ClinicInfo::first();
        $phone = trim((string) ($clinic->contact_no ?? '')) ?: (string) config('clinic.fallback.contact_no', '');

        return new Content(
            view: 'emails.visit-reminder',
            with: [
                'firstName' => explode(' ', trim($this->patientName))[0] ?: 'there',
                'service' => $this->service,
                'doctor' => $this->doctor,
                'schedule' => $this->schedule,
                'reference' => $this->visitId,
                'confirmUrl' => $this->confirmUrl,
                'phone' => $phone,
            ],
        );
    }
}