<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NotificationService
{
    public static function create(
        string $type,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        DB::table('app_notifications')->insert([
            'type'           => $type,
            'title'          => $title,
            'message'        => $message,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'is_read'        => 0,
            'created_at'     => now(),
        ]);
    }

    public static function newInquiry(string $patientName, string $inquiryId): void
    {
        self::create(
            'chatbot',
            'New Chatbot Inquiry',
            "{$patientName} submitted an inquiry the chatbot couldn't answer.",
            'inquiry',
            (int) $inquiryId
        );
    }

    public static function newFeedback(string $patientName, string $feedbackId): void
    {
        self::create(
            'system',
            'New Feedback Received',
            "{$patientName} submitted new feedback.",
            'feedback',
            (int) $feedbackId
        );
    }

    public static function newComplaint(string $patientName, string $feedbackId): void
    {
        self::create(
            'system',
            'New Complaint Received',
            "{$patientName} filed a complaint that may need review.",
            'feedback',
            (int) $feedbackId
        );
    }

    public static function system(string $title, string $message): void
    {
        self::create('system', $title, $message);
    }
    //add this line
    //NotificationService::newInquiry($patientName, $inquiry->id);
}