import { Line } from 'vue-chartjs';
import { formatDate, formatMno } from '@/modules/shared/utils/formatters';
import {
    Chart as ChartJS,
    registerables
} from 'chart.js';

ChartJS.register(...registerables);

// Проверка: есть ли доза у точки (не null/undefined/'—'/''/NaN/0).
// Дозы варфарина — доли таблетки (от четвертины и выше), 0/NaN назначением не являются.
export function hasDoseValue(dose) {
    if (dose === null || dose === undefined || dose === '—' || dose === '') return false;
    const num = Number(dose);
    return !Number.isNaN(num) && num > 0;
}

// Кастомный плагин для подписей вне диапазона.
// МНО и доза выводятся отдельными строками друг под другом (чтобы не наползали).
const LINE_HEIGHT = 12;
const LABEL_OFFSET = 15;

const customLabelsPlugin = {
    id: 'customLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0);
        const mnoFrom = chart.options.mnoFrom;
        const mnoTo = chart.options.mnoTo;
        const doses = chart.options.doses || [];

        if (!meta || !meta.data) return;

        ctx.font = 'bold 10px Arial';
        ctx.textAlign = 'center';

        meta.data.forEach((point, index) => {
            const value = chart.data.datasets[0].data[index];
            if (value === null || value === undefined) return;

            const below = mnoFrom !== null && value < mnoFrom;
            const above = mnoTo !== null && value > mnoTo;
            if (!below && !above) return;

            const dose = doses[index];
            const hasDose = hasDoseValue(dose);
            const x = point.x;
            ctx.fillStyle = below ? '#2a5c98' : '#e74c3c';

            if (below) {
                // ниже диапазона — подпись под точкой: МНО, затем доза
                ctx.fillText(String(value), x, point.y + LABEL_OFFSET);
                if (hasDose) {
                    ctx.fillText(String(dose), x, point.y + LABEL_OFFSET + LINE_HEIGHT);
                }
            } else {
                // выше диапазона — подпись над точкой: МНО, затем доза
                if (hasDose) {
                    ctx.fillText(String(value), x, point.y - LABEL_OFFSET - LINE_HEIGHT);
                    ctx.fillText(String(dose), x, point.y - LABEL_OFFSET);
                } else {
                    ctx.fillText(String(value), x, point.y - LABEL_OFFSET);
                }
            }
        });
    }
};

export default {
    name: 'MnoChart',
    components: { Line },
    props: {
        data: { type: Array, required: true },
        mnoFrom: { type: Number, default: null },
        mnoTo: { type: Number, default: null },
    },
    data() {
        return {
            customLabelsPlugin,
            selectedRange: 'all',
            ranges: [
                { value: '1m', label: '1 мес.' },
                { value: '3m', label: '3 мес.' },
                { value: '6m', label: '6 мес.' },
                { value: '1y', label: '1 год' },
                { value: 'all', label: 'Всё' },
            ],
        };
    },
    computed: {
        // Единая точка расчёта: preparedData кэширует prepareChartData(),
        // чтобы chartData и chartOptions не пересчитывали данные дважды.
        preparedData() {
            return this.prepareChartData();
        },
        chartData() {
            const d = this.preparedData;
            if (!d) return null;
            return { labels: d.labels, datasets: d.datasets };
        },
        chartOptions() {
            const d = this.preparedData;
            return {
                responsive: true,
                maintainAspectRatio: false,
                // Отступы сверху/снизу — чтобы подписи «над точкой» не уходили за край.
                layout: {
                    padding: { top: 40, bottom: 30 },
                },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                if (context.datasetIndex !== 0) {
                                    return `${context.dataset.label}: ${context.parsed.y}`;
                                }
                                const doses = context.chart.options.doses || [];
                                const dose = doses[context.dataIndex];
                                if (hasDoseValue(dose)) {
                                    return `МНО: ${context.parsed.y} · доза: ${dose}`;
                                }
                                return `МНО: ${context.parsed.y}`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        title: { display: true, text: 'МНО' },
                        min: 0,
                    },
                    x: {
                        title: { display: true, text: 'Дата' },
                    },
                },
                mnoFrom: this.mnoFrom,
                mnoTo: this.mnoTo,
                doses: d ? d.doses : [],
            };
        },
    },
    methods: {
        changeRange(range) {
            this.selectedRange = range;
        },
        prepareChartData() {
            if (!this.data || this.data.length === 0) return null;

            let items = this.data
                .filter(item => item.inr !== '—' && !isNaN(parseFloat(item.inr)))
                .sort((a, b) => new Date(a.date) - new Date(b.date));

            if (items.length === 0) return null;

            if (this.selectedRange !== 'all') {
                const lastDate = items.reduce((max, item) => new Date(item.date) > max ? new Date(item.date) : max, new Date(0));
                if (isNaN(lastDate.getTime())) return null;

                const msMap = {
                    '1m': 30 * 24 * 60 * 60 * 1000,
                    '3m': 90 * 24 * 60 * 60 * 1000,
                    '6m': 180 * 24 * 60 * 60 * 1000,
                    '1y': 365 * 24 * 60 * 60 * 1000,
                };
                const limit = new Date(lastDate.getTime() - msMap[this.selectedRange]);
                items = items.filter(item => new Date(item.date) >= limit);
            }

            const labels = items.map(item => formatDate(item.date));
            const inrValues = items.map(item => parseFloat(item.inr));
            const doses = items.map(item => item.dose);

            const datasets = [
                {
                    label: 'МНО',
                    data: inrValues,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.3,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                },
            ];

            if (this.mnoFrom !== null && this.mnoFrom !== undefined) {
                datasets.push({
                    label: `Нижняя граница (${formatMno(this.mnoFrom)})`,
                    data: new Array(labels.length).fill(this.mnoFrom),
                    borderColor: '#27ae60',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                });
            }
            if (this.mnoTo !== null && this.mnoTo !== undefined) {
                datasets.push({
                    label: `Верхняя граница (${formatMno(this.mnoTo)})`,
                    data: new Array(labels.length).fill(this.mnoTo),
                    borderColor: '#e74c3c',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                });
            }

            return { labels, doses, datasets };
        },
    },
};