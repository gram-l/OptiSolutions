<?php
// app/Http/Controllers/admin_acc/ServiceController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\admin_acc\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:100',
            'icon'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'room'        => 'nullable|string|max:50',
            'schedule'    => 'nullable|string|max:255',
        ]);

        $validated['service_key'] = Str::slug($validated['title'], '_');
        $validated['available']   = true;

        Service::create($validated);

        return back()->with('success', 'Service added.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:100',
            'icon'        => 'required|string|max:50',
            'description' => 'nullable|string',
            'room'        => 'nullable|string|max:50',
            'schedule'    => 'nullable|string|max:255',
        ]);

        $validated['available'] = $request->boolean('available');

        $service->update($validated);

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return back()->with('success', 'Service deleted.');
    }
}