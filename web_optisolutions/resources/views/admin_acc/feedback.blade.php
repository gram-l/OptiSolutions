<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>OptiSolutions - Feedback & Sentiment Analysis</title>
    @vite(['resources/css/admin_css/feedback.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])
</head>
<body>
    @include('admin_acc.header')

    <div class="container">
        @include('admin_acc.sidebar')
        <div style="flex: 1; min-width: 0;">
            <div class="page-header">
                <h2>
                    <span><i class="fa-solid fa-star-half-stroke"></i></span>
                    Feedback & Sentiment Analysis
                </h2>
                <p>Review patient feedback, analyze sentiment trends, and identify recurring issues</p>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <input type="text" class="search-box" id="searchInput" placeholder="Search by comment...">
                    <select class="filter-select" id="sentimentFilter">
                        <option value="all">All Sentiments</option>
                        <option value="positive">Positive <i class="fa-regular fa-face-smile-beam"></i></option>
                        <option value="neutral">Neutral <i class="fa-regular fa-face-meh"></i></option>
                        <option value="negative">Negative <i class="fa-regular fa-face-frown"></i></option>
                    </select>
                </div>
            </div>

            <!-- Overview: sentiment + trend stacked left, recurring patterns tall on the right -->
            <div class="overview-grid">
                <div class="analytics-card card-sentiment">
                    <div class="analytics-title"><i class="fa-solid fa-chart-pie"></i> Sentiment Distribution</div>
                    <div class="sentiment-summary" id="sentimentSummary"></div>
                    <div style="margin-top: 0.6rem;">
                        <div class="rating-stars" id="avgRating"></div>
                        <div style="font-size: 0.8rem; color: #7f8c8d; margin-top: 0.25rem;">Average Rating: <span id="avgRatingValue">0</span>/5</div>
                    </div>
                </div>
                <div class="analytics-card card-trend">
                    <div class="analytics-title"><i class="fa-solid fa-chart-line"></i> Trend Overview</div>
                    <div id="trendInfo" class="trend-info">
                        <div><i class="fa-regular fa-face-smile-beam"></i> Positive feedback: <span id="positivePercent">0</span>%</div>
                        <div><i class="fa-regular fa-face-frown"></i> Negative feedback: <span id="negativePercent">0</span>%</div>
                        <div><i class="fa-regular fa-file-lines"></i> Total responses: <span id="totalCount">0</span></div>
                    </div>
                </div>
                <div class="pattern-card card-pattern">
                    <div class="pattern-header">Recurring Negative Patterns</div>
                    <div id="negativePatterns"></div>
                </div>
            </div>

            <!-- Feedback List -->
            <div class="feedback-section">
                
                <table class="feedback-table" id="feedbackTable">
                    <thead>
                        <tr>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Sentiment</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const feedbackData = @json($feedbackData);

        function renderStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += i <= rating ? '<span class="star-filled">★</span>' : '<span class="star-empty">☆</span>';
            }
            return stars;
        }

        function renderAnalytics(filteredData) {
            const total = filteredData.length;
            const positive = filteredData.filter(f => f.sentiment === 'positive').length;
            const neutral = filteredData.filter(f => f.sentiment === 'neutral').length;
            const negative = filteredData.filter(f => f.sentiment === 'negative').length;
            const avgRating = total ? (filteredData.reduce((sum, f) => sum + f.rating, 0) / total).toFixed(1) : 0;

            document.getElementById('sentimentSummary').innerHTML = `
                <div class="sentiment-item"><div class="sentiment-count positive">${positive}</div><div class="sentiment-label">Positive 😊</div></div>
                <div class="sentiment-item"><div class="sentiment-count neutral">${neutral}</div><div class="sentiment-label">Neutral 😐</div></div>
                <div class="sentiment-item"><div class="sentiment-count negative">${negative}</div><div class="sentiment-label">Negative 😟</div></div>
            `;

            document.getElementById('avgRatingValue').innerText = avgRating;
            document.getElementById('positivePercent').innerText = total ? ((positive/total)*100).toFixed(0) : 0;
            document.getElementById('negativePercent').innerText = total ? ((negative/total)*100).toFixed(0) : 0;
            document.getElementById('totalCount').innerText = total;

            // Recurring negative patterns (no department/doctor split)
            const negativeComments = filteredData.filter(f => f.sentiment === 'negative').map(f => f.comment);
            const patterns = [
                { keywords: ['wait', 'waiting', 'delay', 'late'], message: 'Long waiting times' },
                { keywords: ['rude', 'unfriendly', 'receptionist', 'staff'], message: 'Staff attitude concerns' },
                { keywords: ['rushed', 'not listen', "didn't address", 'barely spent'], message: 'Rushed consultations' },
                { keywords: ['scheduling', 'appointment', 'book', 'reschedule'], message: 'Scheduling difficulties' },
                { keywords: ['clean', 'dirty', 'facility', 'parking'], message: 'Facility issues' }
            ];

            const counts = patterns.map(p => ({
                message: p.message,
                count: negativeComments.filter(c => p.keywords.some(kw => (c || '').toLowerCase().includes(kw))).length
            })).filter(p => p.count > 0).sort((a, b) => b.count - a.count);

            document.getElementById('negativePatterns').innerHTML = counts.length ? counts.map(p => {
                const percent = negativeComments.length ? ((p.count / negativeComments.length) * 100).toFixed(0) : 0;
                return `
                    <div class="pattern-item">
                        <div class="pattern-name">${p.message}</div>
                        <div class="pattern-stats">
                            <span class="negative-count">${p.count} mentions</span>
                            <div class="progress-bar"><div class="progress-fill fill-negative" style="width: ${percent}%"></div></div>
                            <span>${percent}%</span>
                        </div>
                    </div>
                `;
            }).join('') : '<div style="padding:1rem; text-align:center; color:#95a5a6;">No negative feedback for selected filters</div>';
        }

        function renderFeedbackTable(filteredData) {
            const tbody = document.getElementById('feedbackTableBody');
            if (filteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-state">No feedback entries found</td></tr>';
                return;
            }

            tbody.innerHTML = filteredData.map(f => `
                <tr>
                    <td class="rating-stars">${renderStars(f.rating)}</td>
                    <td class="feedback-comment">${escapeHtml(f.comment)}</td>
                    <td>${sentimentTag(f.sentiment)}</td>
                    <td>${formatDate(f.date)}</td>
                </tr>
            `).join('');
        }

        function sentimentTag(sentiment) {
            const map = {
                positive: '😊 Positive',
                neutral: '😐 Neutral',
                negative: '😟 Negative',
                pending: '⏳ Pending'
            };
            return `<span class="sentiment-tag sentiment-${sentiment}">${map[sentiment] || sentiment}</span>`;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '—';
            const date = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(date)) return dateStr;
            return date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        function refreshAll() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const sentimentFilter = document.getElementById('sentimentFilter').value;

            let filtered = feedbackData.filter(f => {
                const matchesSearch = (f.comment || '').toLowerCase().includes(searchTerm);
                const matchesSentiment = sentimentFilter === 'all' || f.sentiment === sentimentFilter;
                return matchesSearch && matchesSentiment;
            });

            renderAnalytics(filtered);
            renderFeedbackTable(filtered);
        }

        document.getElementById('searchInput').addEventListener('input', refreshAll);
        document.getElementById('sentimentFilter').addEventListener('change', refreshAll);

        refreshAll();
    </script>
</body>
</html>