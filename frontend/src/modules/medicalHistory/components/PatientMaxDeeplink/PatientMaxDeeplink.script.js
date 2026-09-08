import apiClient from '@/modules/shared/api/client';

export default {
    name: 'PatientMaxDeeplink',
    props: {
        patientId: { type: [Number, String], required: true },
    },
    data() {
        return {
            url: null,
            bound: null,
            loading: false,
            error: null,
            copyFeedback: null,
        };
    },
    methods: {
        async fetchDeeplink() {
            this.loading = true;
            this.error = null;
            try {
                const { data } = await apiClient.get(`/patients/${this.patientId}/max-deeplink`);
                this.url = data.url || null;
                this.bound = data.bound === true;
            } catch (err) {
                this.error = err.response?.status === 404
                    ? 'Пациент не найден.'
                    : 'Не удалось получить ссылку. Попробуйте ещё раз.';
                this.url = null;
                this.bound = null;
            } finally {
                this.loading = false;
            }
        },
        async copyLink() {
            if (!this.url) return;
            try {
                await navigator.clipboard.writeText(this.url);
                this.copyFeedback = 'copied';
            } catch {
                this.copyFeedback = 'fallback';
            }
        },
    },
};
