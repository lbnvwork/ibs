export default {
  name: 'AppointmentAdd',
  props: {
    treatment: { type: String, default: null },
    drugId: { type: Number, default: null },
    treatmentId: { type: Number, default: null },
  },
  emits: ['close', 'saved'],
};