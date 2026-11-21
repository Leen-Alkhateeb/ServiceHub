<script>
(async function () {
    const bookingsCanvas = document.getElementById('bookingsChart');
    const statusCanvas = document.getElementById('statusChart');
    const bookings = @json($bookingChart);
    const statuses = @json($statusChart);

    // Booking statuses in Arabic for the doughnut chart legend.
    const statusNames = {
        pending: 'قيد الانتظار',
        awaiting_payment: 'بانتظار العربون',
        approved: 'مقبول',
        confirmed: 'مؤكد',
        paid: 'مدفوع',
        in_progress: 'قيد التنفيذ',
        completed: 'مكتمل',
        cancelled: 'ملغي',
        refunded: 'مسترد',
        objection_pending: 'اعتراض قيد المراجعة',
        rejected: 'مرفوض',
        expired: 'منتهي',
        closed: 'مغلق',
    };

    if (!window.Chart) {
        window.serviceHubChartLibrary ??= new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.addEventListener('load', resolve, { once: true });
            script.addEventListener('error', reject, { once: true });
            document.head.append(script);
        });

        try {
            await window.serviceHubChartLibrary;
        } catch {
            delete window.serviceHubChartLibrary;
            if (bookingsCanvas.isConnected) {
                for (const canvas of [bookingsCanvas, statusCanvas]) {
                    const message = document.createElement('p');
                    message.textContent = 'تعذّر تحميل الرسم البياني.';
                    canvas.replaceWith(message);
                }
            }
            return;
        }
    }

    if (!bookingsCanvas.isConnected || !statusCanvas.isConnected) return;
    const Chart = window.Chart;
    Chart.defaults.font.family = "'IBM Plex Sans Arabic', 'Cairo', sans-serif";

    const bookingsChart = new Chart(bookingsCanvas, {
        type: 'line',
        data: {
            labels: bookings.labels,
            datasets: [{
                label: 'الحجوزات',
                data: bookings.values,
                borderColor: '#202124',
                backgroundColor: 'rgba(255,210,31,.20)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#FFD21F'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    const statusChart = statuses.values.length ? new Chart(statusCanvas, {
        type: 'doughnut',
        data: {
            labels: statuses.labels.map(function (status) {
                return statusNames[status] || status;
            }),
            datasets: [{
                data: statuses.values,
                backgroundColor: [
                    '#FFD21F',
                    '#202124',
                    '#0B6B52',
                    '#2457C5',
                    '#B42318',
                    '#7A4B00',
                    '#9CA3AF',
                    '#7C3AED'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 16
                    }
                }
            }
        }
    }) : null;
    const events = new AbortController();
    const dispose = () => {
        bookingsChart.destroy();
        statusChart?.destroy();
        events.abort();
    };
    document.addEventListener('turbo:before-cache', dispose, { signal: events.signal });
    document.addEventListener('turbo:before-render', dispose, { signal: events.signal });
})();
</script>
