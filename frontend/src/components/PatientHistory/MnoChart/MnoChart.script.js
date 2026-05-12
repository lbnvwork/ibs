// Импорт убираем – Chart доступен глобально через CDN

export default {
    name: 'MnoChart',
    props: {
        data: { type: Array, required: true },
        mnoFrom: { type: Number, default: null },
        mnoTo: { type: Number, default: null },
    },
    mounted() {
        // Дождёмся, пока Chart загрузится с CDN
        if (typeof Chart === 'undefined') {
            const checkChart = setInterval(() => {
                if (typeof Chart !== 'undefined') {
                    clearInterval(checkChart);
                    this.renderChart();
                }
            }, 100);
            return;
        }
        this.renderChart();
    },
    methods: {
        renderChart() {
            if (!this.$refs.chartCanvas || this.data.length === 0) return;

            const filtered = this.data
                .filter(item => item.inr !== '—' && !isNaN(parseFloat(item.inr)))
                .sort((a, b) => new Date(a.date) - new Date(b.date));

            const labels = filtered.map(item => item.date);
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