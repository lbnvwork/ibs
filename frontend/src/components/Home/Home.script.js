import PatientMonitoring from '@/components/PatientList/PatientMonitoring/PatientMonitoring.vue';
import PatientWorkList from '@/components/PatientList/PatientWorkList/PatientWorkList.vue';

export default {
  name: 'Home',
  components: { PatientMonitoring, PatientWorkList },
  data() {
    return {
      activeFilter: 'monitoring',
    };
  },
};