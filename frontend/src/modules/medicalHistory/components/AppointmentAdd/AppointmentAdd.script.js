import apiClient from '@/modules/shared/api/client';
import { testHistoryApi } from '@/modules/shared/api/testHistory';
import { validateForm } from '@/modules/shared/utils/validationHelper';

const AUTOFILL_DEBOUNCE_MS = 300;

export default {
    name: 'AppointmentAdd',
    props: {
        treatment: { type: String, required: true },
        drugId: { type: Number, required: true },
        treatmentId: { type: Number, required: true },
        drugGenitive: { type: String, default: '' }
    },
    emits: ['close', 'saved'],
    data() {
        return {
            appointmentDt: new Date().toISOString().slice(0, 10),
            nextTestDt: null,
            comment: '',
            dose: null,
            selectedVariant: null,
            variants: [],
            explanation: '',
            isLoading: false,
            error: null,
            saveError: null,
            alternationDelta: null,
            enableAlternation: false,
            showDoseWarning: false,
            confirmOver50: false,
            lastAppointmentDose: null,
            mno: null,
            isCommentDirty: false,
            autofillTimer: null,
        };
    },
    computed: {
        canSave() {
            return this.dose !== null && this.dose > 0;
        },
        dose2() {
            if (!this.enableAlternation || this.alternationDelta === null || this.alternationDelta === '') {
                return null;
            }
            return this.dose !== null ? this.dose + Number(this.alternationDelta) : null;
        }
    },
    watch: {
        dose() {
            this.scheduleAutofill();
        },
        dose2() {
            this.scheduleAutofill();
        },
        appointmentDt() {
            this.scheduleAutofill();
        },
        mno() {
            this.scheduleAutofill();
        }
    },
    beforeUnmount() {
        if (this.autofillTimer) {
            clearTimeout(this.autofillTimer);
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
                this.alternationDelta = null;
            }
            this.scheduleAutofill();
        },

        scheduleAutofill() {
            if (this.autofillTimer) {
                clearTimeout(this.autofillTimer);
            }
            this.autofillTimer = setTimeout(() => this.autofillComment(), AUTOFILL_DEBOUNCE_MS);
        },

        async autofillComment() {
            if (this.isCommentDirty) {
                return;
            }
            if (this.dose === null || this.dose === undefined) {
                return;
            }
            const sdose = this.enableAlternation && this.dose2 !== null ? this.dose2 : null;
            const code = sdose !== null ? 'appointment_alternate' : 'appointment_dose';
            try {
                const response = await apiClient.post('/notification_templates/resolve', {
                    code,
                    data: {
                        mno: this.mno,
                        date: this.formatAppointmentDate(this.appointmentDt),
                        dose: this.dose,
                        sdose,
                        drug_genitive: this.drugGenitive || null,
                    },
                });
                this.comment = response.data?.body ?? '';
            } catch (err) {
                console.error('Не удалось получить превью сообщения:', err);
            }
        },

        markCommentDirty() {
            this.isCommentDirty = true;
        },

        formatAppointmentDate(dateStr) {
            if (!dateStr) {
                return '';
            }
            const [year, month, day] = dateStr.split('-');
            return `${day}.${month}.${year}`;
        },

        async loadLastMno() {
            try {
                const items = await testHistoryApi.getLatestByTreatments([this.treatmentId]);
                if (items && items.length > 0) {
                    this.mno = items[0].mno ?? null;
                }
            } catch (err) {
                console.warn('Не удалось загрузить последний МНО для автозаполнения:', err);
            }
        },

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
                rules.alternationDelta = {
                    required: true,
                    message: 'Выберите отклонение чередования.',
                };
            }

            const extraChecks = (errors, data) => {
                if (data.doze !== null && data.doze > 0 && data.doze % 0.25 !== 0) {
                    errors.doze = 'Доза должна быть кратна 0.25 таблетки.';
                }
                if (data.doze > 10) {
                    errors.doze = 'Максимальная доза 10 таблеток.';
                }
                if (this.enableAlternation && this.dose2 !== null) {
                    if (this.dose2 <= 0) {
                        errors.alternationDelta = 'Вторая доза должна быть положительной.';
                    }
                    if (this.dose2 > 10) {
                        errors.alternationDelta = 'Максимальная доза 10 таблеток.';
                    }
                }
                if (this.nextTestDt && this.appointmentDt && this.nextTestDt < this.appointmentDt) {
                    errors.nextTestDt = 'Дата следующей сдачи не может быть раньше даты назначения.';
                }
            };

            const formData = {
                appointmentDt: this.appointmentDt,
                doze: this.dose,
            };
            if (this.enableAlternation) {
                formData.alternationDelta = this.alternationDelta;
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
                nextTestDt: this.nextTestDt ? new Date(this.nextTestDt).toISOString() : null,
                doze: this.dose,
                doze2: this.enableAlternation && this.dose2 !== null ? this.dose2 : -1,
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
        this.loadLastMno();
    }
};