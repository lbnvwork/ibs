import { mapState, mapActions } from 'pinia'
import { usePatientStore } from '@/stores/patientStore'
import { transformForListPanel } from '@/transformers/patientTransformers'
import { calculateAge } from '@/utils/formatters'
import debounce from 'lodash/debounce'

export default {
  name: 'PatientListPanel',
  data: () => ({ searchQuery: '' }),
  computed: {
    ...mapState(usePatientStore, [
      'rawPatients',
      'pageCache',
      'pagination',
      'loading',
      'error',
      'selectedPatient'
    ]),
    displayedPatients() {
      const pageIds = this.pageCache.get(this.pagination.currentPage) || []
      return pageIds
          .map(id => this.rawPatients.get(id))
          .filter(Boolean)
          .map(transformForListPanel)
    },
    filteredPatients() {
      if (!this.searchQuery) return this.displayedPatients
      const q = this.searchQuery.toLowerCase()
      return this.displayedPatients.filter(p =>
          p.name.toLowerCase().includes(q)
      )
    },
    selectedPatientInfo() {
      if (!this.selectedPatient) {
        return { name: '', info: 'Выберите пациента' }
      }
      const patient = this.selectedPatient
      const fullName = [patient.lastname, patient.firstname, patient.secondName]
          .filter(Boolean).join(' ').trim() || 'Без имени'
      const age = calculateAge(patient.birthday)
      const ageText = age ? `${age} лет` : 'Возраст не указан'
      const phone = patient.smsPhone || 'телефон не указан'
      return {
        name: fullName,
        info: `${ageText}, тел: ${phone}`
      }
    },
    progressPercentage() {
      const total = this.displayedPatients.length
      return total ? Math.round((this.filteredPatients.length / total) * 100) : 0
    },
  },
  methods: {
    ...mapActions(usePatientStore, [
      'loadPatients',
      'selectPatient',
      'searchPatients',
      'clearSearch'
    ]),
    async handlePatientClick(patient) {
      try {
        await this.selectPatient(patient.id)
        this.$router.push(`/patient/${patient.id}`)
      } catch (error) {
        console.error('Ошибка при выборе пациента:', error)
      }
    },
    handlePatientHover: debounce(function(patient) {
      this.selectPatient(patient.id).catch(err => {
        console.error('Ошибка при наведении:', err)
      })
    }, 100),
    onSearchInput: debounce(function() {
      if (this.searchQuery.length >= 2) {
        this.searchPatients(this.searchQuery).catch(err => {
          console.error('Ошибка поиска:', err)
        })
      } else if (this.searchQuery.length === 0) {
        this.clearSearch()
      }
    }, 300),
  },
  created() {
    this.loadPatients(1).catch(err => {
      console.error('Не удалось загрузить первую страницу:', err)
    })
  },
}