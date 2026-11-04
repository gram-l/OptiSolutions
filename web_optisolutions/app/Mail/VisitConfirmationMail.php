<?php

namespace App\Mail;

use App\Models\ClinicInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Sent after a patient confirms a schedule visit in the chatbot (if they ticked
// "Email me my visit confirmation"). Replaces the old conversation transcript email.
class VisitConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $patientName,
        public string $service,
        public string $doctor,
        public string $schedule,
        public int $visitId
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'PolyClinic Lipa: Your schedule visit is confirmed');
    }

    public function content(): Content
    {
        $fallback = config('clinic.fallback', []);
        $clinic = ClinicInfo::first();

        $pick = fn (string $key) => trim((string) ($clinic->{$key} ?? '')) !== ''
            ? trim((string) $clinic->{$key})
            : (string) ($fallback[$key] ?? '');

        // operating_hours is stored as "Mon-Fri 8AM-5PM | Sat 8AM-12PM".
        $hours = array_values(array_filter(array_map('trim', explode('|', $pick('operating_hours')))));

        $reminders = array_values(array_filter(
            array_map('trim', (array) config('clinic.visit_reminders', []))
        ));

        return new Content(
            view: 'emails.visit-confirmation',
            with: [
                'firstName' => explode(' ', trim($this->patientName))[0] ?: 'there',
                'patientName' => $this->patientName,
                'service' => $this->service,
                'doctor' => $this->doctor,
                'schedule' => $this->schedule,
                'reference' => $this->visitId,
                'clinicName' => $pick('clinic_name') ?: 'PolyClinic Lipa',
                'address' => $pick('address'),
                'phone' => $pick('contact_no'),
                'hours' => $hours,
                'reminders' => $reminders,
            ],
        );
    }
}