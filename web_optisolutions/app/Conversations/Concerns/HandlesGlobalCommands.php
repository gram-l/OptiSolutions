<?php

namespace App\Conversations\Concerns;

use BotMan\BotMan\Messages\Incoming\Answer;
use App\Services\ClinicInfoService;
use App\Conversations\AppointmentConversation;
use App\Conversations\ReviewConversation;
use App\Conversations\ComplaintConversation;
use Closure;

/**
 * Shared "global command" handling for conversations that don't need to
 * preserve multi-step, in-progress state the way AppointmentConversation
 * does (Complaint, Review, Inquiry). Typing/clicking any of these values
 * at ANY step of these conversations immediately switches conversations
 * (or, if you're already in the target conversation, just resumes the
 * current step instead of restarting it).
 *
 * AppointmentConversation keeps its own version of this because it needs
 * to stash/resume schedule-visit progress and log to a transcript.
 */
trait HandlesGlobalCommands
{
    protected function normalizeCommand(Answer $answer): string
    {
        return strtolower(trim($answer->getValue() ?: $answer->getText()));
    }

    protected function isGlobalCommand(Answer $answer): bool
    {
        return in_array($this->normalizeCommand($answer), [
            'schedule visit',
            'general information',
            'review', 'submit review/rating',
            'complaint', 'submit complaint',
            'cancel', 'menu',
        ], true);
    }

    /**
     * @param Answer  $answer
     * @param Closure $resumeCurrentStep Re-shows the current step (used
     *                after a "general information" side-trip, or when the
     *                command targets the conversation we're already in).
     */
    protected function handleGlobalCommand(Answer $answer, Closure $resumeCurrentStep)
    {
        $cmd = $this->normalizeCommand($answer);

        if ($cmd === 'schedule visit') {
            $this->bot->startConversation(new AppointmentConversation());
            return;
        }

        if ($cmd === 'general information') {
            $this->say(ClinicInfoService::infoCardMessage());
            $resumeCurrentStep();
            return;
        }

        if (in_array($cmd, ['review', 'submit review/rating'], true)) {
            if ($this instanceof ReviewConversation) {
                $resumeCurrentStep();
                return;
            }
            $this->bot->startConversation(new ReviewConversation(
                $this->patientId ?? null,
                $this->patientName ?? null
            ));
            return;
        }

        if (in_array($cmd, ['complaint', 'submit complaint'], true)) {
            if ($this instanceof ComplaintConversation) {
                $resumeCurrentStep();
                return;
            }
            $this->bot->startConversation(new ComplaintConversation(
                $this->patientId ?? null,
                $this->patientName ?? null
            ));
            return;
        }

        if (in_array($cmd, ['cancel', 'menu'], true)) {
            $this->say('Okay, cancelled. Type "Menu" anytime to start again.');
            return;
        }
    }
}