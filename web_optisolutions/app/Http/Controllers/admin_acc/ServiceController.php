<?php
// app/Http/Controllers/admin_acc/ServiceController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:100',
        ]);

        $validated['sort_order'] = (Service::max('sort_order') ?? 0) + 1;
        $validated['is_active']  = true;

        Service::create($validated);

        return back()->with('success', 'Service added.');
    }

    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:100',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $service->update($validated);

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return back()->with('success', 'Service deleted.');
    }
}