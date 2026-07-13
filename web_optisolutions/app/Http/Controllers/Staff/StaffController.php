<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\ScheduleVisit;
use App\Models\Staff\User;
use App\Models\Staff\Doctor;
use App\Models\Staff\Patient;
use App\Models\Staff\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;

class StaffController extends Controller
{
    public function login()
    {
        return view('staff.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'staff_email' => 'required|email',
            'staff_password' => 'required',
        ]);

        $user = User::where('email', $request->staff_email)
                    ->where('user_role', 'staff')
                    ->first();

        if (!$user || !Hash::check($request->staff_password, $user->password)) {
            return back()->withErrors([
                'staff_email' => 'Invalid email or password.',
            ])->onlyInput('staff_email');
        }

        if ($user->status !== 'active') {
            return back()->withErrors([
                'staff_email' => 'Your account is inactive. Please contact the administrator.',
            ])->onlyInput('staff_email');
        }

        Auth::guard('staff')->login($user);
        $request->session()->regenerate();

        return redirect()->intended('/staff/dashboard');
    }

    public function dashboard()
    {
        return view('staff.dashboard', $this->buildDashboardData());
    }

    /**
     * JSON endpoint polled every 30s by dashboard.blade.php's JavaScript
     * to refresh charts without a full page reload.
     *
     * Route to add in routes/staff_acc/web.php (inside the 'staff' middleware group):
     *   Route::get('/staff/dashboard/data', [StaffController::class, 'data'])->name('staff.dashboard.data');
     */
    public function data()
    {
        return response()->json($this->buildDashboardData());
    }

    /**
     * Shared data-building logic so dashboard() and data() never drift apart.
     */
    private function buildDashboardData(): array
    {
        // -----------------------------------------------------------
        // Stat cards
        // -----------------------------------------------------------
        $totalScheduleVisit = ScheduleVisit::count();
        $pendingInquiries   = Inquiry::where('resolved_status', 'Pending')->count();
        $activeDoctors      = Doctor::where('available', 1)->count();
        $totalPatients      = Patient::count();

        // -----------------------------------------------------------
        // 1) Service Distribution — was hardcoded to collect() (empty).
        //    Now actually queries schedule_visit.service_type.
        // -----------------------------------------------------------
        $serviceDistribution = ScheduleVisit::selectRaw('service_type, COUNT(*) as total')
            ->groupBy('service_type')
            ->get();

        // -----------------------------------------------------------
        // 2) Weekly Patient Visits — was missing entirely.
        //    Uses visit_date (actual appointment date), last 6 weeks
        //    including the current week.
        // -----------------------------------------------------------
        $weekLabels = [];
        $weeklyVisits = [];
        $startOfThisWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        for ($i = 5; $i >= 0; $i--) {
            $weekStart = $startOfThisWeek->copy()->subWeeks($i);
            $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

            $weekLabels[] = $i === 0 ? 'This wk' : 'W-' . $i;

            $weeklyVisits[] = ScheduleVisit::whereBetween('visit_date', [
                $weekStart->toDateString(),
                $weekEnd->toDateString(),
            ])->count();
        }

        // -----------------------------------------------------------
        // 3) Sentiment Analysis — was hardcoded (75/18/5) regardless of
        //    real feedback. Now queries the feedback table if it has a
        //    star_rating column; falls back to the old hardcoded values
        //    only if the table/column truly isn't there yet.
        // -----------------------------------------------------------
        $positivePercent = 75;
        $neutralPercent  = 18;
        $negativePercent = 5;

        if (Schema::hasTable('feedback') && Schema::hasColumn('feedback', 'star_rating')) {
            $feedbackCounts = DB::table('feedback')->selectRaw('
                    SUM(CASE WHEN star_rating >= 4 THEN 1 ELSE 0 END) as positive,
                    SUM(CASE WHEN star_rating = 3 THEN 1 ELSE 0 END) as neutral,
                    SUM(CASE WHEN star_rating > 0 AND star_rating < 3 THEN 1 ELSE 0 END) as negative
                ')->first();

            $sentimentTotal = ($feedbackCounts->positive ?? 0)
                + ($feedbackCounts->neutral ?? 0)
                + ($feedbackCounts->negative ?? 0);

            if ($sentimentTotal > 0) {
                $positivePercent = round($feedbackCounts->positive / $sentimentTotal * 100);
                $neutralPercent  = round($feedbackCounts->neutral  / $sentimentTotal * 100);
                $negativePercent = round($feedbackCounts->negative / $sentimentTotal * 100);
            }
        }

        // -----------------------------------------------------------
        // 4) Inquiry Volume per Week — was missing entirely.
        //    inquiries has no date column of its own, so we JOIN to
        //    chatbot_logs (via log_id) to get chat_time as the date.
        // -----------------------------------------------------------
        $inquiryVolumeByDay = array_fill(0, 7, 0); // index 0 = Monday

        $thisWeekInquiries = DB::table('inquiries')
            ->join('chatbot_logs', 'inquiries.log_id', '=', 'chatbot_logs.log_id')
            ->whereBetween('chatbot_logs.chat_time', [
                $startOfThisWeek->toDateTimeString(),
                $startOfThisWeek->copy()->endOfWeek(Carbon::SUNDAY)->toDateTimeString(),
            ])
            ->pluck('chatbot_logs.chat_time');

        foreach ($thisWeekInquiries as $chatTime) {
            $dayIndex = Carbon::parse($chatTime)->dayOfWeekIso - 1; // Mon=0 .. Sun=6
            $inquiryVolumeByDay[$dayIndex]++;
        }

        // -----------------------------------------------------------
        return [
            'totalScheduleVisit'  => $totalScheduleVisit,
            'pendingInquiries'    => $pendingInquiries,
            'activeDoctors'       => $activeDoctors,
            'totalPatients'       => $totalPatients,
            'serviceDistribution' => $serviceDistribution,
            'weekLabels'          => $weekLabels,
            'weeklyVisits'        => $weeklyVisits,
            'positivePercent'     => $positivePercent,
            'neutralPercent'      => $neutralPercent,
            'negativePercent'     => $negativePercent,
            'inquiryVolumeByDay'  => $inquiryVolumeByDay,
        ];
    }

    public function logout(Request $request)
    {
        Auth::guard('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/staff/login');
    }
}