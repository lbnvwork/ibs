import { formatMno } from '@/modules/shared/utils/formatters';

export default {
    name: 'ComplicationRisk',
    props: {
        mno: { type: Number, default: null },
        mnoFrom: { type: Number, default: null },
        mnoTo: { type: Number, default: null }
    },
    computed: {
        hasData() {
            return this.mno !== null && this.mno !== undefined;
        },
        hasRange() {
            return this.mnoFrom !== null && this.mnoFrom !== undefined &&
                this.mnoTo !== null && this.mnoTo !== undefined;
        },
        level() {
            if (!this.hasData) return 'none';
            if (this.hasRange) {
                if (this.mno < this.mnoFrom) return 'info';
                if (this.mno > this.mnoTo) return 'danger';
                return 'success';
            }
            return 'neutral';
        },
        label() {
            switch (this.level) {
                case 'danger': return 'Угроза кровотечения';
                case 'info': return 'Риск тромбоза';
                case 'success': return 'В норме';
                case 'none': return 'Нет данных';
                default: return 'Диапазон не задан';
            }
        },
        rangeText() {
            if (!this.hasRange) return '';
            return `${formatMno(this.mnoFrom)}–${formatMno(this.mnoTo)}`;
        }
    }
};
