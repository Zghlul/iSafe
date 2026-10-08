import './bootstrap';

import '@fontsource/manrope/latin-400.css';
import '@fontsource/manrope/latin-500.css';
import '@fontsource/manrope/latin-600.css';
import '@fontsource/manrope/latin-700.css';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

window.salesPage = (initial, stockSearchUrl, openOnLoad = false, allowManual = true, initialStocks = []) => ({
    drawerOpen: openOnLoad,
    sale: { ...initial },
    unitMode: initial.unit_mode ?? 'stock',
    allowManual,
    selectedStock: initial.stock ?? null,
    stockQuery: '',
    stockResults: initialStocks,
    stockRequestId: 0,

    init() {
        this.$nextTick(() => this.normalizePriceInputs());
    },

    get profit() {
        return (Number(this.sale.selling_price) || 0) - (Number(this.selectedStock?.cost_price ?? this.sale.cost_price) || 0);
    },

    openDrawer(record = null) {
        this.sale = record ? { ...record } : {
            id: null,
            sale_date: new Date().toLocaleDateString('en-CA'),
            seller_name: '',
            buyer_name: '',
            buyer_phone: '',
            selling_price: '',
            payment_method: 'transfer',
            notes: '',
        };
        this.selectedStock = record?.stock ?? null;
        this.unitMode = 'stock';
        this.stockQuery = '';
        this.stockResults = [];
        this.drawerOpen = true;
        this.$nextTick(() => this.normalizePriceInputs());
    },

    normalizePriceInputs() {
        for (const field of ['selling_price', 'cost_price']) {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) input.value = this.formatDigits(input.value);
        }
    },

    formatDigits(value) {
        const digits = String(value ?? '').replace(/\D/g, '');
        return digits ? new Intl.NumberFormat('id-ID').format(digits) : '';
    },

    formatPrice(event, field) {
        const digits = event.target.value.replace(/\D/g, '');
        this.sale[field] = digits;
        event.target.value = this.formatDigits(digits);
    },

    formatRupiah(value) {
        const number = Number(value) || 0;
        const sign = number < 0 ? '−' : '';
        return `${sign}Rp ${new Intl.NumberFormat('id-ID').format(Math.abs(number))}`;
    },

    async searchStocks() {
        const query = this.stockQuery.trim();
        const requestId = ++this.stockRequestId;
        if (query.length < 2) {
            this.stockResults = [];
            return;
        }
        try {
            const url = new URL(stockSearchUrl, window.location.origin);
            url.searchParams.set('q', query);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Pencarian stok gagal.');
            const results = await response.json();
            if (requestId === this.stockRequestId) this.stockResults = results;
        } catch {
            if (requestId === this.stockRequestId) this.stockResults = [];
        }
    },

    chooseStock(stock) {
        this.selectedStock = stock;
        this.stockResults = [];
        this.stockQuery = '';
    },

    async prepareSubmit(event) {
        if (this.unitMode === 'stock' && !this.selectedStock) {
            event.preventDefault();
            return;
        }
        for (const field of ['selling_price', 'cost_price']) {
            const input = event.target.querySelector(`[name="${field}"]:not(:disabled)`);
            if (input) input.value = input.value.replace(/\D/g, '');
        }
    },

    selectAll(event) {
        document.querySelectorAll('.sale-selection').forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });
    },
});

window.dashboardCharts = (revenue, profit, payments) => ({
    init() {
        const css = getComputedStyle(document.documentElement);
        const token = (name) => css.getPropertyValue(`--color-${name}`).trim();
        const primary = token('primary');
        const accent = token('accent');
        const green = token('accent-dark');
        const amber = token('chart-amber');
        const slate = token('chart-slate');
        const grid = token('border');
        const muted = token('text-muted');
        const number = (value) => new Intl.NumberFormat('id-ID').format(value);
        const labelOptions = {
            color: muted,
            font: { family: 'Manrope', size: 11 },
        };

        const revenueCanvas = document.getElementById('revenue-chart');
        if (revenueCanvas) {
            new Chart(revenueCanvas, {
                data: {
                    labels: revenue.labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Omzet',
                            data: revenue.revenue,
                            backgroundColor: accent,
                            borderRadius: 4,
                            yAxisID: 'money',
                        },
                        {
                            type: 'line',
                            label: 'Margin %',
                            data: revenue.margin,
                            borderColor: primary,
                            backgroundColor: primary,
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointBackgroundColor: '#FFFFFF',
                            pointBorderColor: primary,
                            yAxisID: 'percent',
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (context) => `${context.dataset.label}: ${context.dataset.yAxisID === 'percent' ? `${number(context.raw)}%` : `Rp ${number(context.raw)}`}` } },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: labelOptions },
                        money: { beginAtZero: true, grid: { color: grid }, ticks: { ...labelOptions, callback: (value) => `Rp ${number(value)}` } },
                        percent: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { ...labelOptions, callback: (value) => `${number(value)}%` } },
                    },
                },
            });

        }

        const profitCanvas = document.getElementById('profit-chart');
        if (profitCanvas) {
            new Chart(profitCanvas, {
                type: 'line',
                data: {
                    labels: profit.labels,
                    datasets: [
                        { label: 'iPhone Baru', data: profit.new, borderColor: green, backgroundColor: `${green}30`, fill: true, tension: 0.2, pointRadius: 2 },
                        { label: 'iPhone Second', data: profit.used, borderColor: amber, backgroundColor: `${amber}30`, fill: true, tension: 0.2, pointRadius: 2 },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => `${context.dataset.label}: Rp ${number(context.raw)}` } } },
                    scales: {
                        x: { grid: { display: false }, ticks: labelOptions },
                        y: { grid: { color: grid }, ticks: { ...labelOptions, callback: (value) => `Rp ${number(value)}` } },
                    },
                },
            });
        }

        const paymentCanvas = document.getElementById('payment-chart');
        if (paymentCanvas) {
            new Chart(paymentCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Transfer', 'Tunai', 'Cicilan', 'Lainnya'],
                    datasets: [{ data: [payments.transfer, payments.cash, payments.installment, payments.other], backgroundColor: [primary, accent, amber, slate], borderWidth: 0, hoverOffset: 0 }],
                },
                options: {
                    cutout: '68%',
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: (context) => `${context.label}: ${number(context.raw)}` } },
                    },
                },
            });
        }
    },
});

window.stockPage = (imeiCheckUrl, openOnLoad = false, initialImei = '') => ({
    drawerOpen: openOnLoad,
    mode: 'single',
    condition: 'new',
    imei: initialImei,
    imeiStatus: '',
    imeiMessage: '',
    checkedImei: '',
    requestId: 0,

    init() {
        if (this.drawerOpen && this.imei) this.checkImei();
    },

    openDrawer() {
        this.mode = 'single';
        this.condition = 'new';
        this.imei = '';
        this.imeiStatus = '';
        this.imeiMessage = '';
        this.checkedImei = '';
        this.drawerOpen = true;
    },

    async checkImei() {
        const imei = String(this.imei ?? '');
        const requestId = ++this.requestId;
        this.checkedImei = '';

        if (!/^\d{15}$/.test(imei)) {
            this.imeiStatus = imei ? 'invalid' : '';
            this.imeiMessage = imei ? 'Masukkan tepat 15 digit angka.' : '';
            this.checkedImei = imei;
            return;
        }

        try {
            const url = new URL(imeiCheckUrl, window.location.origin);
            url.searchParams.set('imei', imei);
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('Pemeriksaan IMEI gagal.');
            const result = await response.json();
            if (requestId !== this.requestId) return;
            this.checkedImei = imei;
            this.imeiStatus = result.status;
            this.imeiMessage = ({
                valid: 'IMEI valid dan belum tercatat.',
                checksum: 'IMEI tidak lolos pemeriksaan checksum.',
                duplicate: 'IMEI ini sudah digunakan pada unit stok lain.',
                deleted: `IMEI ada pada unit terhapus #${result.id}. Pulihkan unit tersebut.`,
                invalid: 'Masukkan tepat 15 digit angka.',
            })[result.status] ?? '';
        } catch {
            if (requestId !== this.requestId) return;
            this.imeiStatus = 'error';
            this.imeiMessage = 'IMEI belum dapat diperiksa. Coba lagi.';
        }
    },

    async prepareSubmit(event) {
        if (this.mode === 'bulk') return;
        const imei = String(this.imei ?? '');
        if (this.checkedImei !== imei) await this.checkImei();
        if (this.checkedImei !== imei || this.imeiStatus !== 'valid') event.preventDefault();
    },

    selectAll(event) {
        document.querySelectorAll('.stock-selection:not(:disabled)').forEach((checkbox) => {
            checkbox.checked = event.target.checked;
        });
    },
});

Alpine.start();
