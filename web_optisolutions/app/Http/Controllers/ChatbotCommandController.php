<?php

namespace App\Http\Controllers;

use App\Models\ChatbotCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Admin-side CRUD for the chatbot's manageable quick-reply commands
 * (the "things the chatbot can do" outside of the 4 hardcoded flows:
 * schedule visit, general information, submit complaint, submit
 * review/rating). Add/edit/delete a row here and BotManController
 * will pick it up on the next chat message — no deploy needed.
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
        $commands = ChatbotCommand::orderBy('sort_order')
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
        $validated = $this->validateCommand($request);

        ChatbotCommand::create($validated);

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
        return view('admin_acc.chatbot_commands.edit', ['command' => $chatbot_command]);
    }

    public function update(Request $request, ChatbotCommand $chatbot_command)
    {
        $validated = $this->validateCommand($request, $chatbot_command->command_id);

        $chatbot_command->update($validated);

        return redirect()
            ->route('admin_acc.chatbot_commands.index')
            ->with('chatbot_success', 'Chatbot command updated.');
    }

    public function destroy(ChatbotCommand $chatbot_command)
    {
        $chatbot_command->delete();

        return redirect()
            ->route('admin_acc.chatbot_commands.index')
            ->with('chatbot_success', 'Chatbot command deleted.');
    }

    protected function validateCommand(Request $request, ?int $ignoreId = null): array
    {
        $triggerValue = ChatbotCommand::normalizeTrigger((string) $request->input('trigger_value', ''));

        $validator = Validator::make(
            array_merge($request->all(), ['trigger_value' => $triggerValue]),
            [
                'label'         => ['required', 'string', 'max:100'],
                'trigger_value' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:chatbot_commands,trigger_value' . ($ignoreId ? ",{$ignoreId},command_id" : ''),
                ],
                'reply_text'    => ['required', 'string'],
                'show_in_menu'  => ['sometimes', 'boolean'],
                'is_active'     => ['sometimes', 'boolean'],
                'sort_order'    => ['nullable', 'integer', 'min:0'],
            ],
            [
                'trigger_value.unique' => 'That trigger word/phrase is already used by another command.',
            ]
        );

        $validator->after(function ($validator) use ($triggerValue) {
            if (in_array($triggerValue, ChatbotCommand::RESERVED_TRIGGERS, true)) {
                $validator->errors()->add(
                    'trigger_value',
                    'That phrase is reserved for a built-in chatbot flow and can\'t be reused here.'
                );
            }
        });

        $validated = $validator->validate();

        $validated['trigger_value'] = $triggerValue;
        $validated['show_in_menu']  = $request->boolean('show_in_menu');
        $validated['is_active']     = $request->boolean('is_active');
        $validated['sort_order']    = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
