<?php

namespace App\Conversations\Concerns;

use BotMan\BotMan\Messages\Incoming\Answer;
use App\Services\ClinicInfoService;
use App\Conversations\InquiryConversation;
use Closure;

/**
 * Shared off-topic detection for conversations that show a button-driven
 * menu and want stray free-typed questions to still reach staff via
 * InquiryConversation, instead of just getting a repeated
 * "please choose one of the options above."
 *
 * Only wire this into steps where free text ISN'T the expected answer
 * (e.g. a button menu) — not into steps where free text is the actual
 * content you want (e.g. typing the complaint itself), or a genuine
 * complaint/comment that happens to contain a question mark would get
 * misrouted.
 *
 * AppointmentConversation keeps its own, richer version of this (it also
 * stashes/resumes in-progress schedule-visit state) — this trait is for
 * Complaint, Review, and similar single-purpose conversations.
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
     * @param Answer  $answer
     * @param Closure $resumeCurrentStep Re-shows the current step after an
     *                acknowledgment or an answered info-request.
     * @return bool   True if the answer was off-topic and has been handled.
     */
    protected function handleOffTopicIfAny(Answer $answer, Closure $resumeCurrentStep): bool
    {
        if ($this->looksLikeAcknowledgment($answer)) {
            $this->say(ClinicInfoService::acknowledgmentReply());
            $resumeCurrentStep();
            return true;
        }

        if ($this->hasAttachment($answer)) {
            $this->routeToInquiry($answer);
            return true;
        }

        if (!$this->looksLikeInquiry($answer)) {
            return false;
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
        $this->say("Got it — let me send that to our staff so a real person can take a look.");
        $this->bot->startConversation(new InquiryConversation($answer->getText()));
    }
}