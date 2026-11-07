<?php

namespace App\Services;

use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use Illuminate\Support\Facades\Log;

/**
 * Para makita ni Admin/Staff ang buong usapan sa isang bukas na inquiry, kasama
 * ang mga bagay na ang chatbot mismo ang sumagot: ang "Thank you" ng patient at
 * ang "You're welcome" ng bot, ang mga greeting, at ang mga sagot sa clinic info.
 * Sa ganitong paraan alam ni Admin/Staff kung kailan solved na ang inquiry.
 *
 * - Ang mensahe ng patient ay naka-save bilang sender "Patient".
 * - Ang sagot ng bot ay naka-save bilang sender "Chatbot" (is_staff = true para
 *   lumabas sa panig ng sumasagot sa Admin/Staff view). HINDI ito ipinapakita
 *   ulit sa widget ng patient: nasa chat na niya ang sagot ng bot, kaya
 *   hinahanap ng publicUpdates() ang sender na ito at inaalis.
 * - Walang bukas na inquiry = walang isinusulat dito (hindi ito bagong inquiry).
 */
class InquiryThread
{
    public const BOT_SENDER = 'Chatbot';

    /**
     * Isinusulat ang usapan (patient + sagot ng bot) sa bukas na inquiry ng chat.
     * True kung may bukas na inquiry at naisulat ito.
     */
    public static function recordBotExchange(string $conversationId, string $patientText, string $botText): bool
    {
        $patientText = trim($patientText);
        $botText = self::describeBotMessage($botText);

        if ($conversationId === '' || $patientText === '' || $botText === '') {
            return false;
        }

        try {
            $inquiryId = self::openInquiryId($conversationId);
            if ($inquiryId === null) {
                return false;
            }

            InquiryReply::create([
                'inquiry_id' => $inquiryId,
                'sender'     => 'Patient',
                'is_staff'   => false,
                'message'    => $patientText,
            ]);

            InquiryReply::create([
                'inquiry_id' => $inquiryId,
                'sender'     => self::BOT_SENDER,
                'is_staff'   => true,
                'message'    => $botText,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to record chatbot exchange in inquiry thread: ' . $e->getMessage());
            return false;
        }
    }

    // Ang bukas pa (hindi Resolved) na inquiry ng chat na ito, kung meron.
    public static function openInquiryId(string $conversationId): ?int
    {
        $id = Inquiry::where('conversation_id', $conversationId)
            ->where('resolved_status', '!=', 'Resolved')
            ->orderByDesc('inquiry_id')
            ->value('inquiry_id');

        return $id !== null ? (int) $id : null;
    }

    // Mga card (INFO_CARD::{json}) ay ginagawang maikling paglalarawan para mabasa ng staff.
    public static function describeBotMessage(string $text): string
    {
        $text = trim($text);

        if (str_starts_with($text, 'INFO_CARD::')) {
            return '[Sent: Clinic Information Card]';
        }

        return $text;
    }
}