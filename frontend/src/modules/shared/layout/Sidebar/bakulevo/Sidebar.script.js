import { HOME_PATH, PATIENT_ADD_PATH } from '@/router/paths';
import bakulevoLogo from '@/assets/logos/bakulevo.png';
import { useAppointmentAddStore } from '@/modules/medicalHistory/stores/appointmentAddStore';
import { useTestAddStore } from '@/modules/medicalHistory/stores/testAddStore';
import { useAuthStore } from '@/modules/shared/stores/authStore';

// Иконки (SVG-пути без обёртки) — stroke=currentColor, размер задаёт CSS.
const ICONS = {
    patients: "<path d='M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2' stroke='currentColor' stroke-width='2' fill='none'/><circle cx='9' cy='7' r='4' stroke='currentColor' stroke-width='2' fill='none'/><path d='M23 21v-2a4 4 0 00-3-3.87' stroke='currentColor' stroke-width='2' fill='none'/><path d='M16 3.13a4 4 0 010 7.75' stroke='currentColor' stroke-width='2' fill='none'/>",
    recommendations: "<path d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' stroke='currentColor' stroke-width='2' fill='none'/>",
    patientList: "<path d='M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01' stroke='currentColor' stroke-width='2' fill='none'/>",
    patientAdd: "<path d='M12 4v16m8-8H4' stroke='currentColor' stroke-width='2' fill='none'/><circle cx='12' cy='12' r='10' stroke='currentColor' stroke-width='2' fill='none'/>",
    appointment: "<path d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' stroke='currentColor' stroke-width='2' fill='none'/>",
    testAdd: "<path d='M10 2v6.5L4.5 19a2 2 0 001.7 3h11.6a2 2 0 001.7-3L14 8.5V2' stroke='currentColor' stroke-width='2' fill='none'/><path d='M8 2h8' stroke='currentColor' stroke-width='2'/><path d='M7 15h10' stroke='currentColor' stroke-width='2'/>",
    logout: "<path d='M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9' stroke='currentColor' stroke-width='2' fill='none'/>",
};

export default {
    name: 'Sidebar',
    data() {
        return {
            bakulevoLogo,
            // Раскрывашки, раскрытые по умолчанию. Только рабочий функционал (доработка №1):
            // заглушки (sendMessage/editData/calendar/aiHelp/statistics/disabledPatients/chat/print/saveFormats) удалены.
            sidebarGroups: [
                {
                    id: 'patients',
                    title: 'Пациенты',
                    icon: ICONS.patients,
                    expanded: true,
                    items: [
                        { name: 'patientList', label: 'Список пациентов', icon: ICONS.patientList, title: 'Перейти к списку пациентов' },
                        { name: 'patientAdd', label: 'Новый пациент', icon: ICONS.patientAdd, title: 'Добавить в систему пациента или ЛПУ' }
                    ]
                },
                {
                    id: 'recommendations',
                    title: 'Рекомендации',
                    icon: ICONS.recommendations,
                    expanded: true,
                    items: [
                        { name: 'appointment', label: 'Назначение', icon: ICONS.appointment, title: 'Сформировать рекомендации пациенту' },
                        { name: 'testAdd', label: 'Анализ', icon: ICONS.testAdd, title: 'Добавить анализ пациенту' }
                    ]
                }
            ]
        };
    },
    computed: {
        isBackButtonActive() {
            return this.$route && this.$route.path !== HOME_PATH;
        },
        backButtonTitle() {
            return 'Вернуться к списку пациентов';
        }
    },
    methods: {
        handleBackButton() {
            const backTarget = this.$route.meta.backTarget;
            if (backTarget) {
                this.$router.push(backTarget);
            } else {
                this.$router.push(HOME_PATH);
            }
        },
        toggleGroup(group) {
            group.expanded = !group.expanded;
        },
        isItemDisabled(item) {
            // «Назначение»/«Анализ» доступны только на карточке пациента при активном лечении.
            if (item.name === 'appointment' || item.name === 'testAdd') {
                if (this.$route.name === 'MedicalHistory') {
                    return !useAppointmentAddStore().isTreatmentActive;
                }
                return true;
            }
            return false;
        },
        handleItemClick(item) {
            console.log('Clicked:', item.name);
            switch (item.name) {
                case 'patientList':
                    this.$router.push(HOME_PATH);
                    break;
                case 'patientAdd':
                    this.$router.push(PATIENT_ADD_PATH);
                    break;
                case 'appointment':
                    if (this.$route.name === 'MedicalHistory' && useAppointmentAddStore().isTreatmentActive) {
                        useAppointmentAddStore().openModal();
                    }
                    break;
                case 'testAdd':
                    if (this.$route.name === 'MedicalHistory' && useAppointmentAddStore().isTreatmentActive) {
                        useTestAddStore().openModal();
                    }
                    break;
                default:
                    console.log('Not implemented yet');
            }
        },
        handleLogout() {
            useAuthStore().logout();
        }
    }
};
