<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Admin Dashboard</title>
    <!-- Vite CSS -->
   @vite([
    'resources/css/admin_css/dashboard.css', 
    'resources/css/admin_css/sidebar.css', 
    'resources/css/admin_css/header.css'
])


</head>
<body>
    <!-- Header -->
  @include('admin_acc.header')

    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar Navigation -->
        @include('admin_acc.sidebar')

        <!-- Main Content -->
        <main class="main-content">
            <div class="welcome-banner">
                <h2>Welcome, {{ Auth::user()->name }}!</h2>
                <p>Here's what's happening with your clinic today.</p>
            </div>

            <!-- Analytics Cards -->
            <div class="analytics-grid">
                <div class="analytics-card">
                    <div class="card-title">Total Inquiries</div>
                    <div class="card-value">0</div>
                    <div class="card-subtitle">↑ 12% from last week</div>
                </div>
                <div class="analytics-card">
                    <div class="card-title">Today's Appointments</div>
                    <div class="card-value">0</div>
                    <div class="card-subtitle">0 pending approval</div>
                </div>
                <div class="analytics-card">
                    <div class="card-title">Active Patients</div>
                    <div class="card-value">0</div>
                    <div class="card-subtitle">18 new this month</div>
                </div>
                <div class="analytics-card">
                    <div class="card-title">Patient Satisfaction</div>
                    <div class="card-value">0</div>
                    <div class="card-subtitle">⭐ Average rating</div>
                </div>
            </div>

            <!-- Charts Section - FIXED: Using direct hex colors instead of missing variables -->
            <div class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Inquiry Volume (Last 7 Days)</h3>
                        <button class="filter-btn">This Week ▼</button>
                    </div>
                    <div style="height: 250px; background: var(--light-gray); border-radius: 10px; display: flex; align-items: flex-end; justify-content: space-around; padding: 1rem;">
                        <div style="width: 12%; background: #0E62AA; height: 60%; border-radius: 8px 8px 0 0;"></div>
                        <div style="width: 12%; background: #9DBCD4; height: 75%; border-radius: 8px 8px 0 0;"></div>
                        <div style="width: 12%; background: #062744; height: 85%; border-radius: 8px 8px 0 0;"></div>
                        <div style="width: 12%; background: #0E62AA; height: 70%; border-radius: 8px 8px 0 0;"></div>
                        <div style="width: 12%; background: #9DBCD4; height: 90%; border-radius: 8px 8px 0 0;"></div>
                        <div style="width: 12%; background: #062744; height: 100%; border-radius: 8px 8px 0 0;"></div>
                        <div style="width: 12%; background: #0E62AA; height: 65%; border-radius: 8px 8px 0 0;"></div>
                    </div>
                </div>

                <!-- Sentiment Analysis - FIXED: Using correct colors -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Sentiment Analysis</h3>
                    </div>
                    <div class="sentiment-bars">
                        <div class="sentiment-bar">
                            <div class="sentiment-label" style="color: #0E62AA;">😊 Positive</div>
                            <div class="bar-container">
                                <div class="bar-fill bar-positive" style="width: 75%;">75%</div>
                            </div>
                        </div>
                        <div class="sentiment-bar">
                            <div class="sentiment-label" style="color: #9DBCD4;">😐 Neutral</div>
                            <div class="bar-container">
                                <div class="bar-fill bar-neutral" style="width: 18%;">18%</div>
                            </div>
                        </div>
                        <div class="sentiment-bar">
                            <div class="sentiment-label" style="color: #062744;">😟 Negative</div>
                            <div class="bar-container">
                                <div class="bar-fill bar-negative" style="width: 7%;">7%</div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 2rem; padding: 1rem; background: var(--light-gray); border-radius: 10px;">
                        <div style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0.5rem;">Top Complaint:</div>
                        <div style="font-size: 0.85rem; color: #7f8c8d;">Long waiting times at reception</div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-section">
                <div class="activity-header">
                    <h3>Recent Activity</h3>
                </div>
                <div class="activity-item">
                    <div class="activity-icon activity-icon-orange"><i class="fas fa-comment"></i></div>
                    <div class="activity-details">
                        <div class="activity-title">New inquiry from Maria Santos</div>
                        <div class="activity-time">2 minutes ago • Ophthalmology consultation</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon activity-icon-blue"><i class="fas fa-calendar"></i></div>
                    <div class="activity-details">
                        <div class="activity-title">Appointment scheduled with Dr. Cruz</div>
                        <div class="activity-time">15 minutes ago • May 23, 2026 at 10:00 AM</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon activity-icon-green"><i class="fas fa-star"></i></div>
                    <div class="activity-details">
                        <div class="activity-title">New 5-star feedback received</div>
                        <div class="activity-time">1 hour ago • ENT Department</div>
                    </div>
                </div>
                <div class="activity-item">
                    <div class="activity-icon activity-icon-yellow"><i class="fas fa-user-plus"></i></div>
                    <div class="activity-details">
                        <div class="activity-title">New patient registration</div>
                        <div class="activity-time">2 hours ago • Pediatrics</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="action-btn action-orange" onclick="window.location.href='chatbotlogs.html'">📋 View All Inquiries</button>
                <button class="action-btn action-blue" onclick="window.location.href='doctors.html'">➕ Add New Doctor</button>
                <button class="action-btn action-yellow" onclick="window.location.href='reports.html'">📊 Generate Report</button>
                <button class="action-btn action-green" onclick="window.location.href='usermanagement.html'">👥 Manage Staff</button>
            </div>
        </main>
    </div>

    <script>
        // Simple navigation interaction
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });

       

        // Animate sentiment bars on load
        window.addEventListener('load', function() {
            const bars = document.querySelectorAll('.bar-fill');
            bars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>