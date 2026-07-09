import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import { formatDate } from '@/modules/shared/utils/formatters';

Chart.register(...registerables, ChartDataLabels);

export default {
    name: 'MnoChart',
    props: {
        data: { type: Array, required: true },
        mnoFrom: { type: Number, default: null },
        mnoTo: { type: Number, default: null },
    },
    mounted() {
        this.renderChart();
    },
    methods: {
        renderChart() {
            if (!this.$refs.chartCanvas || this.data.length === 0) return;

            const filtered = this.data
                .filter(item => item.inr !== '—' && !isNaN(parseFloat(item.inr)))
                .sort((a, b) => new Date(a.date) - new Date(b.date));

            const labels = filtered.map(item => formatDate(item.date));
            const inrValues = filtered.map(item => parseFloat(item.inr));

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
                    data: Array(labels.length).fill(this.mnoFrom),
                    borderColor: '#27ae60',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                });
            }
            if (this.mnoTo !== null && this.mnoTo !== undefined) {
                datasets.push({
                    label: `Верхняя граница (${this.mnoTo})`,
                    data: Array(labels.length).fill(this.mnoTo),
                    borderColor: '#e74c3c',
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false,
                });
            }

            new Chart(this.$refs.chartCanvas, {
                type: 'line',
                data: { labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        datalabels: {
                            display: (context) => {
                                // Показываем только для основной линии МНО и только если значение вне диапазона
                                if (context.datasetIndex !== 0) return false;
                                const value = context.dataset.data[context.dataIndex];
                                const below = this.mnoFrom !== null && value < this.mnoFrom;
                                const above = this.mnoTo !== null && value > this.mnoTo;
                                return below || above;
                            },
                            anchor: 'center',
                            align: (context) => {
                                const value = context.dataset.data[context.dataIndex];
                                if (this.mnoFrom !== null && value < this.mnoFrom) return 'bottom'; // ниже нормы — метка снизу
                                if (this.mnoTo !== null && value > this.mnoTo) return 'top';         // выше нормы — метка сверху
                                return 'center';
                            },
                            offset: 6,
                            color: (context) => {
                                const value = context.dataset.data[context.dataIndex];
                                if (this.mnoFrom !== null && value < this.mnoFrom) return '#2a5c98'; // синий для нижних
                                if (this.mnoTo !== null && value > this.mnoTo) return '#e74c3c';     // красный для верхних
                                return '#000';
                            },
                            formatter: (value) => value,
                            font: { weight: 'bold', size: 10 },
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
                },
            });
        },
    },
};