import { defineStore } from 'pinia';
import { treatmentApi } from '@/api/treatments';
import { drugApi } from '@/api/drug';
import { extractIdFromIri } from '@/utils/apiHelpers';
import { validateForm } from '@/utils/validationHelper';
import { parseApiError } from '@/utils/apiErrorHandler';

export const useTreatmentStore = defineStore('treatment', {
    state: () => ({
        treatment: null,
        loading: false,
        error: null,
        editingTreatment: false,
        originalTreatmentJson: '',
        editingTreatmentData: {},
        allDrugs: [],
        treatmentFormError: ''
    }),

    getters: {
        isActive: (state) => {
            if (!state.treatment) return false;
            return state.treatment.realEndDt === null || state.treatment.realEndDt === undefined;
        },
        treatmentIri: (state) => state.treatment?.['@id'] || null
    },

    actions: {
        async fetchTreatment(patientId) {
            this.loading = true;
            this.error = null;
            try {
                const resp = await treatmentApi.getAll({
                    patient: `/api/patients/${patientId}`,
                    itemsPerPage: 1,
                    order: { begDt: 'desc' }
                });
                const treatments = resp.member || [];
                this.treatment = treatments.length > 0 ? treatments[0] : null;
            } catch (err) {
                this.error = 'Не удалось загрузить данные лечения.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        async loadDrugsIfNeeded() {
            if (this.allDrugs.length === 0) {
                try {
                    const resp = await drugApi.getAll();
                    this.allDrugs = resp.member || [];
                } catch (err) {
                    console.error('Ошибка загрузки препаратов', err);
                }
            }
        },

        startEditingTreatment() {
            this.treatmentFormError = '';
            this.editingTreatmentData = {
                diagnosis: this.treatment.diagnosis,
                diagnosisCode: this.treatment.diagnosisCode || '',
                comorbiditiesRaw: this.treatment.comorbidities || '',
                mnoFrom: this.treatment.mnoFrom,
                mnoTo: this.treatment.mnoTo,
                drugId: this.treatment.drug ? extractIdFromIri(this.treatment.drug) : null,
                begDt: this.treatment.begDt ? this.treatment.begDt.substring(0, 10) : null,
                planEndDt: this.treatment.planEndDt ? this.treatment.planEndDt.substring(0, 10) : null,
                treatmentComment: this.treatment.comment || ''
            };
            this.originalTreatmentJson = JSON.stringify(this.editingTreatmentData);
            this.editingTreatment = true;
        },

        cancelEditingTreatment() {
            if (this.originalTreatmentJson) {
                this.editingTreatmentData = JSON.parse(this.originalTreatmentJson);
            }
            this.editingTreatment = false;
            this.treatmentFormError = '';
        },

        validateTreatmentForm() {
            const rules = {
                diagnosis: {
                    required: true,
                    message: 'Диагноз обязателен',
                    validator: (val) => val && val.trim().length > 0,
                    errorMsg: 'Введите диагноз'
                },
                drugId: {
                    required: true,
                    message: 'Выберите препарат'
                },
                begDt: {
                    required: true,
                    message: 'Дата госпитализации обязательна'
                },
                mnoFrom: {
                    required: true,
                    message: 'Нижняя граница МНО обязательна'
                },
                mnoTo: {
                    required: true,
                    message: 'Верхняя граница МНО обязательна'
                }
            };

            const extraChecks = (errors, data) => {
                const from = parseFloat(data.mnoFrom);
                const to = parseFloat(data.mnoTo);
                if (!isNaN(from) && !isNaN(to) && from >= to) {
                    errors.mnoRange = 'Нижняя граница должна быть меньше верхней';
                }
                if (data.planEndDt && data.begDt &&
                    new Date(data.planEndDt) < new Date(data.begDt)) {
                    errors.planEndDt = 'Плановая дата не может быть раньше даты госпитализации';
                }
            };

            const errors = validateForm(this.editingTreatmentData, rules, extraChecks);
            if (Object.keys(errors).length > 0) {
                this.treatmentFormError = Object.values(errors).join('\n');
                return true;
            }
            this.treatmentFormError = '';
            return false;
        },

        isTreatmentDataChanged() {
            return JSON.stringify(this.editingTreatmentData) !== this.originalTreatmentJson;
        },

        async saveTreatment() {
            if (this.validateTreatmentForm()) return false;
            if (!this.isTreatmentDataChanged()) {
                this.editingTreatment = false;
                return true;
            }

            const body = {
                diagnosis: this.editingTreatmentData.diagnosis.trim(),
                comorbidities: this.editingTreatmentData.comorbiditiesRaw.trim(),
                mnoFrom: Number(this.editingTreatmentData.mnoFrom),
                mnoTo: Number(this.editingTreatmentData.mnoTo),
                drug: `/api/drugs/${this.editingTreatmentData.drugId}`,
                begDt: this.editingTreatmentData.begDt,
                planEndDt: this.editingTreatmentData.planEndDt || null,
                comment: (this.editingTreatmentData.treatmentComment || '').trim() || null
            };

            try {
                const treatmentId = extractIdFromIri(this.treatment['@id']);
                await treatmentApi.update(treatmentId, body);

                this.treatment.diagnosis = body.diagnosis;
                this.treatment.comorbidities = body.comorbidities;
                this.treatment.mnoFrom = body.mnoFrom;
                this.treatment.mnoTo = body.mnoTo;
                this.treatment.drug = `/api/drugs/${this.editingTreatmentData.drugId}`;
                this.treatment.begDt = body.begDt;
                this.treatment.planEndDt = body.planEndDt;
                this.treatment.comment = body.comment;

                this.editingTreatment = false;
                return true;
            } catch (err) {
                console.error('Ошибка сохранения лечения:', err);
                if (err.response?.status === 422) {
                    const parsed = parseApiError(err);
                    if (parsed.violations) {
                        const messages = parsed.violations.map(v => {
                            let msg = v.message;
                            const prefix = `${v.propertyPath}: `;
                            if (msg.startsWith(prefix)) msg = msg.slice(prefix.length);
                            return `• ${v.propertyPath}: ${msg}`;
                        });
                        this.treatmentFormError = messages.join('\n');
                    } else {
                        this.treatmentFormError = parsed.generalError || 'Ошибка сохранения';
                    }
                } else {
                    this.treatmentFormError = 'Не удалось сохранить изменения. Проверьте соединение.';
                }
                return false;
            }
        }
    }
});