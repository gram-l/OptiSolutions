<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\ScheduleVisit;
use App\Models\Staff\Doctor;
use App\Models\Staff\Patient;
use App\Models\Staff\Inquiry;
use App\Models\Staff\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    public function dashboard(Request $request)
    {
        $data = $this->buildDashboardData();
        $data['diagnostic'] = app(\App\Services\DiagnosticAnalyticsService::class)->dashboard($request);
        return view('staff.dashboard', $data);
    }

    public function diagnostics(Request $request)
    {
        $diagnostic = app(\App\Services\DiagnosticAnalyticsService::class)->dashboard($request);
        return response()->json([
            'html' => view('admin_acc.partials.diagnostic_results', [
                'diagnostic' => $diagnostic, 'diagnosticStaff' => true,
            ])->render(),
        ]);
    }

    public function data()
    {
        return response()->json($this->buildDashboardData());
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = Auth::user();

        // burahin lumang photo kung meron
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $path = $request->file('profile_photo')->store('profile_photos', 'public');

        $user->profile_photo = $path;
        $user->save();

        return back()->with('success', 'Profile photo updated!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password updated!');
    }

    private function buildDashboardData(): array
    {
        $today = \Carbon\CarbonImmutable::now('Asia/Manila');
        $todaysScheduledVisits = ScheduleVisit::whereDate('visit_date', $today->toDateString())->count();


        $pendingInquiries = Inquiry::whereNull('inquiry_reply')
            ->orWhere('inquiry_reply', '')
            ->count();

        $availableDoctorsToday = Doctor::where('available', 1)
            ->whereHas('schedules', fn ($query) => $query->where('day', $today->format('l')))
            ->count();
        $totalPatients      = Patient::count();

        return array_merge([
            'todaysScheduledVisits' => $todaysScheduledVisits,
            'pendingInquiries' => $pendingInquiries,
            'availableDoctorsToday' => $availableDoctorsToday,
            'totalPatients' => $totalPatients,
            'recentActivities' => $this->buildRecentActivities(),
        ], app(\App\Services\DashboardChartService::class)->data());
    }

    private function buildRecentActivities(int $limit = 5): array
    {
        if (!Schema::hasTable('app_notifications')) {
            return [];
        }

        return AppNotification::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($notif) {
                [$icon, $color] = $this->iconAndColorForType($notif->type);

                return [
                    'icon'        => $icon,
                    'color'       => $color,
                    'title'       => $notif->title,
                    'description' => $notif->message,
                    'time'        => $notif->created_at,
                    'time_human'  => $notif->created_at
                        ? $notif->created_at->diffForHumans()
                        : '',
                ];
            })
            ->values()
            ->toArray();
    }

    private function iconAndColorForType(?string $type): array
    {
        return match ($type) {
            'inquiry'     => ['bi-chat-dots', '#e67e22'],
            'schedule visit' => ['bi-calendar-check', '#2980b9'],
            'patient'     => ['bi-people', '#8e44ad'],
            'doctor'      => ['bi-person-badge', '#16a085'],
            default       => ['bi-bell', '#7f8c8d'],
        };
    }
}
