import { extractIdFromIri } from '@/modules/shared/utils/apiHelpers';
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore';
import { useTestAddStore } from '@/modules/medicalHistory/stores/testAddStore';
import AppointmentAdd from '@/modules/medicalHistory/components/AppointmentAdd/AppointmentAdd.vue';
import TestAddModal from '@/modules/medicalHistory/components/TestAddModal/TestAddModal.vue';
import PatientCard from '@/modules/medicalHistory/components/PatientCard/PatientCard.vue';
import PatientMaxDeeplink from '@/modules/medicalHistory/components/PatientMaxDeeplink/PatientMaxDeeplink.vue';
import TreatmentCard from '@/modules/medicalHistory/components/TreatmentCard/TreatmentCard.vue';
import { usePatientCardStore } from '@/modules/medicalHistory/stores/patientCardStore';
import { useTreatmentStore } from '@/modules/medicalHistory/stores/treatmentStore';
import { useMedicalTableStore } from '@/modules/medicalHistory/stores/medicalTableStore';
import MedicalTable from '@/modules/medicalHistory/components/MedicalTable/MedicalTable.vue';
import MnoChart from '@/modules/medicalHistory/components/MnoChart/MnoChart.vue';
import VitalsCard from '@/modules/medicalHistory/components/VitalsCard/bakulevo/VitalsCard.vue';
import DashboardHeader from './DashboardHeader/DashboardHeader.vue';
import MetricCard from './MetricCard/MetricCard.vue';
import ComplicationRisk from './ComplicationRisk/ComplicationRisk.vue';
import CurrentTherapy from './CurrentTherapy/CurrentTherapy.vue';
import RecentEvents from './RecentEvents/RecentEvents.vue';
import { patientApi } from '@/modules/shared/api/patients';
import { formatMno, formatDoseWithAlternation } from '@/modules/shared/utils/formatters';

export default {
    name: 'MedicalHistory',
    components: {
        AppointmentAdd,
        TestAddModal,
        PatientCard,
        PatientMaxDeeplink,
        TreatmentCard,
        MedicalTable,
        MnoChart,
        VitalsCard,
        DashboardHeader,
        MetricCard,
        ComplicationRisk,
        CurrentTherapy,
        RecentEvents
    },
    props: {
        id: { type: String, default: null }
    },
    data() {
        return {
            loading: true,
            error: null,
            activeTab: 'overview',
            showAppointmentInlineModal: false,
            showPatientModal: false,
            showTreatmentModal: false,
            showVitalsModal: false,
            patientSex: null
        };
    },
    computed: {
        showAppointmentModal() {
            return useAppointmentAddStore().isModalOpen;
        },
        showTestModal() {
            return useTestAddStore().isModalOpen;
        },
        treatmentStore() {
            return useTreatmentStore();
        },
        patientCardStore() {
            return usePatientCardStore();
        },
        activeTreatmentId() {
            const treatment = this.treatmentStore.treatment;
            return treatment ? this.extractIdFromIri(treatment['@id']) : null;
        },
        events() {
            return useMedicalTableStore().events;
        },
        latestMetrics() {
            const out = {
                mno: null, hb: null, heartRate: null,
                systolicPressure: null, diastolicPressure: null,
                saturation: null, weight: null
            };
            for (const e of this.events) {
                if (out.mno == null && e.mno != null) out.mno = e.mno;
                if (out.hb == null && e.hb != null) out.hb = e.hb;
                if (out.heartRate == null && e.heartRate != null) out.heartRate = e.heartRate;
                if (out.systolicPressure == null && e.systolicPressure != null) out.systolicPressure = e.systolicPressure;
                if (out.diastolicPressure == null && e.diastolicPressure != null) out.diastolicPressure = e.diastolicPressure;
                if (out.saturation == null && e.saturation != null) out.saturation = e.saturation;
                if (out.weight == null && e.weight != null) out.weight = e.weight;
            }
            return out;
        },
        metrics() {
            const m = this.latestMetrics;
            const t = this.treatmentStore.treatment;
            const from = t ? t.mnoFrom : null;
            const to = t ? t.mnoTo : null;
            const hasRange = from != null && to != null;
            const mnoTone = m.mno == null
                ? 'neutral'
                : hasRange
                    ? (m.mno < from ? 'info' : (m.mno > to ? 'danger' : 'success'))
                    : 'neutral';
            return [
                { label: 'МНО (INR)', value: m.mno != null ? String(m.mno) : '—', hint: hasRange ? `Целевой диапазон ${formatMno(from)}–${formatMno(to)}` : '', tone: mnoTone },
                { label: 'Гемоглобин', value: m.hb != null ? `${m.hb} г/л` : '—', hint: '', tone: 'neutral' },
                { label: 'ЧСС', value: m.heartRate != null ? `${m.heartRate} уд/мин` : '—', hint: '', tone: 'neutral' },
                { label: 'АД', value: (m.systolicPressure != null && m.diastolicPressure != null) ? `${m.systolicPressure}/${m.diastolicPressure} мм рт.ст.` : '—', hint: '', tone: 'neutral' },
                { label: 'SpO₂', value: m.saturation != null ? `${m.saturation}%` : '—', hint: '', tone: 'neutral' },
                { label: 'Вес', value: m.weight != null ? `${m.weight} кг` : '—', hint: '', tone: 'neutral' }
            ];
        },
        chartData() {
            return this.events
                .filter(e => e.mno !== null && e.mno !== undefined)
                .map(e => ({ date: e.date, inr: e.mno, dose: e.prescribedDose }));
        },
        doctorName() {
            for (const e of this.events) {
                if (e.doctorName) return e.doctorName;
            }
            return '';
        },
        latestDose() {
            for (const e of this.events) {
                if (e.prescribedDose != null && e.prescribedDose !== '—' && Number(e.prescribedDose) > 0) {
                    return formatDoseWithAlternation(e.prescribedDose, e.prescribedDose2);
                }
            }
            return null;
        },
        latestDoseDate() {
            for (const e of this.events) {
                if (e.prescribedDose != null && e.prescribedDose !== '—' && Number(e.prescribedDose) > 0) return e.displayDate;
            }
            return '';
        },
        drugName() {
            const t = this.treatmentStore.treatment;
            if (!t) return '—';
            if (t.drugName) return t.drugName;
            const drugId = t.drug ? this.extractIdFromIri(t.drug) : null;
            if (drugId) {
                const drug = this.treatmentStore.allDrugs.find(d => d.id === drugId);
                if (drug) return drug.nominative || '—';
            }
            return '—';
        },
        testDrugGenitive() {
            const treatment = this.treatmentStore.treatment;
            if (!treatment || !treatment.drug) return '';
            const drugId = this.extractIdFromIri(treatment.drug);
            const drug = this.treatmentStore.allDrugs.find(d => this.extractIdFromIri(d['@id']) === drugId);
            return drug?.genitive || '';
        },
        recentEvents() {
            const t = this.treatmentStore.treatment;
            const from = t ? t.mnoFrom : null;
            const to = t ? t.mnoTo : null;
            return this.events.slice(0, 5).map(e => {
                let title;
                let tone = 'neutral';
                if (e.type === 'appointment') {
                    title = `Назначена доза ${formatDoseWithAlternation(e.prescribedDose, e.prescribedDose2)}`;
                    tone = 'info';
                } else if (e.mno != null) {
                    title = `Анализ МНО ${e.mno}`;
                    if (from != null && to != null) {
                        if (e.mno < from) tone = 'info';
                        else if (e.mno > to) tone = 'danger';
                        else tone = 'success';
                    }
                } else {
                    title = 'Показатели обновлены';
                }
                return { title, date: e.displayDate, tone };
            });
        }
    },
    watch: {
        id: {
            immediate: true,
            async handler(newId) {
                if (newId) {
                    const patientCardStore = usePatientCardStore();
                    const treatmentStore = useTreatmentStore();
                    await Promise.all([
                        patientCardStore.fetchPatient(newId),
                        treatmentStore.fetchTreatment(newId),
                        this.loadPatientSex(newId)
                    ]);
                    this.loadPatientData();
                }
            }
        }
    },
    methods: {
        extractIdFromIri,

        async loadPatientSex(id) {
            try {
                const raw = await patientApi.getOne(id);
                this.patientSex = raw ? raw.sex : null;
            } catch (err) {
                this.patientSex = null;
            }
        },

        async loadPatientData() {
            this.loading = true;
            useAppointmentAddStore().setTreatmentActive(false);
            this.error = null;

            try {
                const treatmentStore = useTreatmentStore();
                const treatment = treatmentStore.treatment;

                if (!treatment) {
                    this.loading = false;
                    return;
                }

                const isActive = treatment.realEndDt === null || treatment.realEndDt === undefined;
                useAppointmentAddStore().setTreatmentActive(isActive);

                if (treatment['@id']) {
                    await Promise.all([
                        useMedicalTableStore().fetchMedicalData(treatment['@id']),
                        treatmentStore.loadDrugsIfNeeded()
                    ]);
                }
            } catch (err) {
                console.error('Ошибка загрузки истории:', err);
                this.error = 'Не удалось загрузить данные пациента.';
            } finally {
                this.loading = false;
            }
        },

        closeAppointmentModal() {
            useAppointmentAddStore().closeModal();
        },
        onAppointmentSaved() {
            this.loadPatientData();
            useAppointmentAddStore().closeModal();
        },
        openTestModal() {
            useTestAddStore().openModal();
        },
        closeTestModal() {
            useTestAddStore().closeModal();
        },
        onTestSaved() {
            this.loadPatientData();
            useTestAddStore().closeModal();
        },
        openAppointmentInlineModal() {
            this.showAppointmentInlineModal = true;
        },
        onAppointmentInlineSaved() {
            this.loadPatientData();
            this.showAppointmentInlineModal = false;
        },
        openPatientEdit() {
            this.showPatientModal = true;
        },
        closePatientEdit() {
            this.showPatientModal = false;
        },
        onPatientSaved() {
            this.showPatientModal = false;
            if (this.id) {
                this.loadPatientSex(this.id);
            }
        },
        openTreatmentEdit() {
            this.showTreatmentModal = true;
        },
        closeTreatmentEdit() {
            this.showTreatmentModal = false;
        },
        openVitalsEdit() {
            this.showVitalsModal = true;
        },
        closeVitalsEdit() {
            this.showVitalsModal = false;
            this.loadPatientData();
        }
    }
};
