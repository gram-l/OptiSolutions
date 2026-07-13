@extends('staff.layouts.app')

@section('content')
<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $totalAppointments ?? 0 }}</div>
            <div class="stat-label">Total Appointments</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $pendingInquiries ?? 0 }}</div>
            <div class="stat-label">Pending Inquiries</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $activeDoctors ?? 0 }}</div>
            <div class="stat-label">Active Doctors</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $totalPatients ?? 0 }}</div>
            <div class="stat-label">Registered Patients</div>
        </div>
    </div>

    <!-- SENTIMENT ANALYSIS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">{{ $positivePercent ?? 0 }}%</div>
            <div class="stat-label">Positive Feedback</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $neutralPercent ?? 0 }}%</div>
            <div class="stat-label">Neutral</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">{{ $negativePercent ?? 0 }}%</div>
            <div class="stat-label">Negative</div>
        </div>
    </div>

    <!-- SERVICE DISTRIBUTION -->
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Service Distribution</h3>
        <p style="color: #7f8c8d; font-size: 0.9rem;">Distribution of patient visits by department</p>
        
        @if(isset($serviceDistribution) && $serviceDistribution->count() > 0)
            @foreach($serviceDistribution as $service)
                <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--light-gray);">
                    <span>{{ $service->service_type }}</span>
                    <span>{{ $service->total }} visits</span>
                </div>
            @endforeach
        @else
            <p style="color: #7f8c8d; margin-top: 10px;">No service data available yet.</p>
        @endif
    </div>

    <!-- RECENT ACTIVITY -->
    <div style="background: white; border-radius: 20px; padding: 1.5rem; box-shadow: var(--shadow); margin-top: 2rem;">
        <h3 style="color: var(--text-dark);">Recent Activity</h3>
        <div style="padding: 0.8rem 0; border-bottom:1px solid var(--light-gray);">
            <i class="bi bi-chat-right-text" style="color:var(--primary-deep-blue); margin-right: 10px;"></i> 
            Welcome to the Staff Dashboard!
        </div>
        <div style="padding: 0.8rem 0; border-bottom:1px solid var(--light-gray);">
            <i class="bi bi-calendar-check" style="color:var(--primary-deep-blue); margin-right: 10px;"></i> 
            {{ $totalAppointments ?? 0 }} total appointments
        </div>
        <div style="padding: 0.8rem 0;">
            <i class="bi bi-people" style="color:var(--primary-deep-blue); margin-right: 10px;"></i> 
            {{ $totalPatients ?? 0 }} registered patients
        </div>
    </div>
</div>
@endsection