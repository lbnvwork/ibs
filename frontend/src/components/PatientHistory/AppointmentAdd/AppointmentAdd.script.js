import apiClient from '@/api/client';

export default {
  name: 'AppointmentAdd',
  props: {
    treatment: { type: String, required: true },
    drugId: { type: Number, required: true },
    treatmentId: { type: Number, required: true }
  },
  emits: ['close', 'saved'],
  data() {
    return {
      appointmentDt: new Date().toISOString().slice(0, 10),
      comment: '',
      dose: null,
      selectedVariant: null,
      variants: [],
      explanation: '',
      isLoading: false,
      error: null
    };
  },
  computed: {
    canSave() {
      return this.dose !== null && this.dose > 0;
    }
  },
  methods: {
    async calculateDose() {
      this.isLoading = true;
      this.error = null;
      this.variants = [];
      this.explanation = '';
      this.selectedVariant = null;

      try {
        const response = await apiClient.get('/dosage/recommendation', {
          params: { treatment_id: this.treatmentId }
        });
        const data = response.data;
        this.variants = data.variants || [];
        this.explanation = data.explanation || '';

        if (this.variants.length > 0) {
          this.selectedVariant = 0;
          this.dose = this.variants[0].dose;
        }
      } catch (err) {
        console.error('Ошибка расчёта дозы:', err);
        this.error = 'Не удалось рассчитать дозу. Проверьте соединение или повторите позже.';
      } finally {
        this.isLoading = false;
      }
    },

    onVariantSelected() {
      if (this.selectedVariant !== null) {
        this.dose = this.variants[this.selectedVariant].dose;
      }
    },

    onDoseManualChange() {
      this.selectedVariant = null;
    },

    async save() {
      if (!this.canSave) return;

      const payload = {
        treatment: this.treatment,
        appointmentDt: this.appointmentDt,
        doze: this.dose,
        drug: `/api/drugs/${this.drugId}`,
        comment: this.comment || null
      };

      try {
        await apiClient.post('/appointments', payload);
        this.$emit('saved');
      } catch (err) {
        console.error('Ошибка сохранения назначения:', err);
        this.error = 'Не удалось сохранить назначение.';
      }
    }
  }
};