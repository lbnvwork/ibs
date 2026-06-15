import { defineStore } from 'pinia';
import { patientApi } from '@/api/patients';
import apiClient from '@/api/client';
import { extractIdFromIri } from '@/utils/apiHelpers';
import { validateForm } from '@/utils/validationHelper';
import { formatPhone, formatPassport, formatSnils } from '@/utils/formatters';
import { isValidPhone, isValidSnils, isValidPassport, isValidEmail } from '@/utils/validators';
import { parseApiError } from '@/utils/apiErrorHandler';

export const usePatientCardStore = defineStore('patientCard', {
    state: () => ({
        patient: null,
        loading: false,
        error: null,
        editingPatient: false,
        originalPatientJson: '',
        editingPatientData: {},
        patientFormError: '',
        hospitalName: ''
    }),

    getters: {
        isPatientLoaded: (state) => state.patient !== null
    },

    actions: {
        async fetchPatient(patientId) {
            this.loading = true;
            this.error = null;
            try {
                const data = await patientApi.getOne(patientId);
                if (data.hospital) {
                    const hospitalId = extractIdFromIri(data.hospital);
                    if (hospitalId) {
                        const resp = await apiClient.get(`/hospitals/${hospitalId}`);
                        this.hospitalName = resp.data.name || '';
                    }
                }
                this.patient = {
                    id: data.id,
                    name: `${data.lastname} ${data.firstname} ${data.secondName}`.trim() || 'Без имени',
                    address: data.address || '—',
                    phone: data.smsPhone || '—',
                    email: data.email || '—',
                    passport: data.passport || '—',
                    insurance: data.healthInsurance || '—',
                    snils: data.snils || '—',
                    comment: data.comment || '',
                    hospital: this.hospitalName || '—',
                    birthDate: data.birthday
                };
            } catch (err) {
                this.error = 'Не удалось загрузить данные пациента.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        startEditingPatient() {
            this.patientFormError = '';
            this.editingPatientData = {
                address: this.patient.address || '',
                phone: formatPhone(this.patient.phone) || this.patient.phone || '',
                passport: formatPassport(this.patient.passport) || this.patient.passport || '',
                insurance: this.patient.insurance || '',
                snils: formatSnils(this.patient.snils) || this.patient.snils || '',
                comment: this.patient.comment || '',
                email: this.patient.email || ''
            };
            this.originalPatientJson = JSON.stringify(this.editingPatientData);
            this.editingPatient = true;
        },

        cancelEditingPatient() {
            if (this.originalPatientJson) {
                this.editingPatientData = JSON.parse(this.originalPatientJson);
            }
            this.editingPatient = false;
            this.patientFormError = '';
        },

        validatePatientForm() {
            const rules = {
                address: { required: true, message: 'Адрес обязателен' },
                phone: {
                    required: true,
                    message: 'Телефон обязателен',
                    validator: isValidPhone,
                    errorMsg: 'Формат: 8(XXX)XXX-XX-XX'
                },
                passport: {
                    required: true,
                    message: 'Паспорт обязателен',
                    validator: isValidPassport,
                    errorMsg: 'Формат: XXXX XXXXXX'
                },
                snils: {
                    required: true,
                    message: 'СНИЛС обязателен',
                    validator: isValidSnils,
                    errorMsg: 'Формат: XXX-XXX-XXX XX'
                },
                email: {
                    validator: isValidEmail,
                    errorMsg: 'Неверный формат email'
                }
            };

            const errors = validateForm(this.editingPatientData, rules);
            if (Object.keys(errors).length > 0) {
                this.patientFormError = Object.values(errors).join('\n');
                return true;
            }
            this.patientFormError = '';
            return false;
        },

        async savePatient(patientId) {
            if (this.validatePatientForm()) return false;

            const body = {
                address: this.editingPatientData.address.trim(),
                smsPhone: formatPhone(this.editingPatientData.phone),
                passport: formatPassport(this.editingPatientData.passport),
                healthInsurance: this.editingPatientData.insurance.trim(),
                snils: formatSnils(this.editingPatientData.snils),
                comment: this.editingPatientData.comment.trim() || null,
                email: this.editingPatientData.email.trim() || null
            };

            try {
                await patientApi.update(patientId, body);
                this.patient.address = body.address;
                this.patient.phone = body.smsPhone;
                this.patient.passport = body.passport;
                this.patient.insurance = body.healthInsurance;
                this.patient.snils = body.snils;
                this.patient.comment = body.comment;
                this.patient.email = body.email;
                this.editingPatient = false;
                return true;
            } catch (err) {
                console.error('Ошибка сохранения данных пациента:', err);
                if (err.response?.status === 422) {
                    const parsed = parseApiError(err);
                    if (parsed.violations) {
                        const messages = parsed.violations.map(v => {
                            let msg = v.message;
                            const prefix = `${v.propertyPath}: `;
                            if (msg.startsWith(prefix)) msg = msg.slice(prefix.length);
                            return `• ${v.propertyPath}: ${msg}`;
                        });
                        this.patientFormError = messages.join('\n');
                    } else {
                        this.patientFormError = parsed.generalError || 'Ошибка сохранения';
                    }
                } else {
                    this.patientFormError = 'Не удалось сохранить данные. Проверьте соединение.';
                }
                return false;
            }
        }
    }
});