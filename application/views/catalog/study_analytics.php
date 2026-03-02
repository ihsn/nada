<?php
/**
 * Study-level Analytics Tab
 *
 * Variables in scope (extracted from $survey_row by CI):
 *   $survey_id      - numeric survey ID (also the analytics study_id)
 *   $idno           - string IDNO
 *   $title          - survey title
 *   $total_views    - all-time views synced from analytics (surveys.total_views)
 *   $total_downloads- all-time downloads synced from analytics (surveys.total_downloads)
 */
$_sid           = (int) $survey_id;
$_total_views   = (int) ($total_views ?? 0);
$_total_downloads = (int) ($total_downloads ?? 0);
?>

<?php /* Load Vue2, Chart.js, and Axios once, guarded against double-load */ ?>
<script>
if (typeof Vue === 'undefined') {
    document.write('<script src="<?php echo base_url(); ?>javascript/vue.min.js"><\/script>');
}
</script>
<script>
if (typeof Chart === 'undefined') {
    document.write('<script src="<?php echo base_url(); ?>javascript/chart.min.js"><\/script>');
}
</script>
<script>
if (typeof axios === 'undefined') {
    document.write('<script src="<?php echo base_url(); ?>javascript/axios.min.js"><\/script>');
}
</script>

<style>
#study-analytics-app .sa-kpi-card        { border-top: 3px solid #5bc0de; background: #fff; border-radius: 4px; padding: 14px 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
#study-analytics-app .sa-kpi-card.views  { border-top-color: #337ab7; }
#study-analytics-app .sa-kpi-card.dl     { border-top-color: #d9534f; }
#study-analytics-app .sa-kpi-value       { font-size: 1.75rem; font-weight: 600; line-height: 1.2; color: #333; }
#study-analytics-app .sa-kpi-label       { font-size: 1rem; color: #888; text-transform: uppercase; letter-spacing: .4px; margin-top: 4px; }
#study-analytics-app .sa-chart-wrap      { position: relative; height: 220px; }
#study-analytics-app .sa-section-title   { font-size: 1.05rem; font-weight: 600; color: #555; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 6px; }
#study-analytics-app table.sa-table      { font-size: 1rem; }
#study-analytics-app table.sa-table th   { background: #f7f7f7; font-weight: 600; }
#study-analytics-app .sa-empty           { color: #aaa; font-style: italic; font-size: 1rem; padding: 12px 0; }
#study-analytics-app .sa-loading         { color: #888; font-size: 1rem; padding: 10px 0; }
#study-analytics-app .sa-badge-views     { background: #337ab7; color: #fff; border-radius: 3px; padding: 2px 8px; font-size: 0.9rem; }
#study-analytics-app .sa-badge-dl        { background: #d9534f; color: #fff; border-radius: 3px; padding: 2px 8px; font-size: 0.9rem; }
</style>

<div id="study-analytics-app">

    <!-- KPI row -->
    <div class="row">
        <div class="col-sm-6 col-md-3">
            <div class="sa-kpi-card views">
                <div class="sa-kpi-value"><?php echo number_format($_total_views); ?></div>
                <div class="sa-kpi-label">All-time views</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="sa-kpi-card dl">
                <div class="sa-kpi-value"><?php echo number_format($_total_downloads); ?></div>
                <div class="sa-kpi-label">All-time downloads</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="sa-kpi-card views">
                <div class="sa-kpi-value">
                    <span v-if="loading">…</span>
                    <span v-else>{{ curMonth.pageviews.toLocaleString() }}</span>
                </div>
                <div class="sa-kpi-label">Views this month</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="sa-kpi-card dl">
                <div class="sa-kpi-value">
                    <span v-if="loading">…</span>
                    <span v-else>{{ curMonth.downloads.toLocaleString() }}</span>
                </div>
                <div class="sa-kpi-label">Downloads this month</div>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="row" style="margin-top:8px;">
        <div class="col-md-12">
            <div style="background:#fff; border:1px solid #e8e8e8; border-radius:4px; padding:14px; margin-bottom:16px;">
                <div class="sa-section-title">Monthly Trend</div>
                <div v-if="loading" class="sa-loading">Loading chart data…</div>
                <div v-else-if="monthlyData.length === 0" class="sa-empty">No monthly data recorded yet for this study.</div>
                <div v-else class="sa-chart-wrap">
                    <canvas id="sa-trend-chart-<?php echo $_sid; ?>"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly breakdown (full width) -->
    <div class="row">
        <div class="col-md-12">
            <div style="background:#fff; border:1px solid #e8e8e8; border-radius:4px; padding:14px; margin-bottom:16px;">
                <div class="sa-section-title" style="display:flex; align-items:center; justify-content:space-between;">
                    <span>Monthly Breakdown</span>
                    <span style="font-weight:400; font-size:0.85rem;">
                        <a href="<?php echo site_url('api/analytics/monthly/studies/export?study_id=' . $_sid . '&format=csv'); ?>" class="btn btn-default btn-xs" title="Export as CSV"><i class="fa fa-download"></i> CSV</a>
                        <a href="<?php echo site_url('api/analytics/monthly/studies/export?study_id=' . $_sid . '&format=json'); ?>" class="btn btn-default btn-xs" title="Export as JSON"><i class="fa fa-download"></i> JSON</a>
                    </span>
                </div>
                <div v-if="loading" class="sa-loading">Loading…</div>
                <div v-else-if="monthlyData.length === 0" class="sa-empty">No data yet.</div>
                <table v-else class="table table-condensed table-hover sa-table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th class="text-right">Views</th>
                            <th class="text-right">Unique Visitors</th>
                            <th class="text-right">Downloads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in monthlyTableRows" :key="row.year + '-' + row.month">
                            <td>{{ row.label }}</td>
                            <td class="text-right"><span class="sa-badge-views">{{ row.pageviews.toLocaleString() }}</span></td>
                            <td class="text-right">{{ row.unique_visitors.toLocaleString() }}</td>
                            <td class="text-right"><span class="sa-badge-dl">{{ row.downloads.toLocaleString() }}</span></td>
                        </tr>
                    </tbody>
                    <tfoot v-if="monthlyTableRows.length > 0">
                        <tr style="font-weight:600; background:#f7f7f7;">
                            <td>Total</td>
                            <td class="text-right">{{ monthlyTotal.pageviews.toLocaleString() }}</td>
                            <td class="text-right">―</td>
                            <td class="text-right">{{ monthlyTotal.downloads.toLocaleString() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- File downloads report (full width) -->
    <div class="row">
        <div class="col-md-12">
            <div style="background:#fff; border:1px solid #e8e8e8; border-radius:4px; padding:14px; margin-bottom:16px;">
                <div class="sa-section-title" style="display:flex; align-items:center; justify-content:space-between;">
                    <span>File Downloads</span>
                    <span style="font-weight:400; font-size:0.85rem;">
                        <a href="<?php echo site_url('api/analytics/monthly/files/export?study_id=' . $_sid . '&format=csv'); ?>" class="btn btn-default btn-xs" title="Export as CSV"><i class="fa fa-download"></i> CSV</a>
                        <a href="<?php echo site_url('api/analytics/monthly/files/export?study_id=' . $_sid . '&format=json'); ?>" class="btn btn-default btn-xs" title="Export as JSON"><i class="fa fa-download"></i> JSON</a>
                    </span>
                </div>
                <div v-if="filesLoading" class="sa-loading">Loading…</div>
                <div v-else-if="fileDownloads.length === 0" class="sa-empty">No file download data yet.</div>
                <table v-else class="table table-condensed table-hover sa-table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th class="text-right">Total Downloads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="f in fileDownloads" :key="f.file_name">
                            <td style="word-break:break-all;" :title="f.file_name">{{ f.file_name }}</td>
                            <td class="text-right"><span class="sa-badge-dl">{{ f.downloads.toLocaleString() }}</span></td>
                        </tr>
                    </tbody>
                    <tfoot v-if="fileDownloads.length > 0">
                        <tr style="font-weight:600; background:#f7f7f7;">
                            <td>Total</td>
                            <td class="text-right">{{ fileDownloadsTotal.toLocaleString() }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div><!-- #study-analytics-app -->

<script>
(function() {
    var STUDY_ID   = <?php echo $_sid; ?>;
    var API_BASE   = (typeof CI !== 'undefined' ? CI.base_url : '') + '/api/analytics';
    var CHART_ID   = 'sa-trend-chart-' + STUDY_ID;
    var MONTH_NAMES = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function periodLabel(year, month) {
        return MONTH_NAMES[month - 1] + ' ' + year;
    }

    // Guard: only mount if Vue is available and not already mounted
    function mountApp() {
        if (typeof Vue === 'undefined' || typeof axios === 'undefined' || typeof Chart === 'undefined') {
            setTimeout(mountApp, 80);
            return;
        }
        if (document.getElementById('study-analytics-app').__vue__) {
            return; // already mounted (e.g. hot reload)
        }

        new Vue({
            el: '#study-analytics-app',

            data: {
                loading: true,
                filesLoading: true,
                monthlyData: [],     // raw rows from /monthly/studies
                filesData: [],       // raw rows from /monthly/files
                curMonth: { pageviews: 0, downloads: 0 },
                trendChart: null
            },

            computed: {
                // Show only months with data, sorted newest-first for table
                monthlyTableRows: function() {
                    return this.monthlyData.slice().reverse().map(function(r) {
                        return {
                            year:            r.year,
                            month:           r.month,
                            label:           periodLabel(r.year, r.month),
                            pageviews:       parseInt(r.pageviews)       || 0,
                            unique_visitors: parseInt(r.unique_visitors) || 0,
                            downloads:       parseInt(r.downloads)       || 0
                        };
                    });
                },

                monthlyTotal: function() {
                    return this.monthlyTableRows.reduce(function(acc, r) {
                        acc.pageviews  += r.pageviews;
                        acc.downloads  += r.downloads;
                        return acc;
                    }, { pageviews: 0, downloads: 0 });
                },

                // Aggregate file downloads across all months, all files sorted by total desc
                fileDownloads: function() {
                    var map = {};
                    this.filesData.forEach(function(r) {
                        var fn = r.file_name || '(unknown)';
                        if (!map[fn]) { map[fn] = 0; }
                        map[fn] += parseInt(r.downloads) || 0;
                    });
                    return Object.keys(map)
                        .map(function(fn) { return { file_name: fn, downloads: map[fn] }; })
                        .sort(function(a, b) { return b.downloads - a.downloads; });
                },

                fileDownloadsTotal: function() {
                    return this.fileDownloads.reduce(function(sum, f) { return sum + f.downloads; }, 0);
                }
            },

            mounted: function() {
                this.loadMonthly();
                this.loadFiles();
            },

            beforeDestroy: function() {
                if (this.trendChart) { this.trendChart.destroy(); }
            },

            methods: {
                loadMonthly: function() {
                    var self = this;
                    axios.get(API_BASE + '/monthly/studies', { params: { study_id: STUDY_ID, limit: 500 } })
                        .then(function(resp) {
                            if (resp.data && resp.data.status === 'success') {
                                var rows = (resp.data.data || [])
                                    .filter(function(r) { return r.year != 0 || r.month != 0; })
                                    .sort(function(a, b) {
                                        return (a.year * 100 + a.month) - (b.year * 100 + b.month);
                                    });

                                self.monthlyData = rows;
                                self.setCurMonth(rows);
                            }
                        })
                        .catch(function(err) { console.error('Analytics monthly load error', err); })
                        .finally(function() {
                            // Set loading=false first so v-if reveals the canvas,
                            // then wait for Vue's DOM update before rendering the chart.
                            self.loading = false;
                            self.$nextTick(function() { self.renderChart(self.monthlyData); });
                        });
                },

                loadFiles: function() {
                    var self = this;
                    axios.get(API_BASE + '/monthly/files', { params: { study_id: STUDY_ID, limit: 500 } })
                        .then(function(resp) {
                            if (resp.data && resp.data.status === 'success') {
                                self.filesData = (resp.data.data || [])
                                    .filter(function(r) { return r.year != 0 || r.month != 0; });
                            }
                        })
                        .catch(function(err) { console.error('Analytics files load error', err); })
                        .finally(function() { self.filesLoading = false; });
                },

                setCurMonth: function(rows) {
                    var found = null;
                    for (var i = rows.length - 1; i >= 0; i--) {
                        if ((rows[i].pageviews > 0 || rows[i].downloads > 0)) {
                            found = rows[i];
                            break;
                        }
                    }
                    if (found) {
                        this.curMonth = {
                            pageviews: parseInt(found.pageviews) || 0,
                            downloads: parseInt(found.downloads) || 0
                        };
                    }
                },

                renderChart: function(rows) {
                    var canvas = document.getElementById(CHART_ID);
                    if (!canvas || rows.length === 0) { return; }
                    if (this.trendChart) { this.trendChart.destroy(); }

                    var labels    = rows.map(function(r) { return periodLabel(r.year, r.month); });
                    var pageviews = rows.map(function(r) { return parseInt(r.pageviews)  || 0; });
                    var downloads = rows.map(function(r) { return parseInt(r.downloads)  || 0; });

                    this.trendChart = new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Pageviews',
                                    data: pageviews,
                                    borderColor: 'rgba(51,122,183,1)',
                                    backgroundColor: 'rgba(51,122,183,0.10)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Downloads',
                                    data: downloads,
                                    borderColor: 'rgba(217,83,79,1)',
                                    backgroundColor: 'rgba(217,83,79,0.08)',
                                    borderWidth: 2,
                                    pointRadius: 4,
                                    pointHoverRadius: 6,
                                    tension: 0.3,
                                    fill: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'top' }
                            },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
                            }
                        }
                    });
                }
            }
        });
    }

    // Boot after DOM is ready (we're inside a tab that's already rendered)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountApp);
    } else {
        mountApp();
    }
})();
</script>
