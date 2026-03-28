import PatientMonitoring from '@/components/PatientMonitoring/PatientMonitoring.vue';
import PatientWorkList from '@/components/PatientWorkList/PatientWorkList.vue';

export default {
  name: 'Home',
  components: { PatientMonitoring, PatientWorkList },
  data() {
    return {
      activeFilter: 'monitoring',
    };
  },
};