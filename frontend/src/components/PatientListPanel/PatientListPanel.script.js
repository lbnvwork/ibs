import { mapState, mapActions } from 'pinia'
import { usePatientStore } from '@/stores/patientStore'
import { useHospitalStore } from '@/stores/hospitalStore'
import { calculateAge } from '@/utils/formatters'
import debounce from 'lodash/debounce'
import { useDrugGroupStore } from '@/stores/drugGroupStore'
import { useAuthStore } from '@/stores/authStore'

export default {
  name: 'PatientListPanel',
  data: () => ({
    searchQuery: '',
    observer: null,
    authStore: null,
  }),
  computed: {
    ...mapState(useDrugGroupStore, {
      drugGroups: 'drugGroups',
      drugGroupOptions: 'drugGroupOptions',
      drugGroupLoading: 'loading',
      drugGroupError: 'error',
    }),

    ...mapState(usePatientStore, {
      rawPatients: 'rawPatients',
      allPatientIds: 'allPatientIds',
      displayedPatients: 'displayedPatients',
      hasMore: 'hasMore',
      patientLoading: 'loading',
      patientError: 'error',
      selectedPatient: 'selectedPatient',
      hospitalFilter: 'hospitalFilter',
      drugGroupFilter: 'drugGroupFilter',
    }),

    ...mapState(useHospitalStore, {
      hospitals: 'hospitals',
      hospitalOptions: 'hospitalOptions',
      hospitalLoading: 'loading',
      hospitalError: 'error',
    }),

    isLoginPage() {
      return this.$route?.meta?.isLoginPage === true;
    },

    isAuthenticated() {
      return this.authStore?.isAuthenticated || false;
    },

    progressPercentage() {
      return 0
    },

    selectedPatientInfo() {
      if (!this.selectedPatient) {
        return { name: '', info: 'Выберите пациента' };
      }
      const patient = this.selectedPatient
      const fullName = [patient.lastname, patient.firstname, patient.secondName]
          .filter(Boolean).join(' ').trim() || 'Без имени';
      const age = calculateAge(patient.birthday);
      const ageText = age ? `${age} лет` : 'Возраст не указан';
      const phone = patient.smsPhone || 'телефон не указан';
      return {
        name: fullName,
        info: `${ageText}, тел: ${phone}`
      }
    }
  },
  methods: {
    ...mapActions(usePatientStore, [
      'loadMore',
      'selectPatient',
      'searchPatients',
      'clearSearch',
      'setHospitalFilter',
      'setDrugGroupFilter',
    ]),
    ...mapActions(useHospitalStore, [
      'loadHospitals',
    ]),
    ...mapActions(useDrugGroupStore, [
      'loadDrugGroups',
    ]),

    async loadInitialData() {
      try {
        await Promise.all([
          this.loadHospitals(),
          this.loadDrugGroups(),
          this.loadMore(),
        ])
      } catch (err) {
        console.error('Ошибка при инициализации:', err);
      }
    },

    onClearSearch() {
      this.searchQuery = '';
      this.clearSearch();
    },

    onDrugGroupChange(event) {
      const groupId = event.target.value ? parseInt(event.target.value) : null;
      this.setDrugGroupFilter(groupId);
    },

    async handlePatientClick(patient) {
      try {
        await this.selectPatient(patient.id);
        this.$router.push(`/patient/${patient.id}`);
      } catch (error) {
        console.error('Ошибка при выборе пациента:', error);
      }
    },

    handlePatientHover: debounce(function(patient) {
      this.selectPatient(patient.id).catch(err => {
        console.error('Ошибка при наведении:', err);
      })
    }, 100),

    onSearchInput: debounce(function() {
      if (this.searchQuery.length >= 2) {
        this.searchPatients(this.searchQuery).catch(err => {
          console.error('Ошибка поиска:', err);
        })
      } else if (this.searchQuery.length === 0) {
        this.clearSearch();
      }
    }, 300),

    onHospitalChange(event) {
      const hospitalId = event.target.value ? parseInt(event.target.value) : null;
      this.setHospitalFilter(hospitalId);
    },

    setupObserver() {
      if (this.isLoginPage || !this.isAuthenticated) {
        return;
      }

      const listElement = this.$refs.listContainer;
      if (!listElement) return;

      this.observer = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (this.isLoginPage) return;
        if (entry.isIntersecting && this.hasMore && !this.patientLoading) {
          this.loadMore();
        }
      }, {
        root: listElement,
        threshold: 0.1,
      });

      const trigger = this.$refs.trigger;
      if (trigger) {
        this.observer.observe(trigger);
      }
    },
  },
  created() {
    this.authStore = useAuthStore();
    if (!this.isLoginPage && this.isAuthenticated) {
      this.loadInitialData();
    }
  },
  mounted() {
    if (!this.isLoginPage && this.isAuthenticated) {
      this.setupObserver();
    }
  },
  updated() {
    if (this.observer) {
      this.observer.disconnect();
      this.observer = null;
      if (!this.isLoginPage && this.isAuthenticated) {
        this.setupObserver();
      }
    }
    this.setupObserver();
  },
  beforeUnmount() {
    if (this.observer) {
        this.observer.disconnect();
        this.observer = null;
    }
  },
}