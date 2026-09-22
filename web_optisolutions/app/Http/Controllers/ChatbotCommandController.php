<?php

namespace App\Http\Controllers;

use App\Models\ChatbotCommand;
use App\Models\ChatbotCommandTrigger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin-side CRUD for the chatbot's manageable quick-reply commands
 * (the "things the chatbot can do" outside of the 4 hardcoded flows:
 * schedule visit, general information, submit complaint, submit
 * review/rating). Add/edit/delete a row here and BotManController
 * will pick it up on the next chat message — no deploy needed.
 *
 * Each command can now have MULTIPLE trigger words/phrases, stored in
 * chatbot_command_triggers (one row per trigger, unique across the
 * whole table so two commands can't claim the same trigger).
 *
 * NOTE: adjust the namespace/route middleware group below to match
 * however your other admin_acc controllers are organized (e.g. if you
 * keep admin controllers under App\Http\Controllers\Admin, move this
 * file there and update the `namespace` line + routes/admin_acc/web.php
 * accordingly).
 */
class ChatbotCommandController extends Controller
{
    public function index()
    {
        $commands = ChatbotCommand::with('triggers')
            ->orderBy('label')
            ->get();

        return view('admin_acc.chatbot_commands.index', compact('commands'));
    }

    public function create()
    {
        return view('admin_acc.chatbot_commands.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateCommand($request);

        $command = ChatbotCommand::create($data['command']);

        $command->triggers()->createMany(
            collect($data['triggers'])
                ->map(fn ($value) => ['trigger_value' => $value])
                ->all()
        );

        return redirect()
            ->route('admin_acc.chatbot_commands.index')
            ->with('chatbot_success', 'Chatbot command added.');
    }

    // NOTE: param is named $chatbot_command (snake_case), not $chatbotCommand —
    // Route::resource('chatbot-commands', ...) generates the route wildcard as
    // {chatbot_command}, and implicit model binding matches by exact parameter
    // name, so this has to line up or the model won't resolve.
    public function edit(ChatbotCommand $chatbot_command)
    {
        return view('admin_acc.chatbot_commands.edit', ['command' => $chatbot_command->load('triggers')]);
    }

    public function update(Request $request, ChatbotCommand $chatbot_command)
    {
        $data = $this->validateCommand($request, $chatbot_command->command_id);

        $chatbot_command->update($data['command']);

        // Sync triggers: drop any no longer submitted, add any new ones,
        // leave unchanged ones alone so trigger_id / created_at survive.
        $incoming = collect($data['triggers']);

        $chatbot_command->triggers()
            ->whereNotIn('trigger_value', $incoming)
            ->delete();

        $existing = $chatbot_command->triggers()->pluck('trigger_value');

        $incoming->diff($existing)->each(
            fn ($value) => $chatbot_command->triggers()->create(['trigger_value' => $value])
        );

        return redirect()
            ->route('admin_acc.chatbot_commands.index')
            ->with('chatbot_success', 'Chatbot command updated.');
    }

    public function destroy(ChatbotCommand $chatbot_command)
    {
        $chatbot_command->delete(); // triggers cascade-delete via the FK

        return redirect()
            ->route('admin_acc.chatbot_commands.index')
            ->with('chatbot_success', 'Chatbot command deleted.');
    }

    /**
     * @return array{command: array, triggers: array<int, string>}
     */
    protected function validateCommand(Request $request, ?int $ignoreCommandId = null): array
    {
        $triggers = collect((array) $request->input('trigger_values', []))
            ->map(fn ($value) => ChatbotCommand::normalizeTrigger((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();

        $validator = Validator::make(
            array_merge($request->all(), ['trigger_values' => $triggers]),
            [
                'label'            => ['required', 'string', 'max:100'],
                'trigger_values'   => ['required', 'array', 'min:1'],
                'trigger_values.*' => ['string', 'max:100'],
                'reply_text'       => ['required', 'string'],
                'is_active'        => ['sometimes', 'boolean'],
                
            ]
        );

        $validator->after(function ($validator) use ($triggers, $ignoreCommandId) {
            foreach ($triggers as $trigger) {
                if (in_array($trigger, ChatbotCommand::RESERVED_TRIGGERS, true)) {
                    $validator->errors()->add(
                        'trigger_values',
                        "\"{$trigger}\" is reserved for a built-in chatbot flow and can't be reused here."
                    );
                    continue;
                }

                $ownerCommandId = ChatbotCommandTrigger::where('trigger_value', $trigger)
                    ->value('command_id');

                if ($ownerCommandId !== null && $ownerCommandId !== $ignoreCommandId) {
                    $validator->errors()->add(
                        'trigger_values',
                        "\"{$trigger}\" is already used by another command."
                    );
                }
            }
        });

        $validated = $validator->validate();

        return [
            'command' => [
                'label'        => $validated['label'],
                'reply_text'   => $validated['reply_text'],
                'is_active'    => $request->boolean('is_active'),
            
            ],
            'triggers' => $triggers,
        ];
    }
}