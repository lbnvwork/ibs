import PatientMonitoring from '@/modules/physicianDashboard/components/PatientMonitoring/PatientMonitoring.vue';
import PatientWorkList from '@/modules/physicianDashboard/components/PatientWorkList/PatientWorkList.vue';

export default {
  name: 'Home',
  components: { PatientMonitoring, PatientWorkList },
  data() {
    return {
      activeFilter: 'monitoring',
    };
  },
};