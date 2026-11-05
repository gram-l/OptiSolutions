<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AdminAccountOtp extends Mailable
{
    public function __construct(public string $code) {}
    public function envelope(): Envelope { return new Envelope(subject: 'PolyClinic: Verify User Management access'); }
    public function content(): Content { return new Content(view: 'emails.admin_account_otp'); }
}
