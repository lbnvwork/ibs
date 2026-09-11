export default {
    name: 'MetricCard',
    props: {
        label: { type: String, required: true },
        value: { type: String, default: '—' },
        hint: { type: String, default: '' },
        tone: { type: String, default: 'neutral' }
    }
};
