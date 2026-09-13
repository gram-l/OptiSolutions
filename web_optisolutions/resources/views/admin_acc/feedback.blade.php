<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <title>OptiSolutions - Feedback & Sentiment Analysis</title>
    @vite(['resources/css/admin_css/feedback.css', 'resources/css/admin_css/sidebar.css', 'resources/css/admin_css/header.css'])

    <style>
        /* ── Date filter inputs ── */
        .date-filter {
            border: 1px solid #d7dce3;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-dark);
        }
        .date-filter-sep {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        /* ── Download button + dropdown ── */
        .download-dropdown {
            position: relative;
            display: inline-block;
        }
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--primary-main);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: inherit;
            transition: background 0.2s ease, transform 0.1s ease;
        }
        .download-btn:hover { background: #0b4f8a; }
        .download-btn .chevron { font-size: 0.65rem; transition: transform 0.2s ease; }
        .download-dropdown.open .download-btn .chevron { transform: rotate(180deg); }
        .download-menu {
            display: none;
            position: absolute;
            top: calc(100% + 0.5rem);
            left: 0;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 8px 24px var(--shadow);
            overflow: hidden;
            min-width: 170px;
            z-index: 50;
        }
        .download-dropdown.open .download-menu { display: block; }
        .download-menu button {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            width: 100%;
            padding: 0.65rem 1rem;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            font-size: 0.85rem;
            font-family: inherit;
            color: var(--text-dark);
            transition: background 0.15s ease;
        }
        .download-menu button:hover { background: var(--light-gray); }
        .download-menu button i { width: 16px; color: var(--primary-main); }

        /* ── See More / See Less ── */
        .see-more-wrap {
            text-align: center;
            padding: 0.9rem;
            border-top: 1px solid var(--light-gray);
        }
        .see-more-btn {
            background: none;
            border: none;
            color: var(--primary-main);
            font-weight: 600;
            font-size: 0.85rem;
            font-family: inherit;
            cursor: pointer;
        }
        .see-more-btn:hover { text-decoration: underline; }

        /* ── Section header (title + per-table download button) ── */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        .section-title .download-btn {
            padding: 0.4rem 0.9rem;
            font-size: 0.8rem;
        }
    </style>
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
                    <input type="date" class="date-filter" id="dateFromFilter" title="From date">
                    <span class="date-filter-sep">to</span>
                    <input type="date" class="date-filter" id="dateToFilter" title="To date">
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
                <div class="section-title">
                    <span>All Patient Feedback</span>
                    <div class="download-dropdown" id="downloadDropdownFeedback">
                        <button type="button" class="download-btn" id="downloadBtnFeedback">
                            <i class="fa-solid fa-download"></i> Download <i class="fa-solid fa-chevron-down chevron"></i>
                        </button>
                        <div class="download-menu">
                            <button type="button" onclick="exportPDF('feedback')"><i class="fa-regular fa-file-pdf"></i> PDF</button>
                            <button type="button" onclick="exportCSV('feedback')"><i class="fa-regular fa-file-lines"></i> CSV</button>
                            <button type="button" onclick="exportExcel('feedback')"><i class="fa-regular fa-file-excel"></i> Excel</button>
                        </div>
                    </div>
                </div>
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
                <div class="see-more-wrap" id="feedbackSeeMoreWrap">
                    <button type="button" class="see-more-btn" id="feedbackSeeMoreBtn" onclick="toggleSeeMore('feedback')">See More</button>
                </div>
            </div>

            <!-- Complaint List -->
            <div class="feedback-section" style="margin-top: 2rem;">
                <div class="section-title">
                    <span>Complaint List</span>
                    <div class="download-dropdown" id="downloadDropdownComplaint">
                        <button type="button" class="download-btn" id="downloadBtnComplaint">
                            <i class="fa-solid fa-download"></i> Download <i class="fa-solid fa-chevron-down chevron"></i>
                        </button>
                        <div class="download-menu">
                            <button type="button" onclick="exportPDF('complaint')"><i class="fa-regular fa-file-pdf"></i> PDF</button>
                            <button type="button" onclick="exportCSV('complaint')"><i class="fa-regular fa-file-lines"></i> CSV</button>
                            <button type="button" onclick="exportExcel('complaint')"><i class="fa-regular fa-file-excel"></i> Excel</button>
                        </div>
                    </div>
                </div>
                <table class="feedback-table" id="complaintTable">
                    <thead>
                        <tr>
                            <th>Complaint</th>
                            <th>Category</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="complaintTableBody"></tbody>
                </table>
                <div class="see-more-wrap" id="complaintSeeMoreWrap">
                    <button type="button" class="see-more-btn" id="complaintSeeMoreBtn" onclick="toggleSeeMore('complaint')">See More</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const feedbackData = @json($feedbackData);
        const complaintData = @json($complaintData);

        const ROWS_BEFORE_SEE_MORE = 4;
        let feedbackShowAll = false;
        let complaintShowAll = false;

        function toggleSeeMore(section) {
            if (section === 'feedback') {
                feedbackShowAll = !feedbackShowAll;
                renderFeedbackTable(getFilteredData());
            } else {
                complaintShowAll = !complaintShowAll;
                renderComplaintTable(complaintData);
            }
        }

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
            const seeMoreWrap = document.getElementById('feedbackSeeMoreWrap');
            const seeMoreBtn = document.getElementById('feedbackSeeMoreBtn');

            if (filteredData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-state">No feedback entries found</td></tr>';
                seeMoreWrap.style.display = 'none';
                return;
            }

            const rowsToShow = feedbackShowAll ? filteredData : filteredData.slice(0, ROWS_BEFORE_SEE_MORE);

            tbody.innerHTML = rowsToShow.map(f => `
                <tr>
                    <td data-label="Rating" class="rating-stars">${renderStars(f.rating)}</td>
                    <td data-label="Comment" class="feedback-comment">${escapeHtml(f.comment)}</td>
                    <td data-label="Sentiment">${sentimentTag(f.sentiment)}</td>
                    <td data-label="Date">${formatDate(f.date)}</td>
                </tr>
            `).join('');

            if (filteredData.length > ROWS_BEFORE_SEE_MORE) {
                seeMoreWrap.style.display = 'block';
                seeMoreBtn.textContent = feedbackShowAll ? 'See Less' : 'See More';
            } else {
                seeMoreWrap.style.display = 'none';
            }
        }

        function renderComplaintTable(data) {
            const tbody = document.getElementById('complaintTableBody');
            const seeMoreWrap = document.getElementById('complaintSeeMoreWrap');
            const seeMoreBtn = document.getElementById('complaintSeeMoreBtn');

            if (!data || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="empty-state">No complaints found</td></tr>';
                seeMoreWrap.style.display = 'none';
                return;
            }

            const rowsToShow = complaintShowAll ? data : data.slice(0, ROWS_BEFORE_SEE_MORE);

            tbody.innerHTML = rowsToShow.map(c => `
                <tr>
                    <td data-label="Complaint" class="feedback-comment">${escapeHtml(c.complaint_text)}</td>
                    <td data-label="Category">${escapeHtml(c.category)}</td>
                    <td data-label="Date">${formatDate(c.created_at)}</td>
                </tr>
            `).join('');

            if (data.length > ROWS_BEFORE_SEE_MORE) {
                seeMoreWrap.style.display = 'block';
                seeMoreBtn.textContent = complaintShowAll ? 'See Less' : 'See More';
            } else {
                seeMoreWrap.style.display = 'none';
            }
        }

        function sentimentTag(sentiment) {
            if (sentiment === 'no_comment') {
                return '<span class="sentiment-tag sentiment-none">—</span>';
            }
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

        // Returns just the YYYY-MM-DD part of a feedback date, for range comparisons
        function toDateOnly(dateStr) {
            if (!dateStr) return null;
            const date = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(date)) return null;
            return date.toISOString().slice(0, 10);
        }

        function getFilteredData() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const sentimentFilter = document.getElementById('sentimentFilter').value;
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;

            return feedbackData.filter(f => {
                const matchesSearch = (f.comment || '').toLowerCase().includes(searchTerm);
                const matchesSentiment = sentimentFilter === 'all' || f.sentiment === sentimentFilter;

                const entryDate = toDateOnly(f.date);
                const matchesFrom = !dateFrom || (entryDate && entryDate >= dateFrom);
                const matchesTo = !dateTo || (entryDate && entryDate <= dateTo);

                return matchesSearch && matchesSentiment && matchesFrom && matchesTo;
            });
        }

        function refreshAll() {
            const filtered = getFilteredData();
            renderAnalytics(filtered);
            renderFeedbackTable(filtered);
            renderComplaintTable(complaintData);
        }

        document.getElementById('searchInput').addEventListener('input', refreshAll);
        document.getElementById('sentimentFilter').addEventListener('change', refreshAll);
        document.getElementById('dateFromFilter').addEventListener('change', refreshAll);
        document.getElementById('dateToFilter').addEventListener('change', refreshAll);

        /* ── Download dropdown ── */
        function toggleDownloadMenu(section) {
            document.getElementById(`downloadDropdown${section === 'feedback' ? 'Feedback' : 'Complaint'}`).classList.toggle('open');
        }

        function getExportRows(section) {
            if (section === 'complaint') {
                return complaintData.map(c => ({
                    Complaint: c.complaint_text || '',
                    Category: c.category || '',
                    Date: formatDate(c.created_at),
                }));
            }
            return getFilteredData().map(f => ({
                Rating: f.rating,
                Comment: f.comment || '',
                Sentiment: f.sentiment,
                Date: formatDate(f.date),
            }));
        }

        function downloadBlob(blob, filename) {
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function exportFilenameSuffix() {
            const dateFrom = document.getElementById('dateFromFilter').value;
            const dateTo = document.getElementById('dateToFilter').value;
            if (dateFrom || dateTo) {
                return `_${dateFrom || 'start'}_to_${dateTo || 'now'}`;
            }
            return `_${new Date().toISOString().slice(0, 10)}`;
        }

        function exportCSV(section) {
            const rows = getExportRows(section);
            if (rows.length === 0) { alert(`No ${section} to export for the selected filters.`); return; }
            const headers = Object.keys(rows[0]);
            const csvLines = [
                headers.join(','),
                ...rows.map(r => headers.map(h => `"${String(r[h]).replace(/"/g, '""')}"`).join(','))
            ];
            const blob = new Blob(["\ufeff" + csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            downloadBlob(blob, `${section}${exportFilenameSuffix()}.csv`);
            toggleDownloadMenu(section);
        }

        function exportExcel(section) {
            const rows = getExportRows(section);
            if (rows.length === 0) { alert(`No ${section} to export for the selected filters.`); return; }
            const ws = XLSX.utils.json_to_sheet(rows);
            ws['!cols'] = section === 'complaint'
                ? [{ wch: 50 }, { wch: 20 }, { wch: 20 }]
                : [{ wch: 10 }, { wch: 50 }, { wch: 14 }, { wch: 20 }];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, section === 'complaint' ? 'Complaints' : 'Feedback');
            XLSX.writeFile(wb, `${section}${exportFilenameSuffix()}.xlsx`);
            toggleDownloadMenu(section);
        }

        function exportPDF(section) {
            const rows = getExportRows(section);
            if (rows.length === 0) { alert(`No ${section} to export for the selected filters.`); return; }
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.setFontSize(14);
            doc.text(section === 'complaint' ? 'Complaint List' : 'Feedback & Sentiment Analysis', 14, 15);
            if (section === 'complaint') {
                doc.autoTable({
                    startY: 22,
                    head: [['Complaint', 'Category', 'Date']],
                    body: rows.map(r => [r.Complaint, r.Category, r.Date]),
                    styles: { fontSize: 9, cellPadding: 3 },
                    headStyles: { fillColor: [14, 98, 170] },
                    columnStyles: { 0: { cellWidth: 90 } },
                });
            } else {
                doc.autoTable({
                    startY: 22,
                    head: [['Rating', 'Comment', 'Sentiment', 'Date']],
                    body: rows.map(r => [r.Rating, r.Comment, r.Sentiment, r.Date]),
                    styles: { fontSize: 9, cellPadding: 3 },
                    headStyles: { fillColor: [14, 98, 170] },
                    columnStyles: { 1: { cellWidth: 80 } },
                });
            }
            doc.save(`${section}${exportFilenameSuffix()}.pdf`);
            toggleDownloadMenu(section);
        }

        document.getElementById('downloadBtnFeedback').addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDownloadMenu('feedback');
        });
        document.getElementById('downloadBtnComplaint').addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDownloadMenu('complaint');
        });
        document.addEventListener('click', (e) => {
            ['downloadDropdownFeedback', 'downloadDropdownComplaint'].forEach(id => {
                const dropdown = document.getElementById(id);
                if (dropdown.classList.contains('open') && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('open');
                }
            });
        });

        refreshAll();
    </script>
</body>
</html>