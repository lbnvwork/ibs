import PatientMonitoring from '@/modules/patientManagement/components/PatientMonitoring/PatientMonitoring.vue';
import PatientWorkList from '@/modules/patientManagement/components/PatientWorkList/PatientWorkList.vue';

export default {
  name: 'Home',
  components: { PatientMonitoring, PatientWorkList },
  data() {
    return {
      activeFilter: 'monitoring',
    };
  },
};