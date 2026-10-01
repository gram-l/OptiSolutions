<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use App\Conversations\Concerns\HandlesGlobalCommands;
use App\Conversations\Concerns\HandlesOffTopic;

/**
 * "Is there anything else you need?"  [Yes] [No]
 *
 * Shown at the end of every flow (review, complaint, schedule visit,
 * general information, resolved inquiry) so the patient can either go
 * back to the main menu (Yes) or cleanly end the chat (No).
 *
 * Start it with:
 *   $this->bot->startConversation(new AnythingElseConversation());
 */
class AnythingElseConversation extends Conversation
{
    use HandlesGlobalCommands, HandlesOffTopic;

    public const YES = 'anything_else_yes';
    public const NO  = 'anything_else_no';

    // Shown when the patient taps/types "No, I'm all set".
    public const THANK_YOU_TEXT = 'Thank you for scheduling your visit with us.';

    // Free-typed equivalents, so typing "yes" / "no" / "wala na" works
    // just as well as tapping the buttons.
    protected const YES_WORDS = ['yes', 'yep', 'yeah', 'oo', 'opo', 'meron', 'mayroon', 'yes, i need help'];
    protected const NO_WORDS  = ['no', 'nope', 'nah', 'wala', 'wala na', 'hindi', 'okay na', "no, i'm all set", 'no thanks', 'no thank you'];

    protected $patientId;
    protected $patientName;

    // When true, skip the Yes/No question and go straight to
    // "Sure! What would you like to do?" + the menu. Used when the
    // patient taps "Yes, I need help" on the button the widget shows
    // after Admin/Staff resolves an inquiry (no question is pending on
    // the server at that point).
    protected $startAtMenu = false;

    // The single line said right before the menu when $startAtMenu is true.
    protected $menuIntro = 'Sure! What would you like to do?';

    // Line said after "No, I'm all set", before the 4 options come back.
    // null = say nothing, just end and show the 4 options (Complaint,
    // Review, etc.). Only the Schedule Visit flow passes THANK_YOU_TEXT.
    protected $thankYouText = null;

    public function __construct($patientId = null, $patientName = null, bool $startAtMenu = false, ?string $menuIntro = null, ?string $thankYouText = null)
    {
        $this->thankYouText = $thankYouText;
        $this->patientId = $patientId;
        $this->patientName = $patientName;
        $this->startAtMenu = $startAtMenu;
        if ($menuIntro !== null) {
            $this->menuIntro = $menuIntro;
        }
    }

    public function run()
    {
        $stored = $this->bot->userStorage()->find() ?: [];
        $this->patientId   = $this->patientId   ?? ($stored['patient_id'] ?? null);
        $this->patientName = $this->patientName ?? ($stored['patient_name'] ?? null);

        if ($this->startAtMenu) {
            // An empty intro = no extra line, just the 4 options.
            if ($this->menuIntro !== '') {
                $this->say($this->menuIntro);
            }
            return $this->showMenu();
        }

        $this->askAnythingElse();
    }

    public function askAnythingElse(string $prompt = 'Is there anything else you need?')
    {
        $question = Question::create($prompt)
            ->fallback('Please tap Yes or No.')
            ->addButtons([
                Button::create('Yes, I need help')->value(self::YES),
                Button::create("No, I'm all set")->value(self::NO),
            ]);

        $this->ask($question, function (Answer $answer) {
            $choice = $this->normalizeCommand($answer);

            if ($choice === self::YES || in_array($choice, self::YES_WORDS, true)) {
                $this->say('Sure! What would you like to do?');
                return $this->showMenu();
            }

            if ($choice === self::NO || in_array($choice, self::NO_WORDS, true)) {
                return $this->endConversation();
            }

            // "General Information" typed instead of Yes/No: same as above.
            if ($choice === 'general information') {
                $this->say(\App\Services\ClinicInfoService::infoCardMessage());
                return $this->showMenu();
            }

            // "Schedule Visit", "Menu", etc. typed instead of Yes/No.
            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->askAnythingElse());
            }

            // A real question typed here still reaches staff.
            // "Pwede ba maedit ang info na nilagay ko?" etc. always goes to staff.
            if ($this->looksLikeEditRequest($answer)) {
                return $this->routeToInquiry($answer);
            }

            if ($this->handleOffTopicIfAny($answer, fn() => $this->askAnythingElse())) {
                return;
            }

            // Any other real sentence typed here is an inquiry, not a bad button choice.
            if ($this->looksLikeFreeTextMessage($answer)) {
                return $this->routeToInquiry($answer);
            }

            $this->say('Please tap Yes or No below.');
            $this->askAnythingElse();
        });
    }

    // Main 4-button menu. No greeting text — "Sure! What would you like
    // to do?" was just said. General Information here shows the info card
    // and then shows this same menu again.
    protected function showMenu()
    {
        $question = Question::create('')
            ->fallback('Please choose an option from the buttons above.')
            ->addButtons([
                Button::create('Schedule Visit')->value('schedule visit'),
                Button::create('General Information')->value('general information'),
                Button::create('Submit Review/Rating')->value('submit review/rating'),
                Button::create('Submit Complaint')->value('submit complaint'),
            ]);

        $this->ask($question, function (Answer $answer) {
            if ($this->normalizeCommand($answer) === 'general information') {
                // Info sent -> done. Bring the 4 main options back (no
                // "anything else?" question).
                $this->say(\App\Services\ClinicInfoService::infoCardMessage());
                return $this->showMenu();
            }

            if ($this->isGlobalCommand($answer)) {
                return $this->handleGlobalCommand($answer, fn() => $this->showMenu());
            }

            // Anything the patient TYPES here (instead of tapping a button)
            // is treated as an inquiry and forwarded to Admin/Staff, e.g.
            // "Can I resched my appointment?" or "Do you accept HMO?".
            // Only bare greetings / thank-yous are answered by the bot
            // itself, since those aren't real inquiries.
            if ($this->looksLikeFreeTextMessage($answer)) {
                $typed = trim($answer->getText());

                if (\App\Services\ClinicInfoService::looksLikeAcknowledgment($typed)) {
                    $this->say(\App\Services\ClinicInfoService::acknowledgmentReply());
                    return $this->showMenu();
                }

                if (\App\Services\ClinicInfoService::looksLikeGreeting($typed)) {
                    $this->say(\App\Services\ClinicInfoService::greetingReply());
                    return $this->showMenu();
                }

                return $this->routeToInquiry($answer);
            }

            $this->say('Please choose one of the options above.');
            $this->showMenu();
        });
    }

    // True when the patient typed (not tapped) anything at all.
    protected function looksLikeFreeTextMessage(Answer $answer): bool
    {
        if (method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply()) {
            return false;
        }

        $text = trim($answer->getText());

        return $text !== '';
    }

    // Requests to edit / reschedule / cancel something already submitted
    // must reach staff, even if the text also contains a keyword like
    // "info" or "schedule".
    protected function looksLikeEditRequest(Answer $answer): bool
    {
        if (method_exists($answer, 'isInteractiveMessageReply') && $answer->isInteractiveMessageReply()) {
            return false;
        }

        return \App\Services\ClinicInfoService::looksLikeChangeRequest($answer->getText());
    }

    protected function endConversation()
    {
        // End of the flow: optional thank-you, then the 4 main options.
        if ($this->thankYouText !== null && $this->thankYouText !== '') {
            $this->say($this->thankYouText);
        }
        $this->showMenu();
        // No further ask() → the conversation ends here.
    }
}