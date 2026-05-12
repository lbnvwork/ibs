import apiClient from '@/api/client';
import { validateForm } from '@/utils/validationHelper';

export default {
    name: 'TestAddModal',
    props: {
        treatment: { type: String, required: true },
        drugId: { type: Number, required: true }
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
        };
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