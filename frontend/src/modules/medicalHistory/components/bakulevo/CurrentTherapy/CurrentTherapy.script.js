import { formatMno } from '@/modules/shared/utils/formatters';

export default {
    name: 'CurrentTherapy',
    props: {
        drugName: { type: String, default: '—' },
        diagnosis: { type: String, default: '—' },
        mnoFrom: { type: Number, default: null },
        mnoTo: { type: Number, default: null },
        dose: { type: [Number, String], default: null },
        doseDate: { type: String, default: '' }
    },
    computed: {
        rangeText() {
            if (this.mnoFrom !== null && this.mnoFrom !== undefined &&
                this.mnoTo !== null && this.mnoTo !== undefined) {
                return `${formatMno(this.mnoFrom)}–${formatMno(this.mnoTo)}`;
            }
            return '—';
        },
        doseDisplay() {
            const d = this.dose;
            if (d === null || d === undefined || d === '' || d === '—') return '—';
            return d;
        }
    }
};
