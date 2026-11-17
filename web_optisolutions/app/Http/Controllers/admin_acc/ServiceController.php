<?php
// app/Http/Controllers/admin_acc/ServiceController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Support\ServiceCardStyle;
class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validateService($request);
        $key = Str::substr(Str::slug($validated['title'], '_'), 0, 40) ?: 'service';
        $candidate = $key;
        while (Service::where('service_key', $candidate)->exists()) $candidate = $key.'_'.Str::lower(Str::random(8));
        $validated['icon'] = $validated['icon'] ?? ''; // '' = pick automatically from the service name
        $validated['card_image'] = $this->storeCardImage($request);
        unset($validated['remove_card_image']);
        $focusAreas = $this->takeFocusAreas($validated);
        try {
            DB::transaction(function () use ($validated, $candidate, $focusAreas) {
                $service = Service::create($validated + ['service_key' => $candidate]);
                $this->syncFocusAreas($service, $focusAreas);
            });
        } catch (\Throwable $exception) {
            $this->deleteCardImage($validated['card_image']);
            throw $exception;
        }
        return $this->saved($request, 'Service added.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $this->validateService($request, $service);
        $newImage = $this->storeCardImage($request);
        if ($newImage) {
            $validated['card_image'] = $newImage;
        } elseif (!empty($validated['remove_card_image'])) {
            $validated['card_image'] = null;
        }
        unset($validated['remove_card_image']);
        $focusAreas = $this->takeFocusAreas($validated);
        if (array_key_exists('icon', $validated)) $validated['icon'] = $validated['icon'] ?? '';
        try {
            $oldImage = DB::transaction(function () use ($service, $validated, $focusAreas) {
                $current = Service::whereKey($service->getKey())->lockForUpdate()->firstOrFail();
                $previous = $current->card_image;
                $current->update($validated);
                $this->syncFocusAreas($current, $focusAreas);
                return $previous;
            });
        } catch (\Throwable $exception) {
            $this->deleteCardImage($newImage);
            throw $exception;
        }
        if (array_key_exists('card_image', $validated) && $oldImage !== ($validated['card_image'] ?? null)) {
            $this->deleteCardImage($oldImage);
        }
        return $this->saved($request, 'Service updated.');
    }

    public function destroy(Request $request, Service $service)
    {
        $image = DB::transaction(function () use ($service) {
            $service = Service::whereKey($service->getKey())->lockForUpdate()->firstOrFail();
            $image = $service->card_image;
            DB::table('doctor_service')->where('service_id', $service->service_id)->delete();
            DB::table('service_conditions')->where('service_id', $service->service_id)->delete();
            $service->delete();
            return $image;
        });
        $this->deleteCardImage($image);
        return $this->saved($request, 'Service deleted.');
    }

    private function validateService(Request $request, ?Service $service = null): array
    {
        if (is_string($request->input('title'))) $request->merge(['title' => trim($request->input('title'))]);
        $validated = $request->validate([
            'title' => ['bail', 'required', 'string', 'max:100', function ($attribute, $value, $fail) use ($service) {
                $query = Service::whereRaw('LOWER(TRIM(title)) = ?', [mb_strtolower($value)]);
                if ($service) $query->where('service_id', '!=', $service->service_id);
                if ($query->exists()) $fail('A service with this title already exists.');
            }],
            'description' => 'nullable|string|max:10000',
            'room' => 'nullable|string|max:50',
            'available' => 'sometimes|boolean',
            'icon' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(ServiceCardStyle::ICONS))],
            'card_color' => ['sometimes', 'nullable', 'string', Rule::in(array_keys(ServiceCardStyle::COLORS))],
            'card_image' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_card_image' => 'sometimes|boolean',
            'focus_areas' => 'nullable|string|max:2000',
        ], [
            'card_image.uploaded' => 'The picture could not be uploaded. Try a JPG, PNG, or WebP image up to 2 MB.',
            'card_image.max' => 'The picture must be 2 MB or smaller.',
        ]);
        unset($validated['card_image']); // the uploaded file is handled by storeCardImage()
        if (array_key_exists('card_color', $validated)) $validated['card_color'] = $validated['card_color'] ?: null;
        $validated['available'] = $request->boolean('available', $service ? (bool) $service->available : true);
        return $validated;
    }

    // Removes focus_areas from the service columns and returns it as a clean list
    // (null = the field was not sent, so existing focus areas stay as they are).
    private function takeFocusAreas(array &$validated): ?array
    {
        if (!array_key_exists('focus_areas', $validated)) return null;
        $raw = (string) ($validated['focus_areas'] ?? '');
        unset($validated['focus_areas']);

        $items = [];
        foreach (preg_split('/\r\n|\r|\n|,/', $raw) as $item) {
            $item = trim($item);
            if ($item === '') continue;
            $items[mb_strtolower($item)] = Str::limit($item, 80, '');
        }
        return array_slice(array_values($items), 0, 12);
    }

    private function syncFocusAreas(Service $service, ?array $items): void
    {
        if ($items === null) return;
        DB::table('service_conditions')->where('service_id', $service->service_id)->delete();
        foreach ($items as $name) {
            DB::table('service_conditions')->insert(['service_id' => $service->service_id, 'condition_name' => $name]);
        }
    }

    private function saved(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message,
                'html' => view('admin_acc.partials.services-table', ['services' => Service::orderBy('service_id')->get()])->render()]);
        }
        return redirect()->route('system_settings.index', ['tab' => 'services'])->with('success', $message);
    }

    private function storeCardImage(Request $request): ?string
    {
        $file = $request->file('card_image');
        if (!$file) return null;

        try {
            $path = $file->store('service-cards', 'public');
            if ($path !== false) return '/storage/'.$path;
            Log::warning('The public storage disk refused a service photo upload.');
        } catch (\Throwable $exception) {
            report($exception);
        }

        throw ValidationException::withMessages([
            'card_image' => 'The server could not store the picture. Please try again.',
        ]);
    }

    private function deleteCardImage(?string $path): void
    {
        if (!$path) return;

        try {
            if (str_starts_with($path, '/storage/service-cards/')) {
                Storage::disk('public')->delete(substr($path, strlen('/storage/')));
            } elseif (preg_match('~^/images/services/service_[a-z0-9]+\.(?:jpe?g|png|webp)$~i', $path)) {
                // Clean up legacy uploads without touching the built-in service artwork.
                $full = public_path(ltrim($path, '/'));
                if (is_file($full)) unlink($full);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
