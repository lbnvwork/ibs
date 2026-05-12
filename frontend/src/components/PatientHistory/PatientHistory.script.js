import { patientApi } from '@/api/patients';
import { treatmentApi } from '@/api/treatments';
import { drugApi } from '@/api/drug';
import { extractIdFromIri } from '@/utils/apiHelpers';
import { calculateAge, formatAge, formatDate } from '@/utils/formatters';
import RiskScale from '@/components/RiskScale/RiskScale.vue';
import apiClient from '@/api/client';
import { testHistoryApi } from '@/api/testHistory';
import { validateForm } from '@/utils/validationHelper';
import { parseApiError } from '@/utils/apiErrorHandler';
import { useAppointmentAddStore } from '@/stores/appointmentAddStore';
import AppointmentAdd from '@/components/PatientHistory/AppointmentAdd/AppointmentAdd.vue';
import TestAddModal from '@/components/PatientHistory/TestAddModal/TestAddModal.vue';

export default {
    name: 'PatientHistory',
    components: { RiskScale, AppointmentAdd, TestAddModal },
    props: {
        id: { type: String, default: null }
    },
    data() {
        return {
          patient: null,
          loading: true,
          error: null,
          medicalData: [],
          editingPatient: false,
          editingTreatment: false,
          originalTreatmentJson: '',
          editingTreatmentData: {},
          allDrugs: [],
          treatmentFormError: '',
          showTestModal: false,
        }
    },
    watch: {
        id(newId) {
            if (newId) this.loadPatientData();
        }
    },
    computed: {
        showAppointmentModal() {
            return useAppointmentAddStore().isModalOpen;
        },
    },
    created() {
        if (this.id) this.loadPatientData();
    },
    methods: {
        formatDate,
        extractIdFromIri,

        async loadPatientData() {
            this.loading = true;
            useAppointmentAddStore().setTreatmentActive(false);
            this.error = null;
            this.medicalData = [];
            this.editingTreatment = false;
            this.editingPatient = false;

            try {
                const patientData = await patientApi.getOne(this.id);
                const treatmentResp = await treatmentApi.getAll({
                    patient: `/api/patients/${this.id}`,
                    itemsPerPage: 1,
                    order: { begDt: 'desc' }
                });
                const treatments = treatmentResp.member || [];
                const treatment = treatments.length > 0 ? treatments[0] : null;

                const isActive = treatment ? (treatment.realEndDt === null || treatment.realEndDt === undefined) : false;

                useAppointmentAddStore().setTreatmentActive(isActive);

                let hospitalName = '';
                if (patientData.hospital) {
                    const hospitalId = extractIdFromIri(patientData.hospital);
                    if (hospitalId) {
                        const hospResp = await apiClient.get(`/hospitals/${hospitalId}`);
                        hospitalName = hospResp.data.name || '';
                    }
                }

                let drugName = '';
                if (treatment?.drug) {
                    const drugId = extractIdFromIri(treatment.drug);
                    if (drugId) {
                        const drugResp = await drugApi.getAll();
                        const drugs = drugResp.member || [];
                        const found = drugs.find(d => d.id === drugId);
                        if (found) drugName = found.nominative || found.genitive || '';
                    }
                }

                let historyItems = [];
                let appointments = [];
                if (treatment?.['@id']) {
                    const apptResp = await apiClient.get('/appointments', {
                        params: {
                            treatment: treatment['@id'],
                            order: { appointmentDt: 'asc' },
                            itemsPerPage: 1000
                        }
                    });
                    appointments = apptResp.data.member || [];

                    const historyResp = await testHistoryApi.getAll({
                        treatment: treatment['@id'],
                        order: { creationDt: 'desc' },
                        itemsPerPage: 300
                    });
                    historyItems = historyResp.member || [];
                }

                // Формируем единый хронологический список событий
                const events = [];

                // 1. Добавляем анализы
                historyItems.forEach(item => {
                    const testDate = new Date(item.creationDt);
                    const testDateStr = testDate.toLocaleDateString('ru-RU');
                    // Ищем назначение с той же датой
                    const matchingAppt = appointments.find(a => {
                        const apptDate = new Date(a.appointmentDt);
                        return apptDate.toLocaleDateString('ru-RU') === testDateStr;
                    });
                    if (matchingAppt) {
                        // Удаляем найденное назначение из общего списка, чтобы не дублировать
                        appointments = appointments.filter(a => a !== matchingAppt);
                    }
                    events.push({
                        type: 'test',
                        date: item.creationDt,
                        inr: item.mno !== undefined ? item.mno : '—',
                        currentDose: item.doze !== undefined ? item.doze : '—',
                        prescribedDose: matchingAppt ? matchingAppt.doze : '—',
                        recommendations: matchingAppt ? (matchingAppt.comment || '') : '',
                        comment: item.comment || '',
                    });
                });

                // 2. Добавляем оставшиеся назначения отдельными строками
                appointments.forEach(a => {
                    events.push({
                        type: 'appointment',
                        date: a.appointmentDt,
                        inr: '—',
                        currentDose: '—',
                        prescribedDose: a.doze,
                        recommendations: a.comment || '',
                        comment: '',
                    });
                });

                // 3. Сортируем по дате (сначала новые)
                events.sort((a, b) => new Date(b.date) - new Date(a.date));

                // 4. Формируем финальный массив для таблицы
                this.medicalData = events.map(event => ({
                    date: event.date
                        ? new Date(event.date).toLocaleDateString('ru-RU')
                        : '—',
                    inr: event.inr,
                    analysis1: '—',
                    analysis2: '—',
                    heartRate: '—',
                    bloodPressure: '—',
                    currentDose: event.currentDose,
                    prescribedDose: event.prescribedDose,
                    recommendations: event.recommendations,
                    comment: event.comment,
                    isAppointment: event.type === 'appointment',
                }));

                const fullName = [
                    patientData.lastname,
                    patientData.firstname,
                    patientData.secondName
                ].filter(Boolean).join(' ') || 'Без имени';
                const age = calculateAge(patientData.birthday);

                this.patient = {
                    name: fullName,
                    age: age ? formatAge(age) : '—',
                    birthDate: patientData.birthday
                        ? formatDate(patientData.birthday)
                        : '—',
                    address: patientData.address || '—',
                    phone: patientData.smsPhone || '—',
                    email: '—',
                    passport: patientData.passport || '—',
                    insurance: patientData.healthInsurance || '—',
                    snils: patientData.snils || '—',
                    hospital: hospitalName || '—',

                    diagnosis: treatment?.diagnosis || '—',
                    diagnosisCode: treatment?.diagnosisCode || '',
                    comorbiditiesRaw: treatment?.comorbidities || '',
                    additionalConditions: treatment?.comorbidities
                        ? [treatment.comorbidities]
                        : ['Нет данных'],
                    mnoFrom: treatment?.mnoFrom ?? null,
                    mnoTo: treatment?.mnoTo ?? null,
                    drugName: drugName || '—',
                    drugId: treatment?.drug ? extractIdFromIri(treatment.drug) : null,
                    begDt: treatment?.begDt ?? null,
                    planEndDt: treatment?.planEndDt ?? null,
                    realEndDt: treatment?.realEndDt ?? null,
                    treatmentComment: treatment?.comment || '',
                    treatmentIri: treatment?.['@id'] || null,
                    isActive: isActive,
                    stoppingReason: treatment?.stoppingReason || null,
                    hemorrhages: treatment?.hemorrhages ?? 0,

                    consentSigned: false,
                    riskScores: null,
                    pharmacogenetics: [],
                    mutations: [],
                };
            } catch (err) {
                console.error('Ошибка загрузки истории:', err);
                this.error = 'Не удалось загрузить данные пациента.';
            } finally {
                this.loading = false;
            }
        },
        
        async startEditingTreatment() {
            this.treatmentFormError = '';
            await this.loadDrugsIfNeeded();
            this.editingTreatmentData = {
                diagnosis: this.patient.diagnosis,
                diagnosisCode: this.patient.diagnosisCode || '',
                comorbiditiesRaw: this.patient.comorbiditiesRaw,
                mnoFrom: this.patient.mnoFrom,
                mnoTo: this.patient.mnoTo,
                drugId: this.patient.drugId,
                begDt: this.patient.begDt ? this.patient.begDt.substring(0, 10) : null,
                planEndDt: this.patient.planEndDt ? this.patient.planEndDt.substring(0, 10) : null,
                treatmentComment: this.patient.treatmentComment || '',
            };
            this.originalTreatmentJson = JSON.stringify(this.editingTreatmentData);
            this.editingTreatment = true;
        },

        cancelEditingTreatment() {
            this.treatmentFormError = '';
            if (this.originalTreatmentJson) {
                this.editingTreatmentData = JSON.parse(this.originalTreatmentJson);
            }
            this.editingTreatment = false;
        },
        
        async saveTreatment() {
            if (this.validateTreatmentForm()) {
                return;
            }

            if (!this.isTreatmentDataChanged()) {
                this.editingTreatment = false;
                return;
            }

            const body = {
                diagnosis: this.editingTreatmentData.diagnosis.trim(),
                comorbidities: this.editingTreatmentData.comorbiditiesRaw.trim(),
                mnoFrom: Number(this.editingTreatmentData.mnoFrom),
                mnoTo: Number(this.editingTreatmentData.mnoTo),
                drug: `/api/drugs/${this.editingTreatmentData.drugId}`,
                begDt: this.editingTreatmentData.begDt,
                planEndDt: this.editingTreatmentData.planEndDt || null,
                comment: (this.editingTreatmentData.treatmentComment || '').trim() || null,
            };

            try {
                const treatmentId = extractIdFromIri(this.patient.treatmentIri);
                await treatmentApi.update(treatmentId, body);

                this.patient.diagnosis = body.diagnosis;
                this.patient.comorbiditiesRaw = body.comorbidities;
                this.patient.additionalConditions = body.comorbidities
                    ? [body.comorbidities]
                    : ['Нет данных'];
                this.patient.mnoFrom = body.mnoFrom;
                this.patient.mnoTo = body.mnoTo;
                this.patient.drugId = this.editingTreatmentData.drugId;

                const selectedDrug = this.allDrugs.find(d => d.id == this.editingTreatmentData.drugId);
                if (selectedDrug) {
                    this.patient.drugName = selectedDrug.nominative;
                }

                this.patient.begDt = body.begDt;
                this.patient.planEndDt = body.planEndDt;
                this.patient.treatmentComment = body.comment || '';

                this.editingTreatment = false;
            } catch (err) {
                console.error('Ошибка сохранения лечения:', err);
                if (!err.response) {
                    this.treatmentFormError = 'Сервер недоступен. Проверьте соединение и попробуйте снова.';
                    return;
                }
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
                    this.treatmentFormError = parsed.generalError || 'Не удалось сохранить изменения.';
                }
            }
        },

        async loadDrugsIfNeeded(){
            if (this.allDrugs.length === 0) {
                try {
                    const resp = await drugApi.getAll();
                    this.allDrugs = resp.member || [];
                } catch (err) {
                    console.error('Ошибка загрузки препаратов', err);
                }
            }
        },

        validateTreatmentForm() {
            const rules = {
                diagnosis: {
                    required: true,
                    message: 'Диагноз обязателен',
                    validator: (val) => val && val.trim().length > 0,
                    errorMsg: 'Введите диагноз',
                },
                drugId: {
                    required: true,
                    message: 'Выберите препарат',
                },
                begDt: {
                    required: true,
                    message: 'Дата госпитализации обязательна',
                },
                mnoFrom: {
                    required: true,
                    message: 'Нижняя граница МНО обязательна',
                },
                mnoTo: {
                    required: true,
                    message: 'Верхняя граница МНО обязательна',
                },
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
        closeAppointmentModal() {
            useAppointmentAddStore().closeModal();
        },
        onAppointmentSaved() {
            this.loadPatientData();
            useAppointmentAddStore().closeModal();
        },
        openTestModal() {
            this.showTestModal = true;
        },
        onTestSaved() {
            this.loadPatientData();
            this.showTestModal = false;
        },
    }
};