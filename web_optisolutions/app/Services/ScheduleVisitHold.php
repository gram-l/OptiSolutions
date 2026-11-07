<?php

namespace App\Services;

use App\Models\Staff\Inquiry;
use App\Models\Staff\InquiryReply;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Facades\Cache;

/**
 * "Pause" ng Schedule Visit habang may inquiry ang patient.
 *
 * Kapag nag-chat ang patient habang nasa Schedule Visit form siya, hindi na
 * agad ibinabalik ang form. Sa halip, may "hold" na naka-save dito (sa cache,
 * keyed by chat conversation id) na may laman:
 *
 *   - step   : saang step siya huminto ('patient_form', 'visit_form', 'review')
 *   - state  : lahat ng nakuha na ng AppointmentConversation (pangalan, doctor, atbp.)
 *   - draft  : ang mga hindi pa na-submit na na-type niya sa form (para hindi mawala)
 *   - mode   : 'bot'   = nasagot ng bot, ang hinihintay na lang ay ang pili niya
 *              'staff' = naipasa sa Admin/Staff, hinihintay ang resolve
 *   - inquiry_id : ang inquiry na hinihintayan (mode 'staff')
 *   - held_at    : kailan nagsimula ang hold
 *
 * Ang widget ay nagpo-poll sa /api/chat/{id}/updates; ang status() dito ang
 * nagsasabi kung kailan lalabas ang Continue / Start Over / Cancel:
 *   - mode 'bot'                      -> agad
 *   - mode 'staff' + inquiry Resolved -> pag na-resolve
 *   - mode 'staff' + 10 min na walang kilos at bukas pa ang inquiry -> auto-release
 */
class ScheduleVisitHold
{
    // Mga value ng 3 buttons (pareho sa mga dati nang ginagamit ng widget/bot).
    public const CONTINUE = 'continue_schedule';
    public const RESTART  = 'restart_schedule';
    public const CANCEL   = 'cancel_schedule';

    // Control message na pinapadala ng widget pagkatapos mag-upload ng attachment
    // habang may form, para ma-pause din ang form (hindi ito nakikita ng patient).
    public const FOR_INQUIRY_TOKEN = 'hold_schedule_for_inquiry';

    // bot -> widget: "naka-hold na ang form". Hindi ito bubble; hindi rin ito
    // isinasama sa chatbot logs (tingnan ang CaptureReplyMiddleware).
    public const MARKER_PREFIX = 'HOLD::';

    public const MODE_BOT   = 'bot';
    public const MODE_STAFF = 'staff';

    public const CHOICES_QUESTION = 'What would you like to do with your schedule visit?';

    // 10 minutong walang kilos (walang bagong mensahe mula sa patient o staff) bago ang auto-release.
    public const IDLE_RELEASE_SECONDS = 600;

    // Gaano katagal itatago ang hold kung walang gagalaw (hindi dapat mas maikli sa session ng widget).
    private const TTL_HOURS = 24;

    // Mga field ng form na isinasama sa draft (whitelist; para hindi makapag-imbak ng kung ano-ano).
    private const DRAFT_FIELDS = [
        'patient' => ['full_name', 'phone', 'dob', 'email', 'send_copy'],
        'visit'   => ['service', 'doctor', 'slot'],
    ];

    private static function key(string $conversationId): string
    {
        return 'schedule_hold:' . $conversationId;
    }

    public static function get(string $conversationId): ?array
    {
        if ($conversationId === '') {
            return null;
        }

        $hold = Cache::get(self::key($conversationId));

        return is_array($hold) ? $hold : null;
    }

    public static function has(string $conversationId): bool
    {
        return self::get($conversationId) !== null;
    }

    // Sinisimulan ang hold. Pinapalitan ang anumang naunang hold ng parehong chat.
    public static function begin(
        string $conversationId,
        string $step,
        array $state,
        ?array $draft,
        string $mode,
        ?int $inquiryId = null
    ): array {
        $hold = [
            'step'       => $step,
            'state'      => $state,
            'draft'      => $draft,
            'mode'       => $mode === self::MODE_STAFF ? self::MODE_STAFF : self::MODE_BOT,
            'inquiry_id' => $inquiryId,
            'held_at'    => now()->getTimestamp(),
        ];

        if ($conversationId !== '') {
            Cache::put(self::key($conversationId), $hold, now()->addHours(self::TTL_HOURS));
        }

        return $hold;
    }

    // Ipinapasa ang hold sa staff: ito na ang inquiry na hinihintayan. Null kung walang hold.
    public static function linkInquiry(string $conversationId, int $inquiryId): ?array
    {
        $hold = self::get($conversationId);
        if ($hold === null) {
            return null;
        }

        $hold['mode'] = self::MODE_STAFF;
        $hold['inquiry_id'] = $inquiryId;
        Cache::put(self::key($conversationId), $hold, now()->addHours(self::TTL_HOURS));

        return $hold;
    }

    // Kinukuha at binubura ang hold (Continue / Start Over / Cancel).
    public static function take(string $conversationId): ?array
    {
        $hold = self::get($conversationId);
        if ($hold !== null) {
            Cache::forget(self::key($conversationId));
        }

        return $hold;
    }

    // Binubura ang hold. True kung may hold na natanggal.
    public static function forget(string $conversationId): bool
    {
        return self::take($conversationId) !== null;
    }

    // Ang bukas pa (hindi Resolved) na inquiry ng chat na ito, kung meron.
    public static function openInquiryId(string $conversationId): ?int
    {
        if ($conversationId === '') {
            return null;
        }

        $id = Inquiry::where('conversation_id', $conversationId)
            ->where('resolved_status', '!=', 'Resolved')
            ->orderByDesc('inquiry_id')
            ->value('inquiry_id');

        return $id !== null ? (int) $id : null;
    }

    // Ang 3 buttons: Continue Schedule Visit / Start Over / Cancel.
    public static function choicesQuestion(?string $text = null): Question
    {
        return Question::create($text ?? self::CHOICES_QUESTION)
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Continue Schedule Visit')->value(self::CONTINUE),
                Button::create('Start Over')->value(self::RESTART),
                Button::create('Cancel')->value(self::CANCEL),
            ]);
    }

    // Ang mensaheng nagsasabi sa widget na i-lock ang form.
    public static function marker(array $hold): string
    {
        return self::MARKER_PREFIX . json_encode([
            'mode'      => $hold['mode'] ?? self::MODE_BOT,
            'inquiryId' => $hold['inquiry_id'] ?? null,
        ]);
    }

    // Draft handling

    private static function formKind(string $step): ?string
    {
        return match ($step) {
            'name', 'phone', 'dob', 'email', 'patient_form' => 'patient',
            'service', 'doctor', 'schedule', 'visit_form'   => 'visit',
            default                                          => null,
        };
    }

    // Ang draft na kasama ng kasalukuyang request (formDraft ng widget).
    public static function draftFromRequest(string $step): ?array
    {
        return self::normalizeDraft($step, request()->input('formDraft'));
    }

    // Nililinis ang draft: mga kilalang field lang, scalar lang, may limit ang haba.
    public static function normalizeDraft(string $step, $raw): ?array
    {
        $kind = self::formKind($step);
        if ($kind === null) {
            return null;
        }

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return null;
        }

        $draft = [];
        foreach (self::DRAFT_FIELDS[$kind] as $field) {
            if (!array_key_exists($field, $raw)) {
                continue;
            }
            $value = $raw[$field];

            if ($field === 'send_copy') {
                $draft[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_scalar($value)) {
                $draft[$field] = mb_substr(trim((string) $value), 0, 300);
            }
        }

        return $draft === [] ? null : $draft;
    }

    // Status para sa widget (kasama sa /api/chat/{id}/updates)

    /**
     * @return array{active: bool, mode?: string, inquiryId?: ?int, step?: string,
     *               ready?: bool, readyReason?: ?string}
     */
    public static function status(string $conversationId): array
    {
        $hold = self::get($conversationId);
        if ($hold === null) {
            return ['active' => false];
        }

        $mode = $hold['mode'] ?? self::MODE_BOT;
        $inquiryId = $hold['inquiry_id'] ?? null;

        $status = [
            'active'      => true,
            'mode'        => $mode,
            'inquiryId'   => $inquiryId,
            'step'        => $hold['step'] ?? '',
            'ready'       => false,
            'readyReason' => null,
        ];

        // Nasagot na ng bot: ang pili na lang ng patient ang hinihintay.
        if ($mode === self::MODE_BOT) {
            $status['ready'] = true;
            $status['readyReason'] = 'bot';
            return $status;
        }

        // Naipasa sa staff.
        $inquiry = $inquiryId !== null ? Inquiry::find($inquiryId) : null;

        if ($inquiry === null || $inquiry->resolved_status === 'Resolved') {
            $status['ready'] = true;
            $status['readyReason'] = 'resolved';
            return $status;
        }

        // Auto-release: 10 minutong walang kilos habang bukas pa ang inquiry.
        // Ang "kilos" ay anumang bagong mensahe (patient o staff) sa thread, o
        // ang simula ng hold. Bagong kilos = bagong tahimik na panahon, kaya
        // isang beses lang lalabas ang buttons kada tahimik na panahon.
        $lastActivity = (int) ($hold['held_at'] ?? now()->getTimestamp());
        $lastReply = InquiryReply::where('inquiry_id', $inquiryId)->latest('created_at')->first();
        if ($lastReply && $lastReply->created_at) {
            $lastActivity = max($lastActivity, $lastReply->created_at->getTimestamp());
        }

        if (now()->getTimestamp() - $lastActivity >= self::IDLE_RELEASE_SECONDS) {
            $status['ready'] = true;
            $status['readyReason'] = 'idle';
        }

        return $status;
    }
}