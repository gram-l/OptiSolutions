<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <title>OptiSolutions - Chatbot Commands</title>

    {{--
        patients.css and user_management.css are both included explicitly
        here because this page (like patients.blade.php / appointments.blade.php)
        is a standalone page, not extending admin_acc.layout — so nothing
        pulls in their shared classes automatically. patients.css defines
        the :root CSS variables (--primary-main, --white, --shadow, etc.)
        plus .container, .page-header, .filter-bar, .add-patient-btn,
        .status-badge, .modal, .form-group, .btn-save/.btn-cancel.
        user_management.css adds .btn-icon/.btn-edit, .action-buttons,
        .empty-state on top of that. chatbot_commands.css intentionally
        doesn't redefine any of those, so both includes are required,
        not optional — and patients.css must load first so its variables
        are available to everything after it.
    --}}
    @vite(['resources/css/admin_css/patients.css', 'resources/css/admin_css/chatbot_commands.css', 'resources/css/admin_css/user_management.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])

    {{--
        Trigger tag-input styling — not yet in chatbot_commands.css, so
        it's inline here for now. Move these rules into that file whenever
        convenient; nothing else on the page depends on them staying inline.
    --}}
    <style>
        .trigger-tag-box {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            align-items: center;
            border: 1px solid var(--shadow, #ccc);
            border-radius: 6px;
            padding: 0.5rem;
            min-height: 2.6rem;
        }
        .trigger-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: var(--primary-main, #2c7be5);
            color: #fff;
            border-radius: 999px;
            padding: 0.15rem 0.3rem 0.15rem 0.7rem;
            font-size: 0.8rem;
            line-height: 1.6;
        }
        .trigger-tag button {
            background: transparent;
            border: none;
            color: #fff;
            cursor: pointer;
            font-size: 0.9rem;
            line-height: 1;
            padding: 0 0.3rem;
        }
        #triggerInput {
            flex: 1;
            min-width: 140px;
            border: none;
            outline: none;
            font-size: 0.85rem;
            padding: 0.2rem;
        }
    </style>
</head>
<body>
    @include('admin_acc.header')

    <div class="container">
        @include('admin_acc.sidebar')
        <div style="flex: 1; min-width: 0;">
            <div class="page-header">
                <h2><i class="fa-solid fa-robot"></i> Chatbot Commands</h2>
                <p>Manage the extra quick replies the chatbot can answer with — FAQs like "parking",
                   "insurance", "walk-ins", etc.</p>
            </div>

            @if (session('success'))
                <div class="alert-success">{{ session('success') }}</div>
            @endif

            <div class="toolbar">
                <strong>{{ $commands->count() }} command(s)</strong>
                <button type="button" class="add-patient-btn" onclick="openCommandModal()">
                    <i class="fa-solid fa-plus"></i> Add Command
                </button>
            </div>

            <div class="commands-table-container">
                <table class="commands-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Triggers</th>
                            <th>Reply</th>
                            
                            <th>Status</th>
                            
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($commands as $command)
                            <tr>
                                <td>{{ $command->label }}</td>
                                <td>
                                    @foreach ($command->triggers as $trigger)
                                        <code>{{ $trigger->trigger_value }}</code>@if (!$loop->last), @endif
                                    @endforeach
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit($command->reply_text, 60) }}</td>
                               
                                <td>
                                    <span class="status-badge {{ $command->is_active ? 'status-active' : 'status-inactive' }}">
                                        {{ $command->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                               
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-icon btn-edit"
                                                onclick='openCommandModal(@json($command))'>Edit</button>

                                        <button type="button" class="btn-icon btn-delete"
                                                onclick="openDeleteModal({{ $command->command_id }}, {{ json_encode($command->label) }})">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state">No chatbot commands yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add/Edit Command Modal --}}
    <div class="modal" id="commandModal">
        <div class="modal-content">
            <h3 id="commandModalTitle">Add Command</h3>
            <form id="commandForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="commandMethod" value="POST">

                <div class="form-group">
                    <label for="label">Button/label text</label>
                    <input type="text" id="label" name="label" value="{{ old('label') }}" maxlength="100" required>
                    <small class="form-hint">What the patient sees, e.g. "Parking Info".</small>
                    @error('label') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="triggerInput">Trigger words/phrases</label>
                    <div class="trigger-tag-box" id="triggerTagBox">
                        <input type="text" id="triggerInput" placeholder="Type a trigger, press Enter">
                    </div>
                    <small class="form-hint">
                        Add one or more words/phrases the bot matches on (a menu button click sends
                        its exact value; typed messages are matched against ALL of a command's
                        triggers). Press Enter or "," after each one. Stored lowercase automatically.
                    </small>
                    @error('trigger_values') <span class="form-error">{{ $message }}</span> @enderror
                    <div id="triggerHiddenInputs"></div>
                </div>

                <div class="form-group">
                    <label for="reply_text">Reply</label>
                    <textarea id="reply_text" name="reply_text" rows="4" required>{{ old('reply_text') }}</textarea>
                    @error('reply_text') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label for="is_active">Active</label>
                </div>


                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeCommandModal()">Cancel</button>
                    <button type="submit" class="btn-save" id="commandSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width:400px;">
            <h3>Delete Command?</h3>
            <p style="font-size:0.85rem; color:#7f8c8d; margin-bottom:1.2rem;">
                Are you sure you want to delete "<strong id="deleteCommandLabel"></strong>"? This can't be undone.
            </p>
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" class="btn-icon" style="background:var(--danger); color:#fff;">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add/Edit Command modal
        const commandModal = document.getElementById('commandModal');
        const commandForm  = document.getElementById('commandForm');

        // Placeholder-based URL templates — avoids hardcoding the route path here.
        const commandUpdateUrlTemplate = "{{ route('admin_acc.chatbot_commands.update', ['chatbot_command' => 'CMD_ID']) }}";
        const commandStoreUrl = "{{ route('admin_acc.chatbot_commands.store') }}";

        // --- Trigger tag input -------------------------------------------------
        const triggerTagBox = document.getElementById('triggerTagBox');
        const triggerInput = document.getElementById('triggerInput');
        const triggerHiddenInputs = document.getElementById('triggerHiddenInputs');
        let triggerValues = [];

        function renderTriggerTags() {
            triggerTagBox.querySelectorAll('.trigger-tag').forEach(el => el.remove());
            triggerHiddenInputs.innerHTML = '';

            triggerValues.forEach((value, index) => {
                const tag = document.createElement('span');
                tag.className = 'trigger-tag';
                tag.innerHTML = `${value} <button type="button" data-index="${index}" aria-label="Remove">&times;</button>`;
                triggerTagBox.insertBefore(tag, triggerInput);

                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'trigger_values[]';
                hidden.value = value;
                triggerHiddenInputs.appendChild(hidden);
            });
        }

        function addTriggerValue(raw) {
            const value = raw.trim().toLowerCase().replace(/,+$/, '');
            triggerInput.value = '';
            if (!value || triggerValues.includes(value)) return;
            triggerValues.push(value);
            renderTriggerTags();
        }

        triggerInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                addTriggerValue(triggerInput.value);
            } else if (e.key === 'Backspace' && !triggerInput.value && triggerValues.length) {
                triggerValues.pop();
                renderTriggerTags();
            }
        });

        triggerInput.addEventListener('blur', () => addTriggerValue(triggerInput.value));

        triggerTagBox.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-index]');
            if (!btn) return;
            triggerValues.splice(parseInt(btn.dataset.index, 10), 1);
            renderTriggerTags();
        });
        // -------------------------------------------------------------------

        function openCommandModal(command = null) {
            commandForm.reset();
            triggerValues = [];

            if (command) {
                document.getElementById('commandModalTitle').textContent = 'Edit Command';
                document.getElementById('label').value = command.label;
                triggerValues = (command.triggers || []).map(t => t.trigger_value);
                document.getElementById('reply_text').value = command.reply_text;
                
                document.getElementById('is_active').checked = !!command.is_active;

                document.getElementById('commandMethod').value = 'PUT';
                document.getElementById('commandSubmitBtn').textContent = 'Update';
                commandForm.action = commandUpdateUrlTemplate.replace('CMD_ID', command.command_id);
            } else {
                document.getElementById('commandModalTitle').textContent = 'Add Command';
                document.getElementById('commandMethod').value = 'POST';
                document.getElementById('commandSubmitBtn').textContent = 'Save';
                document.getElementById('is_active').checked = true;
                commandForm.action = commandStoreUrl;
            }

            renderTriggerTags();
            commandModal.style.display = 'flex';
        }

        function closeCommandModal() {
            commandModal.style.display = 'none';
        }

        commandModal.addEventListener('click', (e) => {
            if (e.target === commandModal) closeCommandModal();
        });

        // Delete confirmation modal
        const deleteModal = document.getElementById('deleteModal');
        const deleteForm  = document.getElementById('deleteForm');
        const deleteUrlTemplate = "{{ route('admin_acc.chatbot_commands.destroy', ['chatbot_command' => 'CMD_ID']) }}";

        function openDeleteModal(id, label) {
            document.getElementById('deleteCommandLabel').textContent = label;
            deleteForm.action = deleteUrlTemplate.replace('CMD_ID', id);
            deleteModal.style.display = 'flex';
        }

        function closeDeleteModal() {
            deleteModal.style.display = 'none';
        }

        deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) closeDeleteModal();
        });

        // If validation failed, reopen the Add/Edit modal with the old input already in place
        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', () => {
                @if (old('_method') === 'PUT')
                    document.getElementById('commandModalTitle').textContent = 'Edit Command';
                    document.getElementById('commandMethod').value = 'PUT';
                    document.getElementById('commandSubmitBtn').textContent = 'Update';
                    commandForm.action = commandUpdateUrlTemplate.replace('CMD_ID', '{{ request()->route('chatbot_command') }}');
                @else
                    commandForm.action = commandStoreUrl;
                @endif
                triggerValues = @json(old('trigger_values', []));
                renderTriggerTags();
                commandModal.style.display = 'flex';
            });
        @endif
    </script>
</body>
</html>