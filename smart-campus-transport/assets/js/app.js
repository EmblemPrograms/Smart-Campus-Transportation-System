/**
 * Smart Campus Transportation System - App JavaScript
 * Federal Polytechnic Ede
 *
 * Dark mode, sidebar toggle, toasts, notification polling,
 * confirm dialogs, table search, spinner helpers, Chart helpers.
 */
(function (window, $) {
    'use strict';

    const SCTS = window.SCTS || (window.SCTS = {});

    /* ---------- Theme / Dark mode ---------- */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.classList.toggle('theme-dark', theme === 'dark');
        const icon = document.getElementById('themeIcon');
        if (icon) {
            icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
        }
        try {
            localStorage.setItem('scts-theme', theme);
        } catch (e) {}
    }

    function initThemeToggle() {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;
        btn.addEventListener('click', function () {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            applyTheme(current === 'dark' ? 'light' : 'dark');
        });
        const saved = localStorage.getItem('scts-theme') || 'light';
        applyTheme(saved);
    }

    /* ---------- Sidebar (mobile) ---------- */
    function initSidebar() {
        const sidebar = document.getElementById('sctsSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('sidebarToggle');
        const closeBtn = document.getElementById('sidebarClose');

        function open() {
            if (sidebar) sidebar.classList.add('show');
            if (overlay) overlay.classList.add('show');
        }

        function close() {
            if (sidebar) sidebar.classList.remove('show');
            if (overlay) overlay.classList.remove('show');
        }

        if (openBtn) openBtn.addEventListener('click', open);
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (overlay) overlay.addEventListener('click', close);
    }

    /* ---------- Loading spinner ---------- */
    SCTS.showSpinner = function () {
        const el = document.getElementById('page-spinner');
        if (el) el.classList.remove('d-none');
    };

    SCTS.hideSpinner = function () {
        const el = document.getElementById('page-spinner');
        if (el) el.classList.add('d-none');
    };

    /* ---------- Toast notifications ---------- */
    SCTS.toast = function (message, type) {
        type = type || 'info';
        const map = {
            success: 'text-bg-success',
            error: 'text-bg-danger',
            danger: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info'
        };
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const el = document.createElement('div');
        el.className = 'toast align-items-center ' + (map[type] || map.info) + ' border-0';
        el.setAttribute('role', 'alert');
        el.innerHTML =
            '<div class="d-flex">' +
            '<div class="toast-body"></div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>';
        el.querySelector('.toast-body').textContent = message;
        container.appendChild(el);

        const toast = new bootstrap.Toast(el, { delay: 5000, autohide: true });
        toast.show();
        el.addEventListener('hidden.bs.toast', function () {
            el.remove();
        });
    };

    /* Show existing flash toasts from PHP */
    function initFlashToasts() {
        document.querySelectorAll('#toastContainer .toast').forEach(function (el) {
            const toast = new bootstrap.Toast(el, { delay: 5000, autohide: true });
            toast.show();
        });
    }

    /* ---------- Confirm dialogs ---------- */
    SCTS.confirm = function (message, onConfirm) {
        if (window.confirm(message)) {
            if (typeof onConfirm === 'function') onConfirm();
            return true;
        }
        return false;
    };

    $(document).on('click', '[data-confirm]', function (e) {
        const msg = $(this).data('confirm') || 'Are you sure?';
        if (!window.confirm(msg)) {
            e.preventDefault();
            return false;
        }
    });

    /* ---------- Table / list search (DataTables-like) ---------- */
    SCTS.bindTableSearch = function (inputSelector, tableSelector) {
        $(document).on('keyup', inputSelector, function () {
            const q = $(this).val().toString().toLowerCase();
            $(tableSelector + ' tbody tr').each(function () {
                const text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(q) !== -1);
            });
        });
    };

    /* ---------- AJAX helpers ---------- */
    SCTS.ajaxHeaders = function () {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': SCTS.csrfToken || ''
        };
    };

    SCTS.post = function (url, data) {
        data = data || {};
        data.csrf_token = SCTS.csrfToken;
        return $.ajax({
            url: url,
            method: 'POST',
            data: data,
            dataType: 'json',
            headers: SCTS.ajaxHeaders()
        });
    };

    SCTS.get = function (url, data) {
        return $.ajax({
            url: url,
            method: 'GET',
            data: data || {},
            dataType: 'json',
            headers: SCTS.ajaxHeaders()
        });
    };

    /* ---------- Notification polling ---------- */
    function renderNotifications(items) {
        const list = $('#notifList');
        if (!list.length) return;

        if (!items || !items.length) {
            list.html('<div class="text-center text-muted py-3 small">No new notifications</div>');
            return;
        }

        let html = '';
        items.forEach(function (n) {
            const unread = parseInt(n.is_read, 10) === 0 ? 'unread' : '';
            const href = n.link || '#';
            html +=
                '<a href="' + href + '" class="notif-item ' + unread + '" data-id="' + n.id + '">' +
                '<div class="notif-title"></div>' +
                '<div class="small text-muted"></div>' +
                '<div class="notif-time"></div>' +
                '</a>';
        });
        list.html(html);
        list.find('.notif-item').each(function (i) {
            const n = items[i];
            $(this).find('.notif-title').text(n.title || '');
            $(this).find('.small').text(n.message || '');
            $(this).find('.notif-time').text(n.time_ago || n.created_at || '');
        });
    }

    function updateNotifBadge(count) {
        const badge = $('#notifBadge');
        if (!badge.length) return;
        if (count > 0) {
            badge.text(count > 99 ? '99+' : count).removeClass('d-none');
        } else {
            badge.addClass('d-none').text('0');
        }
    }

    function pollNotifications() {
        if (!SCTS.ajax || !SCTS.ajax.notifications) return;
        SCTS.get(SCTS.ajax.notifications, { action: 'unread' })
            .done(function (res) {
                if (!res || !res.success) return;
                updateNotifBadge(res.count || 0);
                renderNotifications(res.notifications || []);
            })
            .fail(function () {});
    }

    function initNotifications() {
        pollNotifications();
        setInterval(pollNotifications, 30000);

        $(document).on('click', '#markAllReadBtn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            SCTS.post(SCTS.ajax.notifications, { action: 'mark_all' })
                .done(function (res) {
                    if (res && res.success) {
                        updateNotifBadge(0);
                        pollNotifications();
                        SCTS.toast('All notifications marked as read', 'success');
                    }
                });
        });

        $(document).on('click', '#notifList .notif-item', function () {
            const id = $(this).data('id');
            if (!id) return;
            SCTS.post(SCTS.ajax.notifications, { action: 'mark_read', id: id });
        });
    }

    /* ---------- Chart helpers ---------- */
    SCTS.chartColors = {
        primary: '#0d7a5f',
        accent: '#1aa88a',
        amber: '#f59e0b',
        red: '#dc2626',
        blue: '#0ea5e9',
        slate: '#64748b',
        palette: ['#0d7a5f', '#1aa88a', '#0ea5e9', '#f59e0b', '#dc2626', '#8b5cf6', '#64748b', '#14b8a6']
    };

    SCTS.initLineChart = function (canvasId, labels, data, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return null;
        return new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label || 'Series',
                    data: data,
                    borderColor: SCTS.chartColors.primary,
                    backgroundColor: 'rgba(13, 122, 95, 0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: SCTS.chartColors.primary
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    };

    SCTS.initDoughnutChart = function (canvasId, labels, data) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return null;
        return new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: SCTS.chartColors.palette,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    };

    SCTS.initBarChart = function (canvasId, labels, data, label) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return null;
        return new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label || 'Series',
                    data: data,
                    backgroundColor: 'rgba(13, 122, 95, 0.75)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    };

    /* ---------- Export helpers (print / CSV) ---------- */
    SCTS.printReport = function (selector) {
        const el = document.querySelector(selector || '#reportResults');
        if (!el) {
            window.print();
            return;
        }
        const w = window.open('', '_blank');
        w.document.write('<html><head><title>Report</title>');
        w.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">');
        w.document.write('</head><body class="p-4">');
        w.document.write(el.innerHTML);
        w.document.write('</body></html>');
        w.document.close();
        w.focus();
        setTimeout(function () { w.print(); }, 400);
    };

    SCTS.exportTableCsv = function (tableSelector, filename) {
        const rows = [];
        $(tableSelector + ' tr').each(function () {
            const cols = [];
            $(this).find('th,td').each(function () {
                let t = $(this).text().replace(/\s+/g, ' ').trim();
                t = '"' + t.replace(/"/g, '""') + '"';
                cols.push(t);
            });
            rows.push(cols.join(','));
        });
        const blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename || 'report.csv';
        link.click();
    };

    /* ---------- Bootstrap ---------- */
    $(function () {
        initThemeToggle();
        initSidebar();
        initFlashToasts();
        initNotifications();
        SCTS.bindTableSearch('#tableSearch', '.table-filterable');
    });
})(window, jQuery);
