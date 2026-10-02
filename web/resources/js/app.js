import './bootstrap';
import Chart from 'chart.js/auto';

const sidebar = document.querySelector('[data-sidebar]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');

const setSidebarOpen = (open) => {
    if (!sidebar || !sidebarBackdrop) return;

    sidebar.classList.toggle('-translate-x-full', !open);
    sidebarBackdrop.classList.toggle('hidden', !open);
    document.body.classList.toggle('overflow-hidden', open);
    document.querySelector('[data-sidebar-open]')?.setAttribute('aria-expanded', String(open));

    if (open) {
        sidebar.querySelector('[data-sidebar-close]')?.focus();
    }
};

document.querySelectorAll('[data-sidebar-open]').forEach((button) => {
    button.addEventListener('click', () => setSidebarOpen(true));
});

document.querySelectorAll('[data-sidebar-close], [data-sidebar-backdrop]').forEach((button) => {
    button.addEventListener('click', () => setSidebarOpen(false));
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebarOpen(false);
});

const accountRole = document.querySelector('[data-account-role]');
const roleFieldGroups = document.querySelectorAll('[data-role-fields]');

const showRoleFields = () => {
    if (!accountRole) return;

    roleFieldGroups.forEach((group) => {
        const visible = group.dataset.roleFields === accountRole.value;
        group.classList.toggle('hidden', !visible);
        group.querySelectorAll('input, select').forEach((input) => {
            input.disabled = !visible;
        });
    });
};

accountRole?.addEventListener('change', showRoleFields);
showRoleFields();

document.querySelectorAll('[data-account-editor-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const editorId = button.dataset.accountEditorToggle;
        const editor = document.getElementById(editorId);
        if (!editor) return;

        const opening = editor.classList.contains('hidden');
        editor.classList.toggle('hidden');

        document.querySelectorAll(`[data-account-editor-toggle="${editorId}"]`).forEach((toggle) => {
            toggle.setAttribute('aria-expanded', String(opening));
        });

        if (opening) editor.querySelector('input')?.focus();
    });
});

document.querySelectorAll('[data-ai-generation-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-ai-generation-button]');
        const label = form.querySelector('[data-ai-generation-label]');

        if (button) button.disabled = true;
        if (label) label.textContent = 'Generating insights…';
    });
});

document.querySelectorAll('[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-loading-button]');
        if (!button) return;

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = form.dataset.loadingText || 'Working…';
    });
});

const palette = {
    cyan: '#06b6d4',
    indigo: '#6366f1',
    emerald: '#10b981',
    amber: '#f59e0b',
    rose: '#f43f5e',
    violet: '#8b5cf6',
    slate: '#94a3b8',
};

document.querySelectorAll('[data-chart-config]').forEach((canvas) => {
    const config = JSON.parse(canvas.dataset.chartConfig || '{}');

    const hasDoughnutValues = config.type !== 'doughnut'
        || (config.datasets?.[0]?.data || []).some((value) => Number(value) > 0);

    if (!config.labels?.length || !hasDoughnutValues) {
        canvas.closest('[data-chart-container]')?.classList.add('chart-empty');
        return;
    }

    const isDoughnut = config.type === 'doughnut';
    const datasets = (config.datasets || []).map((dataset, index) => {
        if (isDoughnut) {
            return {
                ...dataset,
                backgroundColor: dataset.colors || [palette.emerald, palette.amber, palette.rose, palette.slate],
                borderColor: '#ffffff',
                borderWidth: 4,
                hoverOffset: 5,
            };
        }

        const color = palette[dataset.color] || palette.cyan;
        const isBar = config.type === 'bar';

        return {
            ...dataset,
            borderColor: color,
            backgroundColor: isBar ? `${color}cc` : `${color}18`,
            borderWidth: 2.5,
            pointRadius: isBar ? 0 : 3,
            pointHoverRadius: 5,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: color,
            pointBorderWidth: 2,
            tension: 0.38,
            fill: !isBar && index === 0,
            borderRadius: isBar ? 8 : 0,
        };
    });

    new Chart(canvas, {
        type: config.type || 'line',
        data: { labels: config.labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 600 },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: config.legend !== false,
                    position: isDoughnut ? 'bottom' : 'top',
                    align: isDoughnut ? 'center' : 'end',
                    labels: {
                        color: '#64748b',
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 18,
                        font: { family: 'Instrument Sans', size: 12, weight: 600 },
                    },
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#f8fafc',
                    bodyColor: '#cbd5e1',
                    padding: 12,
                    cornerRadius: 10,
                },
            },
            scales: isDoughnut ? {} : {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } },
                    border: { display: false },
                },
                y: {
                    beginAtZero: true,
                    suggestedMax: 100,
                    grid: { color: '#e2e8f055' },
                    ticks: { color: '#94a3b8', stepSize: 20, font: { size: 11 } },
                    border: { display: false },
                },
            },
            cutout: isDoughnut ? '68%' : undefined,
        },
    });
});
