import { formatDate } from '@/modules/shared/utils/formatters';

export default {
    name: 'DashboardHeader',
    emits: ['edit-patient', 'edit-treatment'],
    props: {
        patient: { type: Object, default: () => ({}) },
        treatment: { type: Object, default: () => ({}) },
        doctorName: { type: String, default: '' },
        sex: { type: Number, default: null }
    },
    computed: {
        initials() {
            const parts = (this.patient?.name || '').split(' ').filter(Boolean);
            if (parts.length === 0) return '—';
            const first = parts[0].charAt(0);
            const last = parts.length > 1 ? parts[parts.length - 1].charAt(0) : '';
            return (first + last).toUpperCase();
        },
        avatarClass() {
            if (this.sex === 1) return 'avatar--male';
            if (this.sex === 0) return 'avatar--female';
            return 'avatar--neutral';
        },
        genderLabel() {
            if (this.sex === 1) return 'муж';
            if (this.sex === 0) return 'жен';
            return '—';
        },
        birthDate() {
            return formatDate(this.patient?.birthday);
        },
        statusLabel() {
            return this.treatment?.realEndDt ? 'Лечение завершено' : 'Активное наблюдение';
        },
        statusClass() {
            return this.treatment?.realEndDt ? 'status-badge--inactive' : 'status-badge--active';
        }
    },
    methods: {
        formatDate
    }
};
