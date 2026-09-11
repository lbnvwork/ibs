import { isBakulevo } from '@/themes';
import PatientMonitoring from '@/modules/patientManagement/components/PatientMonitoring/PatientMonitoring.vue';
import PatientMonitoringBakulevo from '@/modules/patientManagement/components/PatientMonitoring/bakulevo/PatientMonitoring.vue';
import PatientWorkList from '@/modules/patientManagement/components/PatientWorkList/PatientWorkList.vue';
import PatientWorkListBakulevo from '@/modules/patientManagement/components/PatientWorkList/bakulevo/PatientWorkList.vue';

export default {
  name: 'Home',
  components: {
    PatientMonitoring: isBakulevo ? PatientMonitoringBakulevo : PatientMonitoring,
    PatientWorkList: isBakulevo ? PatientWorkListBakulevo : PatientWorkList,
  },
  data() {
    return {
      activeFilter: isBakulevo ? 'patientList' : 'monitoring',
      isBakulevo,
    };
  },
};