<?php

namespace App\Conversations\Concerns;

use BotMan\BotMan\Messages\Incoming\Answer;
use App\Services\ClinicInfoService;
use App\Conversations\InquiryConversation;
use Closure;

/**
 * Shared off-topic detection: routes stray free-typed questions to staff
 * on button-driven steps. Don't use on steps where free text is the
 * expected answer. Used by Complaint, Review, and similar conversations
 * (AppointmentConversation has its own richer version).
 */
trait HandlesOffTopic
{
    protected function hasAttachment(Answer $answer): bool
    {
        $message = $answer->getMessage();
        if (!$message) {
            return false;
        }

        foreach (['getImages', 'getFiles', 'getVideos', 'getAudio'] as $method) {
            if (method_exists($message, $method) && !empty($message->$method())) {
                return true;
            }
        }

        if (method_exists($message, 'getExtras')) {
            $extras = $message->getExtras() ?? [];
            foreach (['attachment', 'attachments', 'image', 'images', 'file', 'files'] as $key) {
                if (!empty($extras[$key])) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function looksLikeInquiry(Answer $answer): bool
    {
        if (method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply()) {
            return false;
        }

        $text = trim($answer->getText());
        if ($text === '') {
            return false;
        }

        if (str_ends_with($text, '?')) {
            return true;
        }

        $questionWords = [
            'ano', 'anong', 'bakit', 'paano', 'paanong', 'saan', 'saang', 'nasaan',
            'kailan', 'sino', 'sinong', 'magkano', 'ilan', 'ilang', 'alin', 'alinng',
            'pwede', 'puwede', 'meron', 'mayroon',
            'what', 'why', 'how', 'when', 'where', 'who', 'which', 'can', 'could', 'is', 'does',
        ];
        $words = preg_split('/\s+/', $text);
        $firstWord = strtolower(rtrim($words[0] ?? '', '?.,!'));

        return in_array($firstWord, $questionWords, true);
    }

    protected function looksLikeInfoRequest(string $text): bool
    {
        return ClinicInfoService::looksLikeInfoRequest($text);
    }

    protected function looksLikeAcknowledgment(Answer $answer): bool
    {
        if (method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply()) {
            return false;
        }

        $text = trim($answer->getText());
        if ($text === '') {
            return false;
        }

        return ClinicInfoService::looksLikeAcknowledgment($text);
    }

    /**
     * Handles acknowledgments, greetings, info requests, and inquiries.
     * Returns true if the answer was off-topic and has been handled.
     */
    protected function handleOffTopicIfAny(Answer $answer, Closure $resumeCurrentStep): bool
    {
        if ($this->looksLikeAcknowledgment($answer)) {
            $this->say(ClinicInfoService::acknowledgmentReply());
            $resumeCurrentStep();
            return true;
        }

        $isButtonTap = method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply();
        if (!$isButtonTap) {
            $freeText = trim($answer->getText());

            if (ClinicInfoService::looksLikeGreeting($freeText)) {
                $this->say(ClinicInfoService::greetingReply());
                $resumeCurrentStep();
                return true;
            }

            if (ClinicInfoService::looksLikeAskingPermission($freeText)) {
                $this->say(ClinicInfoService::askingPermissionReply());
                $resumeCurrentStep();
                return true;
            }
        }

        if ($this->hasAttachment($answer)) {
            $this->routeToInquiry($answer);
            return true;
        }

        if (!$this->looksLikeInquiry($answer)) {
            // Anything else typed (not a button tap, not a bare 1-5 rating)
            // is something the bot can't answer, so it goes to staff too.
            $typed = trim($answer->getText());
            if ($isButtonTap || $typed === '' || preg_match('/^[1-5]$/', $typed)) {
                return false;
            }
        }

        $text = trim($answer->getText());

        if ($this->looksLikeInfoRequest($text)) {
            $this->say(ClinicInfoService::answerForText($text) ?? ClinicInfoService::infoCardMessage());
            $resumeCurrentStep();
            return true;
        }

        $this->routeToInquiry($answer);
        return true;
    }

    protected function routeToInquiry(Answer $answer)
    {
        // No extra chatter here: InquiryConversation sends the single
        // "forwarded to Admin/Staff" bubble once the inquiry is saved.
        $this->bot->startConversation(new InquiryConversation($answer->getText()));
    }
}