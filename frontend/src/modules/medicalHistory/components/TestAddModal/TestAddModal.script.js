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
    beforeUnmount() {
        if (this.autofillTimer) {
            clearTimeout(this.autofillTimer);
        }
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

            const errors = validateForm(
                { creationDt: this.creationDt, mno: this.mno, doze: this.doze },
                rules
            );
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
                drug: `/api/drugs/${this.drugId}`,
                comment: this.comment || null,
                // doze2 явно не отправляем — сервер должен подставить -1
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
