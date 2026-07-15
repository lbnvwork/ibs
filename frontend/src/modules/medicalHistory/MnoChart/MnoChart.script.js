import { Line } from 'vue-chartjs';
import { formatDate } from '@/modules/shared/utils/formatters';
import {
    Chart as ChartJS,
    registerables
} from 'chart.js';

ChartJS.register(...registerables);

// Кастомный плагин для подписей вне диапазона
const customLabelsPlugin = {
    id: 'customLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const meta = chart.getDatasetMeta(0);
        const mnoFrom = chart.options.mnoFrom;
        const mnoTo = chart.options.mnoTo;

        if (!meta || !meta.data) return;

        meta.data.forEach((point, index) => {
            const value = chart.data.datasets[0].data[index];
            if (value === null || value === undefined) return;

            const below = mnoFrom !== null && value < mnoFrom;
            const above = mnoTo !== null && value > mnoTo;
            if (!below && !above) return;

            const x = point.x;
            const y = below ? point.y + 15 : point.y - 10;
            ctx.font = 'bold 10px Arial';
            ctx.fillStyle = below ? '#2a5c98' : '#e74c3c';
            ctx.textAlign = 'center';
            ctx.fillText(value, x, y);
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
        chartData() {
            const d = this.prepareChartData();
            if (!d) return null;
            return { labels: d.labels, datasets: d.datasets };
        },
        chartOptions() {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
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
                    label: `Нижняя граница (${this.mnoFrom})`,
                    data: new Array(labels.length).fill(this.mnoFrom),
                    borderColor: '#27ae60',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                });
            }
            if (this.mnoTo !== null && this.mnoTo !== undefined) {
                datasets.push({
                    label: `Верхняя граница (${this.mnoTo})`,
                    data: new Array(labels.length).fill(this.mnoTo),
                    borderColor: '#e74c3c',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                });
            }

            return { labels, datasets };
        },
    },
};