/* School Softwere - Chart.js initializers
 * Initializes any canvas with [data-ss-chart] using Chart.js.
 *
 * Expected attributes:
 *   data-ss-chart    = "line|bar|doughnut|pie"
 *   data-ss-labels   = JSON string of labels array
 *   data-ss-datasets = JSON string of datasets array
 */
(function () {
    'use strict';

    function init(canvas) {
        if (typeof Chart === 'undefined') { return; }
        var type     = canvas.getAttribute('data-ss-chart') || 'line';
        var labels   = JSON.parse(canvas.getAttribute('data-ss-labels')   || '[]');
        var datasets = JSON.parse(canvas.getAttribute('data-ss-datasets') || '[]');
        var opts = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { font: { family: 'Inter' }, usePointStyle: true } },
                tooltip: { backgroundColor: '#1E1B4B', padding: 10, cornerRadius: 8 }
            },
            scales: (type === 'doughnut' || type === 'pie') ? {} : {
                y: { beginAtZero: true, grid: { color: '#E0E7FF' } },
                x: { grid: { display: false } }
            }
        };
        if (type === 'line') {
            datasets.forEach(function (d) {
                d.tension     = 0.4;
                d.fill        = true;
                d.borderColor = d.borderColor || '#4F46E5';
                d.backgroundColor = d.backgroundColor || 'rgba(79,70,229,0.10)';
                d.pointRadius = 3;
                d.pointBackgroundColor = '#4F46E5';
            });
        }
        if (type === 'bar') {
            datasets.forEach(function (d) {
                d.backgroundColor = d.backgroundColor || ['#4F46E5','#0EA5E9','#10B981','#F59E0B','#EF4444','#6366F1'];
                d.borderRadius = 6;
            });
        }
        if (type === 'doughnut' || type === 'pie') {
            datasets.forEach(function (d) {
                d.backgroundColor = d.backgroundColor || ['#10B981','#F59E0B','#EF4444','#4F46E5','#0EA5E9','#6366F1'];
            });
        }
        new Chart(canvas, { type: type, data: { labels: labels, datasets: datasets }, options: opts });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var nodes = document.querySelectorAll('canvas[data-ss-chart]');
        nodes.forEach(init);
    });
})();
