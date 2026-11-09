<?php
// app/Http/Controllers/admin_acc/ServiceController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
        $service = Service::create($validated + ['service_key' => $candidate]);
        $this->syncFocusAreas($service, $focusAreas);
        return $this->saved($request, 'Service added.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $this->validateService($request, $service);
        $oldImage = $service->card_image;
        $newImage = $this->storeCardImage($request);
        if ($newImage) {
            $validated['card_image'] = $newImage;
        } elseif (!empty($validated['remove_card_image'])) {
            $validated['card_image'] = null;
        }
        unset($validated['remove_card_image']);
        $focusAreas = $this->takeFocusAreas($validated);
        if (array_key_exists('icon', $validated)) $validated['icon'] = $validated['icon'] ?? '';
        DB::transaction(function () use ($service, $validated, $focusAreas) {
            $service->update($validated);
            $this->syncFocusAreas($service, $focusAreas);
        });
        if (array_key_exists('card_image', $validated) && $oldImage !== ($validated['card_image'] ?? null)) {
            $this->deleteCardImage($oldImage);
        }
        return $this->saved($request, 'Service updated.');
    }

    public function destroy(Request $request, Service $service)
    {
        $image = $service->card_image;
        DB::transaction(function () use ($service) {
            DB::table('doctor_service')->where('service_id', $service->service_id)->delete();
            DB::table('service_conditions')->where('service_id', $service->service_id)->delete();
            $service->delete();
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

    // Saves an uploaded card picture to public/images/services and returns its web path.
    private function storeCardImage(Request $request): ?string
    {
        $file = $request->file('card_image');
        if (!$file || !$file->isValid()) return null;

        $dir = public_path('images/services');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $name = 'service_'.Str::lower(Str::random(16)).'.'.$file->getClientOriginalExtension();
        $file->move($dir, $name);

        return '/images/services/'.$name;
    }

    // Only ever removes files this feature uploaded (never the built-in artwork).
    private function deleteCardImage(?string $path): void
    {
        if ($path && str_starts_with($path, '/images/services/')) {
            $full = public_path(ltrim($path, '/'));
            if (is_file($full)) @unlink($full);
        }
    }
}