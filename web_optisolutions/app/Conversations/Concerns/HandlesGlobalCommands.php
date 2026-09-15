<?php

namespace App\Conversations\Concerns;

use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Services\ClinicInfoService;
use App\Conversations\AppointmentConversation;
use App\Conversations\ReviewConversation;
use App\Conversations\ComplaintConversation;
use Closure;

// Shared "global command" handling for conversations that don't need to
// preserve multi-step, in-progress state the way AppointmentConversation
// does (Complaint, Review, Inquiry). Typing/clicking any of these values
// at any step of these conversations immediately switches conversations
// (or, if you're already in the target conversation, just resumes the
// current step instead of restarting it).
//
// AppointmentConversation keeps its own version of this because it needs
// to stash/resume schedule-visit progress and log to a transcript.
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

    // $answer: the incoming global command.
    // $resumeCurrentStep: re-shows the current step, used when the
    // command targets the conversation we're already in.
    protected function handleGlobalCommand(Answer $answer, Closure $resumeCurrentStep)
    {
        $cmd = $this->normalizeCommand($answer);

        if ($cmd === 'schedule visit') {
            $this->bot->startConversation(new AppointmentConversation());
            return;
        }

        if ($cmd === 'general information') {
            // Show the info, then re-show whatever step we were on so
            // the buttons don't vanish. When this fires from the
            // post-completion main menu (backToMainMenu()), that step
            // IS the 4-button menu, so it comes right back. Mid-flow
            // (e.g. while typing a complaint), it re-asks that same
            // question instead — a side-trip to general info doesn't
            // wipe out the flow that was in progress.
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

        if ($cmd === 'cancel') {
            $this->say('Okay, cancelled. Type "Menu" anytime to start again.');
            return;
        }

        if ($cmd === 'menu') {
            $this->sendMenu();
            return;
        }
    }

    // Shows the main 4-button menu and routes whatever the patient picks.
    protected function sendMenu()
    {
        $question = Question::create('Hello! Welcome to PolyClinic Lipa. How can I help you today?')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('submit review/rating'),
                Button::create('Submit Complaint')->value('submit complaint'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->sendMenu());
            }

            $this->say('Please choose one of the options above.');
            $this->sendMenu();
        });
    }
}