import apiClient from '@/api/client';
import { validateForm } from '@/utils/validationHelper';

export default {
    name: 'AppointmentAdd',
    props: {
        treatment: { type: String, required: true },
        drugId: { type: Number, required: true },
        treatmentId: { type: Number, required: true }
    },
    emits: ['close', 'saved'],
    data() {
        return {
            appointmentDt: new Date().toISOString().slice(0, 10),
            comment: '',
            dose: null,
            selectedVariant: null,
            variants: [],
            explanation: '',
            isLoading: false,
            error: null,
            saveError: null,
            dose2: -1,
            enableAlternation: false,
            showDoseWarning: false,
            confirmOver50: false,
            lastAppointmentDose: null,
        };
    },
    computed: {
        canSave() {
            return this.dose !== null && this.dose > 0;
        }
    },
    methods: {
        async calculateDose() {
            this.isLoading = true;
            this.error = null;
            this.variants = [];
            this.explanation = '';
            this.selectedVariant = null;
            this.dose = null;

            try {
                const response = await apiClient.get('/dosage/recommendation', {
                    params: { treatment_id: this.treatmentId }
                });
                const data = response.data;
                this.variants = data.variants || [];
                this.explanation = data.explanation || '';

                if (this.variants.length === 0) {
                    this.error = this.explanation || 'Не удалось рассчитать дозу.';
                    return;
                }

                this.selectedVariant = 0;
                this.dose = this.variants[0].dose;
            } catch (err) {
                console.error('Ошибка расчёта дозы:', err);
                this.error = 'Не удалось рассчитать дозу. Проверьте соединение или повторите позже.';
            } finally {
                this.isLoading = false;
            }
        },

        selectVariant(idx) {
            this.selectedVariant = idx;
            this.dose = this.variants[idx].dose;
            this.checkDoseChange();
        },

        onDoseManualChange() {
            this.selectedVariant = null;
            this.checkDoseChange();
        },

        onAlternationToggle() {
            if (!this.enableAlternation) {
                this.dose2 = -1;
            } else {
                this.dose2 = null;
            }
        },

        onDose2Change() {},

        async loadLastAppointmentDose() {
            try {
                const response = await apiClient.get('/appointments', {
                    params: {
                        treatment: this.treatment,
                        itemsPerPage: 1,
                        order: { appointmentDt: 'desc' }
                    }
                });
                const member = response.data?.member;
                if (member && member.length > 0) {
                    this.lastAppointmentDose = member[0].doze;
                }
            } catch (err) {
                console.warn('Не удалось загрузить предыдущее назначение для сравнения дозы:', err);
            }
        },

        checkDoseChange() {
            if (!this.lastAppointmentDose || !this.dose || this.dose <= 0) {
                this.showDoseWarning = false;
                this.confirmOver50 = false;
                return;
            }
            const change = Math.abs(this.dose - this.lastAppointmentDose) / this.lastAppointmentDose;
            this.showDoseWarning = change > 0.5;
            this.confirmOver50 = false;
        },

        validateAppointmentForm() {
            const rules = {
                appointmentDt: {
                    required: true,
                    message: 'Укажите дату назначения.',
                },
                doze: {
                    required: true,
                    message: 'Введите дозу.',
                },
            };

            if (this.enableAlternation) {
                rules.doze2 = {
                    required: true,
                    message: 'Введите вторую дозу.',
                    validator: (val) => val !== null && val > 0 && val % 0.25 === 0,
                    errorMsg: 'Вторая доза должна быть положительной и кратной 0.25.',
                };
            }

            const extraChecks = (errors, data) => {
                if (data.doze !== null && data.doze > 0 && data.doze % 0.25 !== 0) {
                    errors.doze = 'Доза должна быть кратна 0.25 таблетки.';
                }
                if (data.doze > 10) {
                    errors.doze = 'Максимальная доза 10 таблеток.';
                }
                if (this.enableAlternation && data.doze2 !== null && data.doze2 > 0) {
                    if (data.doze2 % 0.25 !== 0) {
                        errors.doze2 = 'Вторая доза должна быть кратна 0.25.';
                    }
                    if (data.doze2 > 10) {
                        errors.doze2 = 'Максимальная доза 10 таблеток.';
                    }
                }
            };

            const formData = {
                appointmentDt: this.appointmentDt,
                doze: this.dose,
            };
            if (this.enableAlternation) {
                formData.doze2 = this.dose2;
            }

            const errors = validateForm(formData, rules, extraChecks);
            this.saveError = Object.keys(errors).length > 0 ? Object.values(errors).join('\n') : null;
            return Object.keys(errors).length > 0;
        },

        async save() {
            if (this.validateAppointmentForm()) {
                return;
            }

            if (this.showDoseWarning && !this.confirmOver50) {
                this.confirmOver50 = true;
                return;
            }

            if (this.confirmOver50) {
                this.showDoseWarning = false;
                this.confirmOver50 = false;
            }

            this.saveError = null;

            const isoDate = this.appointmentDt
                ? new Date(this.appointmentDt).toISOString()
                : new Date().toISOString();

            const payload = {
                treatment: this.treatment,
                appointmentDt: isoDate,
                doze: this.dose,
                doze2: this.enableAlternation ? this.dose2 : -1,
                drug: `/api/drugs/${this.drugId}`,
                comment: this.comment || null
            };

            try {
                await apiClient.post('/appointments', payload);
                this.$emit('saved');
            } catch (err) {
                console.error('Ошибка сохранения назначения:', err);
                this.saveError = 'Не удалось сохранить назначение.';
                if (err.response?.status === 422) {
                    this.saveError = 'Лечение не активно. Сохранение назначения невозможно.';
                }
            }
        }
    },
    created() {
        this.loadLastAppointmentDose();
    }
};