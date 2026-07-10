<?php

// app/Http/Controllers/admin_acc/SystemSettingsController.php
namespace App\Http\Controllers\admin_acc;

use App\Http\Controllers\Controller;
use App\Models\ClinicInfo;
use App\Models\Service;
use Illuminate\Http\Request;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $clinic   = ClinicInfo::firstOrFail();
        $services = Service::orderBy('sort_order')->get();

        return view('admin_acc.system_settings.index', compact('clinic', 'services'));
    }

    public function updateGroup(Request $request, string $group)
    {
        $clinic = ClinicInfo::firstOrFail();

        $fieldsByGroup = [
            'hours'   => ['operating_hours'],
            'about'   => ['about_us', 'mission', 'vision', 'core_values'],
            'contact' => ['address', 'contact_no', 'email', 'facebook_link', 'website'],
        ];

        abort_unless(isset($fieldsByGroup[$group]), 404);

        $rules = [
            'operating_hours' => 'sometimes|string|max:255',
            'about_us'        => 'sometimes|nullable|string',
            'mission'         => 'sometimes|nullable|string',
            'vision'          => 'sometimes|nullable|string',
            'core_values'     => 'sometimes|array',
            'core_values.*'   => 'nullable|string|max:255',
            'address'         => 'sometimes|nullable|string',
            'contact_no'      => 'sometimes|nullable|string|max:20',
            'email'           => 'sometimes|nullable|email|max:255',
            'facebook_link'   => 'sometimes|nullable|url|max:255',
            'website'         => 'sometimes|nullable|url|max:255',
        ];

        $validated = $request->validate(
            array_intersect_key($rules, array_flip($fieldsByGroup[$group]))
        );

        if ($group === 'about' && isset($validated['core_values'])) {
            $validated['core_values'] = array_values(array_filter($validated['core_values']));
        }

        $clinic->update($validated);

        return back()->with('success', ucfirst($group) . ' updated successfully.');
    }
}