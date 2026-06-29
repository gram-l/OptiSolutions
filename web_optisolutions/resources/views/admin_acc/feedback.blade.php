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
    <title>OptiSolutions - Feedback & Sentiment Analysis</title>
   @vite(['resources/css/admin_css/feedback.css', 'resources/css/admin_css/sidebar.css'])
</head>
<body>
    <!-- Header -->
     @include('admin_acc.header')
 

    <!-- Main Container -->
    <div class="container">
        @include('admin_acc.sidebar')
        <div class="page-header">
            <h2>
                <span><i class="fa-solid fa-star-half-stroke"></i></span> 
                Feedback & Sentiment Analysis
            </h2>
            <p>Review patient feedback, analyze sentiment trends, and identify recurring issues by service or doctor</p>
            
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <input type="text" class="search-box" id="searchInput" placeholder="Search by patient or comment...">
                <select class="filter-select" id="sentimentFilter">
                    <option value="all">All Sentiments</option>
                    <option value="positive">Positive <i class="fa-regular fa-face-smile-beam"></i></option>
                    <option value="neutral">Neutral <i class="fa-regular fa-face-meh"></i></option>
                    <option value="negative">Negative <i class="fa-regular fa-face-frown"></i></option>
                </select>
                <select class="filter-select" id="departmentFilter">
                    <option value="all">All Departments</option>
                    <option value="Ophthalmology">Ophthalmology</option>
                    <option value="Pediatrics">Pediatrics</option>
                    <option value="ENT">ENT</option>
                    <option value="Cardiology">Cardiology</option>
                    <option value="Dermatology">Dermatology</option>
                    <option value="Orthopedics">Orthopedics</option>
                </select>
            </div>
        </div>

        <!-- Analytics Dashboard -->
        <div class="analytics-dashboard">
            <div class="analytics-card">
                <div class="analytics-title"><i class="fa-solid fa-chart-pie"></i> Sentiment Distribution</div>
                <div class="sentiment-summary" id="sentimentSummary">
                    <!-- Dynamic -->
                </div>
                <div style="margin-top: 1rem;">
                    <div class="rating-stars" id="avgRating"></div>
                    <div style="font-size: 0.8rem; color: #7f8c8d; margin-top: 0.3rem;">Average Rating: <span id="avgRatingValue">0</span>/5</div>
                </div>
            </div>
            <div class="analytics-card">
                <div class="analytics-title"><i class="fa-solid fa-chart-line"></i> Trend Overview</div>
                <div id="trendInfo" style="font-size: 1.2rem; line-height: 1.8;">
                    <div><i class="fa-regular fa-face-smile-beam"></i> Positive feedback: <span id="positivePercent">0</span>%</div>
                    <div><i class="fa-regular fa-face-frown"></i> Negative feedback: <span id="negativePercent">0</span>%</div>
                    <div><i class="fa-regular fa-file-lines"></i> Total responses: <span id="totalCount">0</span></div>
                </div>
            </div>
            <div class="analytics-card">
                <div class="analytics-title"><i class="fa-solid fa-trophy"></i> Top Rated Departments</div>
                <div id="topDepartments"></div>
            </div>
        </div>

        <!-- Pattern Analysis - Negative Feedback Patterns -->
        <div class="pattern-section">
            <div class="pattern-card">
                <div class="pattern-header">
                    <span></span> Recurring Negative Patterns by Department
                </div>
                <div id="departmentPatterns"></div>
            </div>
            <div class="pattern-card">
                <div class="pattern-header">
                    <span><i class="fa-solid fa-user-md"></i></span> Doctor Performance Insights
                </div>
                <div id="doctorPatterns"></div>
            </div>
        </div>

        <!-- Feedback List -->
        <div class="feedback-section">
            <div class="section-title">📝 All Patient Feedback</div>
            <table class="feedback-table" id="feedbackTable">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Department</th>
                        <th>Doctor</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Sentiment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="feedbackTableBody">
                    <!-- Dynamic content -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Mock feedback data with sentiment analysis
        const feedbackData = [
            { id: 1, patient: "Maria Santos", department: "Ophthalmology", doctor: "Dr. Maria Reyes", rating: 5, comment: "Excellent service! Dr. Reyes was very thorough and explained everything clearly. The staff was friendly and the wait time was minimal.", sentiment: "positive", date: "2026-05-20" },
            { id: 2, patient: "John Dela Cruz", department: "Pediatrics", doctor: "Dr. Jose Mendoza", rating: 4, comment: "Good experience overall. The doctor was great with my son. Only downside was the waiting area was crowded.", sentiment: "positive", date: "2026-05-19" },
            { id: 3, patient: "Anna Rivera", department: "ENT", doctor: "Dr. Anna Garcia", rating: 2, comment: "Long waiting time at reception, waited over an hour just to be seen. The doctor was rushed and didn't fully address my concerns.", sentiment: "negative", date: "2026-05-18" },
            { id: 4, patient: "Carlos Gomez", department: "Cardiology", doctor: "Dr. Carlos Santos", rating: 5, comment: "Dr. Santos is amazing! Very professional and caring. The clinic is well-organized and clean.", sentiment: "positive", date: "2026-05-17" },
            { id: 5, patient: "Elena Garcia", department: "Dermatology", doctor: "Dr. Elena Lopez", rating: 3, comment: "The treatment was effective but scheduling was difficult. Had to wait 3 weeks for an appointment.", sentiment: "neutral", date: "2026-05-16" },
            { id: 6, patient: "Roberto Javier", department: "Ophthalmology", doctor: "Dr. Maria Reyes", rating: 1, comment: "Very disappointed. The receptionist was rude and the doctor barely spent 5 minutes with me. Felt like just a number.", sentiment: "negative", date: "2026-05-15" },
            { id: 7, patient: "Sofia Villanueva", department: "Pediatrics", doctor: "Dr. Jose Mendoza", rating: 5, comment: "Wonderful experience! Dr. Mendoza is so patient and kind with children. Highly recommend!", sentiment: "positive", date: "2026-05-14" },
            { id: 8, patient: "Luis Martinez", department: "ENT", doctor: "Dr. Anna Garcia", rating: 2, comment: "Long waiting times again. Came at 10 AM, got seen at 11:30 AM. Need better scheduling system.", sentiment: "negative", date: "2026-05-13" },
            { id: 9, patient: "Patricia Cruz", department: "Cardiology", doctor: "Dr. Carlos Santos", rating: 4, comment: "Good doctor but the facility needs improvement. Parking is terrible and the waiting room is too small.", sentiment: "neutral", date: "2026-05-12" },
            { id: 10, patient: "Miguel Tan", department: "Dermatology", doctor: "Dr. Elena Lopez", rating: 5, comment: "Very satisfied with my treatment. Clear instructions and follow-up care was excellent.", sentiment: "positive", date: "2026-05-11" },
            { id: 11, patient: "Isabel Flores", department: "Ophthalmology", doctor: "Dr. Maria Reyes", rating: 3, comment: "Doctor was okay but the front desk staff needs training. They lost my paperwork and I had to fill it again.", sentiment: "neutral", date: "2026-05-10" },
            { id: 12, patient: "Ricardo Lopez", department: "Pediatrics", doctor: "Dr. Jose Mendoza", rating: 4, comment: "Good experience but the appointment was delayed by 30 minutes. Otherwise doctor was great.", sentiment: "positive", date: "2026-05-09" },
            { id: 13, patient: "Carmen Reyes", department: "ENT", doctor: "Dr. Anna Garcia", rating: 1, comment: "Horrible experience. The doctor didn't listen to my symptoms and just prescribed medication without proper diagnosis.", sentiment: "negative", date: "2026-05-08" },
            { id: 14, patient: "Antonio Cruz", department: "Cardiology", doctor: "Dr. Carlos Santos", rating: 5, comment: "Dr. Santos saved my life! His expertise and care are unmatched. Forever grateful.", sentiment: "positive", date: "2026-05-07" },
            { id: 15, patient: "Julia Ramos", department: "Ophthalmology", doctor: "Dr. Maria Reyes", rating: 2, comment: "Long waiting time. Appointment was at 9AM, got called at 10:15AM. Very frustrating.", sentiment: "negative", date: "2026-05-06" }
        ];

        // Helper functions
        function getSentimentFromRating(rating, comment) {
            // Mock sentiment already assigned, but for display we'll use the data
            return;
        }

        function renderStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += i <= rating ? '<span class="star-filled">★</span>' : '<span class="star-empty">☆</span>';
            }
            return stars;
        }

        // Render analytics and patterns
        function renderAnalytics(filteredData) {
            const total = filteredData.length;
            const positive = filteredData.filter(f => f.sentiment === 'positive').length;
            const neutral = filteredData.filter(f => f.sentiment === 'neutral').length;
            const negative = filteredData.filter(f => f.sentiment === 'negative').length;
            const avgRating = (filteredData.reduce((sum, f) => sum + f.rating, 0) / total).toFixed(1);
            
            document.getElementById('sentimentSummary').innerHTML = `
                <div class="sentiment-item"><div class="sentiment-count positive">${positive}</div><div class="sentiment-label">Positive 😊</div></div>
                <div class="sentiment-item"><div class="sentiment-count neutral">${neutral}</div><div class="sentiment-label">Neutral 😐</div></div>
                <div class="sentiment-item"><div class="sentiment-count negative">${negative}</div><div class="sentiment-label">Negative 😟</div></div>
            `;
            
            document.getElementById('avgRatingValue').innerText = avgRating;
            document.getElementById('positivePercent').innerText = total ? ((positive/total)*100).toFixed(0) : 0;
            document.getElementById('negativePercent').innerText = total ? ((negative/total)*100).toFixed(0) : 0;
            document.getElementById('totalCount').innerText = total;
            
            // Top departments by average rating
            const deptRatings = {};
            filteredData.forEach(f => {
                if (!deptRatings[f.department]) {
                    deptRatings[f.department] = { sum: 0, count: 0 };
                }
                deptRatings[f.department].sum += f.rating;
                deptRatings[f.department].count += 1;
            });
            const deptAvg = Object.entries(deptRatings).map(([dept, data]) => ({ dept, avg: data.sum/data.count }));
            deptAvg.sort((a,b) => b.avg - a.avg);
            const topDepts = deptAvg.slice(0, 3);
            document.getElementById('topDepartments').innerHTML = topDepts.map(d => `
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span>${d.dept}</span>
                    <span class="rating-stars">${renderStars(Math.round(d.avg))}</span>
                </div>
            `).join('') || '<div style="color:#95a5a6;">No data available</div>';
            
            // Department patterns (negative feedback analysis)
            const deptNegative = {};
            filteredData.forEach(f => {
                if (f.sentiment === 'negative') {
                    if (!deptNegative[f.department]) deptNegative[f.department] = { count: 0, comments: [] };
                    deptNegative[f.department].count++;
                    deptNegative[f.department].comments.push(f.comment);
                }
            });
            const deptNegativeList = Object.entries(deptNegative).map(([dept, data]) => ({ dept, count: data.count, comments: data.comments }));
            deptNegativeList.sort((a,b) => b.count - a.count);
            
            document.getElementById('departmentPatterns').innerHTML = deptNegativeList.length ? 
                deptNegativeList.map(d => {
                    const totalDept = filteredData.filter(f => f.department === d.dept).length;
                    const percent = totalDept ? ((d.count/totalDept)*100).toFixed(0) : 0;
                    const topComplaint = extractCommonIssue(d.comments);
                    return `
                        <div class="pattern-item">
                            <div class="pattern-name">${d.dept}</div>
                            <div class="pattern-stats">
                                <span class="negative-count">${d.count} negative</span>
                                <div class="progress-bar"><div class="progress-fill fill-negative" style="width: ${percent}%"></div></div>
                                <span>${percent}%</span>
                            </div>
                        </div>
                        <div style="font-size:0.7rem; color:#e74c3c; margin-left:0.5rem; margin-bottom:0.5rem;">⚠️ "${topComplaint}"</div>
                    `;
                }).join('') : '<div style="padding:1rem; text-align:center; color:#95a5a6;">No negative feedback for selected filters</div>';
            
            // Doctor patterns
            const doctorNegative = {};
            filteredData.forEach(f => {
                if (f.sentiment === 'negative') {
                    if (!doctorNegative[f.doctor]) doctorNegative[f.doctor] = { count: 0, comments: [] };
                    doctorNegative[f.doctor].count++;
                    doctorNegative[f.doctor].comments.push(f.comment);
                }
            });
            const doctorNegativeList = Object.entries(doctorNegative).map(([doc, data]) => ({ doc, count: data.count, comments: data.comments }));
            doctorNegativeList.sort((a,b) => b.count - a.count);
            
            document.getElementById('doctorPatterns').innerHTML = doctorNegativeList.length ?
                doctorNegativeList.map(d => {
                    const totalDoc = filteredData.filter(f => f.doctor === d.doc).length;
                    const percent = totalDoc ? ((d.count/totalDoc)*100).toFixed(0) : 0;
                    const topComplaint = extractCommonIssue(d.comments);
                    return `
                        <div class="pattern-item">
                            <div class="pattern-name">${d.doc}</div>
                            <div class="pattern-stats">
                                <span class="negative-count">${d.count} negative</span>
                                <div class="progress-bar"><div class="progress-fill fill-negative" style="width: ${percent}%"></div></div>
                                <span>${percent}%</span>
                            </div>
                        </div>
                        <div style="font-size:0.7rem; color:#e74c3c; margin-left:0.5rem; margin-bottom:0.5rem;">⚠️ "${topComplaint}"</div>
                    `;
                }).join('') : '<div style="padding:1rem; text-align:center; color:#95a5a6;">No negative feedback for selected filters</div>';
        }
        
        function extractCommonIssue(comments) {
            // Simple keyword extraction for demo
            const commonPatterns = [
                { keywords: ['wait', 'waiting', 'delay', 'late'], message: 'Long waiting times' },
                { keywords: ['rude', 'unfriendly', 'receptionist', 'staff'], message: 'Staff attitude concerns' },
                { keywords: ['rushed', 'not listen', 'didn\'t address', 'barely spent'], message: 'Doctor rushed consultation' },
                { keywords: ['scheduling', 'appointment', 'book', 'reschedule'], message: 'Scheduling difficulties' },
                { keywords: ['clean', 'dirty', 'facility', 'parking'], message: 'Facility issues' }
            ];
            
            for (const pattern of commonPatterns) {
                for (const comment of comments) {
                    if (pattern.keywords.some(kw => comment.toLowerCase().includes(kw))) {
                        return pattern.message;
                    }
                }
            }
            return 'General dissatisfaction';
        }
        
        // Render feedback table
        function renderFeedbackTable(filteredData) {
            const tbody = document.getElementById('feedbackTableBody');
            if (filteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No feedback entries found</td></tr>';
                return;
            }
            
            tbody.innerHTML = filteredData.map(f => `
                <tr>
                    <td>${escapeHtml(f.patient)}</td>
                    <td>${escapeHtml(f.department)}</td>
                    <td class="doctor-name">${escapeHtml(f.doctor)}</td>
                    <td class="rating-stars">${renderStars(f.rating)}</td>
                    <td class="feedback-comment">${escapeHtml(f.comment)}</td>
                    <td><span class="sentiment-tag sentiment-${f.sentiment}">${f.sentiment === 'positive' ? '😊 Positive' : f.sentiment === 'neutral' ? '😐 Neutral' : '😟 Negative'}</span></td>
                    <td>${formatDate(f.date)}</td>
                </tr>
            `).join('');
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
        
        // Filter and refresh all
        function refreshAll() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const sentimentFilter = document.getElementById('sentimentFilter').value;
            const deptFilter = document.getElementById('departmentFilter').value;
            
            let filtered = feedbackData.filter(f => {
                const matchesSearch = f.patient.toLowerCase().includes(searchTerm) || f.comment.toLowerCase().includes(searchTerm);
                const matchesSentiment = sentimentFilter === 'all' || f.sentiment === sentimentFilter;
                const matchesDept = deptFilter === 'all' || f.department === deptFilter;
                return matchesSearch && matchesSentiment && matchesDept;
            });
            
            renderAnalytics(filtered);
            renderFeedbackTable(filtered);
        }
        
        // Navigation functions
        function goBackToDashboard() {
            window.location.href = "dashboard.html";
        }
        
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                alert('Logging out... Redirecting to login page.');
            }
        }
        
        // Event listeners
        document.getElementById('searchInput').addEventListener('input', refreshAll);
        document.getElementById('sentimentFilter').addEventListener('change', refreshAll);
        document.getElementById('departmentFilter').addEventListener('change', refreshAll);
        
        // Initial render
        refreshAll();
    </script>
</body>
</html>