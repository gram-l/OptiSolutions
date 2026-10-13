<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

class VisitReviewPass extends Mailable
{
    public string $reviewUrl;
    public function __construct(
        public string $patientName,
        public string $doctorName,
        public string $service,
        public string $visitDate,
        public string $transactionCode,
        public int $visitId,
    ) {
        // Fragments stay in the browser and are not sent in HTTP requests.
        $this->reviewUrl = rtrim(config('app.url'), '/') . '/home#review-code=' . rawurlencode($transactionCode);
    }

    public function qrCodePng(): string
    {
        $matrix = Encoder::encode($this->reviewUrl, ErrorCorrectionLevel::M())->getMatrix();
        $scale = 6;
        $border = 4;
        $modules = $matrix->getWidth();
        $size = ($modules + $border * 2) * $scale;
        $pixels = '';
        // Grayscale PNG needs only zlib, without a GD/Imagick requirement on the VPS.
        for ($y = -$border; $y < $modules + $border; $y++) {
            $row = "\0";
            for ($x = -$border; $x < $modules + $border; $x++) {
                $black = $x >= 0 && $y >= 0 && $x < $modules && $y < $modules && $matrix->get($x, $y) === 1;
                $row .= str_repeat($black ? "\0" : "\xff", $scale);
            }
            $pixels .= str_repeat($row, $scale);
        }
        $chunk = static fn (string $type, string $data): string => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', pack('NNCCCCC', $size, $size, 8, 0, 0, 0, 0))
            . $chunk('IDAT', gzcompress($pixels)) . $chunk('IEND', '');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'PolyClinic: Your visit review pass');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.visit_review_pass', text: 'emails.visit_review_pass_text', with: ['qrCodePng' => $this->qrCodePng()]);
    }
}
