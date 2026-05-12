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

export default {
    name: 'PatientHistory',
    components: { RiskScale, AppointmentAdd },
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

            this.medicalData = historyItems.map(item => {
              const testDate = new Date(item.creationDt).getTime();
              const nextAppt = appointments.find(a => new Date(a.appointmentDt).getTime() > testDate);
              return {
                date: item.creationDt
                  ? new Date(item.creationDt).toLocaleDateString('ru-RU')
                  : '—',
                inr: item.mno !== undefined ? item.mno : '—',
                analysis1: '—',
                analysis2: '—',
                heartRate: '—',
                bloodPressure: '—',
                currentDose: item.doze !== undefined ? item.doze : '—',
                prescribedDose: nextAppt?.doze ?? '—',
                recommendations: nextAppt?.comment || '',
                comment: item.comment || ''
              };
            });

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
    }
};