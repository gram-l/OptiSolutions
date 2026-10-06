<?php

namespace App\Services;

use App\Mail\ChatbotInquiryAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InquiryEmailService
{
    public static function send(string $title, string $message, ?int $inquiryId): void
    {
        try {
            $recipients = DB::table('users')
                ->whereRaw('LOWER(TRIM(status)) = ?', ['active'])
                ->whereIn(DB::raw('LOWER(TRIM(user_role))'), ['admin', 'staff'])
                ->get(['user_id', 'name', 'email']);

            $sent = [];
            foreach ($recipients as $recipient) {
                $email = trim($recipient->email ?? '');
                $key = strtolower($email);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($sent[$key])) {
                    continue;
                }

                try {
                    Mail::to($email, $recipient->name)->send(new ChatbotInquiryAlert(
                        $title, $message, $inquiryId,
                        rtrim(config('app.url'), '/') . '/auth/login',
                    ));
                    $sent[$key] = true;
                } catch (\Throwable $exception) {
                    Log::error('Chatbot inquiry email delivery failed.', [
                        'inquiry_id' => $inquiryId,
                        'user_id' => $recipient->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $exception) {
            // An email failure must not interfere with saving the inquiry or its notification.
            Log::error('Could not prepare chatbot inquiry emails.', [
                'inquiry_id' => $inquiryId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
