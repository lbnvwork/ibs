import { monitoringPatients } from '@/data/patients.js';

export default {
  name: 'PatientMonitoring',
  data() {
    return {
      activeFilter: 'monitoring',
      activeTab: 'warfarin',
      selectedDiagnosis: 'all',
      tabs: [
        { id: 'warfarin', name: 'Варфарин' },
        { id: 'dabigatran', name: 'Дабигатран' },
        { id: 'rivaroxaban', name: 'Ривароксабан' },
        { id: 'apixaban', name: 'Апиксабан' },
        { id: 'edoxaban', name: 'Эдоксабан' },
        { id: 'aspirin', name: 'Аспирин', style: 'color:#b44; font-weight:700;' },
        { id: 'clopidogrel', name: 'Клопидогрел' },
        { id: 'ticagrelor', name: 'Тикагрелор' },
        { id: 'nmg', name: 'НМГ' },
        { id: 'nfg', name: 'НФГ' }
      ],
      warfarinPatients: monitoringPatients // Используем данные из файла
    }
  }
}