<?php
// app/Http/Controllers/admin_acc/ServiceController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validateService($request);
        $key = Str::substr(Str::slug($validated['title'], '_'), 0, 40) ?: 'service';
        $candidate = $key;
        while (Service::where('service_key', $candidate)->exists()) $candidate = $key.'_'.Str::lower(Str::random(8));
        Service::create($validated + ['service_key' => $candidate, 'icon' => 'fa-solid fa-stethoscope']);
        return $this->saved($request, 'Service added.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $this->validateService($request, $service);
        DB::transaction(function () use ($service, $validated) {
            $service->update($validated);
        });
        return $this->saved($request, 'Service updated.');
    }

    public function destroy(Request $request, Service $service)
    {
        DB::transaction(function () use ($service) {
            DB::table('doctor_service')->where('service_id', $service->service_id)->delete();
            $service->delete();
        });
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
        ]);
        $validated['available'] = $request->boolean('available', $service ? (bool) $service->available : true);
        return $validated;
    }

    private function saved(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message,
                'html' => view('admin_acc.partials.services-table', ['services' => Service::orderBy('service_id')->get()])->render()]);
        }
        return redirect()->route('system_settings.index', ['tab' => 'services'])->with('success', $message);
    }
}
