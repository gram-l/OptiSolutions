<?php

namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Admin > System Settings > FAQs
class FaqController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validateFaq($request);
        $validated['sort_order'] = ((int) Faq::max('sort_order')) + 1;
        Faq::create($validated);

        return $this->saved($request, 'FAQ added.');
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validateFaq($request, $faq));

        return $this->saved($request, 'FAQ updated.');
    }

    public function destroy(Request $request, Faq $faq)
    {
        $faq->delete();

        return $this->saved($request, 'FAQ deleted.');
    }

    // Swap places with the neighbouring FAQ (direction = up | down).
    public function move(Request $request, Faq $faq)
    {
        $direction = $request->validate(['direction' => 'required|in:up,down'])['direction'];

        DB::transaction(function () use ($faq, $direction) {
            // Normalise to 1..n first so equal/legacy sort values can't block a move.
            $all = Faq::orderBy('sort_order')->orderBy('faq_id')->lockForUpdate()->get()->values();
            foreach ($all as $i => $row) {
                if ((int) $row->sort_order !== $i + 1) {
                    $row->update(['sort_order' => $i + 1]);
                }
            }

            $index = $all->search(fn ($row) => $row->faq_id === $faq->faq_id);
            $swap = $direction === 'up' ? $index - 1 : $index + 1;
            if ($index === false || !isset($all[$swap])) {
                return;
            }

            $a = $all[$index];
            $b = $all[$swap];
            [$aOrder, $bOrder] = [$a->sort_order, $b->sort_order];
            $a->update(['sort_order' => $bOrder]);
            $b->update(['sort_order' => $aOrder]);
        });

        return $this->saved($request, 'FAQ order updated.');
    }

    private function validateFaq(Request $request, ?Faq $faq = null): array
    {
        foreach (['question', 'answer'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([$field => trim($request->input($field))]);
            }
        }

        $validated = $request->validate([
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string|max:5000',
            'is_active' => 'sometimes|boolean',
        ]);
        $validated['is_active'] = $request->boolean('is_active', $faq ? (bool) $faq->is_active : true);

        return $validated;
    }

    private function saved(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'html'    => view('admin_acc.partials.faqs-table', [
                    'faqs' => Faq::orderBy('sort_order')->orderBy('faq_id')->get(),
                ])->render(),
            ]);
        }

        return redirect()->route('system_settings.index', ['tab' => 'faqs'])->with('success', $message);
    }
}