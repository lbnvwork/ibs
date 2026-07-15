// frontend/src/modules/medicalHistory/components/VitalsCard/VitalsCard.script.js

import { usePatientVitalsLatestStore } from '@/modules/medicalHistory/stores/patientVitalsLatestStore';

export default {
  name: 'VitalsCard',
  props: {
    patientId: { type: [String, Number], required: true },
    treatmentId: { type: [String, Number], default: null },
  },
  data() {
    return {
      editing: false,
      saving: false,
      saveError: null,
      form: {
        hb: null,
        heartRate: null,
        systolicPressure: null,
        diastolicPressure: null,
        saturation: null,
        weight: null,
        recordDt: '',
        comment: '',
      },
      originalForm: null,
    };
  },
  computed: {
    store() {
      return usePatientVitalsLatestStore();
    },
    latest() {
      return this.store.latest;
    },
    loading() {
      return this.store.loading;
    },
    error() {
      return this.store.error;
    },
    hasAnyData() {
      const l = this.latest;
      return l && (
        l.hb !== null || l.heartRate !== null || l.systolicPressure !== null ||
        l.diastolicPressure !== null || l.saturation !== null || l.weight !== null
      );
    },
    lastUpdatedFormatted() {
      if (!this.latest?.lastUpdated) return null;
      return new Date(this.latest.lastUpdated).toLocaleString('ru-RU');
    },
  },
  watch: {
    patientId: {
      immediate: true,
      handler(newId) {
        if (newId) {
          this.store.fetchLatest(newId);
        }
      },
    },
  },
  methods: {
    startEditing() {
      this.form = {
        hb: this.latest?.hb ?? null,
        heartRate: this.latest?.heartRate ?? null,
        systolicPressure: this.latest?.systolicPressure ?? null,
        diastolicPressure: this.latest?.diastolicPressure ?? null,
        saturation: this.latest?.saturation ?? null,
        weight: this.latest?.weight ?? null,
        recordDt: this.formatDateTimeLocal(new Date()),
        comment: '',
      };
      this.originalForm = JSON.parse(JSON.stringify(this.form));
      this.editing = true;
      this.saveError = null;
    },

    cancelEditing() {
      if (this.originalForm) {
        this.form = JSON.parse(JSON.stringify(this.originalForm));
      }
      this.editing = false;
      this.saveError = null;
    },

    async save() {
      if (!this.validateForm()) return;

      this.saving = true;
      this.saveError = null;
      try {
        const payload = {
          patient: `/api/patients/${this.patientId}`,
          treatment: this.treatmentId ? `/api/treatments/${this.treatmentId}` : null,
          recordDt: this.form.recordDt,
          hb: this.form.hb,
          heartRate: this.form.heartRate,
          systolicPressure: this.form.systolicPressure,
          diastolicPressure: this.form.diastolicPressure,
          saturation: this.form.saturation,
          weight: this.form.weight,
          comment: this.form.comment || '',
        };
        await this.store.saveMeasurement(payload);
        await this.store.fetchLatest(this.patientId);
        this.editing = false;
      } catch (err) {
        console.error(err);
        this.saveError = err.response?.data?.detail || 'Ошибка сохранения.';
      } finally {
        this.saving = false;
      }
    },

    validateForm() {
      const { hb, heartRate, systolicPressure, diastolicPressure, saturation, weight } = this.form;
      if (
        hb === null && heartRate === null && systolicPressure === null &&
        diastolicPressure === null && saturation === null && weight === null
      ) {
        this.saveError = 'Укажите хотя бы один показатель.';
        return false;
      }
      return true;
    },

    formatValue(value, unit) {
      return value !== null && value !== undefined ? `${value} ${unit}` : '—';
    },

    formatDateTimeLocal(date) {
      const pad = (n) => String(n).padStart(2, '0');
      return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    },
  },
};