import apiClient from '@/modules/shared/api/client';
import { validateForm } from '@/modules/shared/utils/validationHelper';

const AUTOFILL_DEBOUNCE_MS = 300;

export default {
    name: 'TestAddModal',
    props: {
        treatment: { type: String, required: true },
        drugId: { type: Number, required: true },
        drugGenitive: { type: String, default: '' }
    },
    emits: ['close', 'saved'],
    data() {
        return {
            creationDt: new Date().toISOString().slice(0, 10),
            mno: null,
            doze: null,
            comment: '',
            enableAlternation: false,
            alternationDelta: null,
            fieldErrors: {},
            saveError: null,
            isCommentDirty: false,
            autofillTimer: null,
        };
    },
    watch: {
        mno() {
            this.scheduleAutofill();
        },
    },
    computed: {
        dose2() {
            if (!this.enableAlternation || this.alternationDelta === null || this.alternationDelta === '') {
                return null;
            }
            return this.doze !== null ? this.doze + Number(this.alternationDelta) : null;
        }
    },
    beforeUnmount() {
        if (this.autofillTimer) {
            clearTimeout(this.autofillTimer);
        }
    },
    created() {
        this.loadLastAppointment();
    },
    methods: {
        validateForm() {
            const rules = {
                creationDt: {
                    required: true,
                    message: 'Дата анализа обязательна',
                },
                mno: {
                    required: true,
                    message: 'МНО обязательно',
                    validator: (val) => val !== null && val >= 0.8 && val <= 10.0,
                    errorMsg: 'МНО должно быть в диапазоне 0.8–10.0',
                },
                doze: {
                    required: true,
                    message: 'Доза обязательна',
                    validator: (val) => val !== null && val > 0 && val <= 10 && val % 0.25 === 0,
                    errorMsg: 'Доза должна быть положительной, кратной 0.25 и не более 10 таблеток',
                },
            };

            if (this.enableAlternation) {
                rules.alternationDelta = {
                    required: true,
                    message: 'Выберите отклонение чередования.',
                };
            }

            const formData = { creationDt: this.creationDt, mno: this.mno, doze: this.doze };
            if (this.enableAlternation) {
                formData.alternationDelta = this.alternationDelta;
            }

            const extraChecks = (errors) => {
                if (this.enableAlternation && this.dose2 !== null) {
                    if (this.dose2 <= 0) {
                        errors.alternationDelta = 'Вторая доза должна быть положительной.';
                    }
                    if (this.dose2 > 10) {
                        errors.alternationDelta = 'Максимальная доза 10 таблеток.';
                    }
                }
            };

            const errors = validateForm(formData, rules, extraChecks);
            this.fieldErrors = errors;
            return Object.keys(errors).length > 0;
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
            if (this.mno === null || this.mno === undefined || this.mno === '') {
                return;
            }
            try {
                const response = await apiClient.post('/notification_templates/resolve', {
                    code: 'analysis_result',
                    data: {
                        mno: this.mno,
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

        onAlternationToggle() {
            if (!this.enableAlternation) {
                this.alternationDelta = null;
            }
        },

        async loadLastAppointment() {
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
                    const last = member[0];
                    this.doze = last.doze;
                    const delta = last.doze2 !== null && last.doze2 !== undefined
                        ? Number(last.doze2) - Number(last.doze)
                        : null;
                    if (delta !== null && [0.25, -0.25, 0.5, -0.5].some(d => Math.abs(delta - d) < 1e-9)) {
                        this.enableAlternation = true;
                        this.alternationDelta = String(delta);
                    }
                }
            } catch (err) {
                console.warn('Не удалось загрузить последнее назначение для автоподстановки:', err);
            }
        },

        async save() {
            if (this.validateForm()) {
                return;
            }

            this.saveError = null;

            const payload = {
                treatment: this.treatment,
                creationDt: new Date(this.creationDt).toISOString(),
                mno: this.mno,
                doze: this.doze,
                doze2: this.enableAlternation && this.dose2 !== null ? this.dose2 : -1,
                drug: `/api/drugs/${this.drugId}`,
                comment: this.comment || null,
            };

            try {
                await apiClient.post('/test_histories', payload);
                this.$emit('saved');
            } catch (err) {
                console.error('Ошибка сохранения анализа:', err);
                this.saveError = 'Не удалось сохранить анализ.';
            }
        },
    },
};
