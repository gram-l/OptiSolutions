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
            'updated_at'     => now(),
        ]);
    }

    // type: 'chat_inquiry' — matches the Flutter app's NotifType.inquiry
    public static function newInquiry(string $patientName, int $inquiryId): void
    {
        self::create(
            'chat_inquiry',
            'New Chatbot Inquiry',
            "{$patientName} submitted an inquiry the chatbot couldn't answer.",
            'inquiry',
            $inquiryId
        );
    }

    // type: 'feedback' — matches the Flutter app's NotifType.feedback
    // Ratings of 3 or below are flagged so admin can spot them quickly.
    public static function newFeedback(string $patientName, int $feedbackId, ?int $rating = null): void
    {
        if ($rating !== null && $rating <= 3) {
            self::create(
                'feedback',
                'Low Rating Received',
                "{$patientName} rated us {$rating}/5. This may need follow-up.",
                'feedback',
                $feedbackId
            );
            return;
        }

        self::create(
            'feedback',
            'New Feedback Received',
            "{$patientName} submitted new feedback.",
            'feedback',
            $feedbackId
        );
    }

    // type: 'chat_inquiry' so STAFF (not just admin) see it — the patient
    // gave a low rating and asked for someone to reach out. Opens the
    // inquiry thread so staff can reply to them in the chat.
    public static function lowRatingFollowUp(string $patientName, int $rating, int $inquiryId): void
    {
        self::create(
            'chat_inquiry',
            'Low Rating – Patient Wants a Follow-up',
            "{$patientName} rated us {$rating}/5 and asked for staff to reach out.",
            'inquiry',
            $inquiryId
        );
    }

    // type: 'complaint' — matches the Flutter app's NotifType.complaint
    public static function newComplaint(string $patientName, int $complaintId): void
    {
        self::create(
            'complaint',
            'New Complaint Received',
            "{$patientName} filed a complaint that may need review.",
            'complaint',
            $complaintId
        );
    }

    // type: 'appointment' — new; scheduled visits didn't have a helper yet
    public static function newVisit(string $patientName, string $doctorName, string $visitDate, int $visitId): void
    {
        self::create(
            'appointment',
            'New Schedule Visit',
            "{$patientName} scheduled a visit with {$doctorName} on "
                . \Carbon\Carbon::parse($visitDate)->format('M d, Y') . '.',
            'visit',
            $visitId
        );
    }

    public static function system(string $title, string $message): void
    {
        self::create('system', $title, $message);
    }

    // Which notification types a given role is allowed to see.
    // feedback/complaint are admin-only — staff has no Feedback page to
    // send those clicks to, and shouldn't see them in the list either.
    public static function visibleTypesFor(?string $role): array
    {
        $all = ['chat_inquiry', 'appointment', 'feedback', 'complaint', 'system'];

        if ($role === 'admin') {
            return $all;
        }

        return array_values(array_diff($all, ['feedback', 'complaint']));
    }
}