export default {
    name: 'Sidebar',
    data() {
        return {
            sidebarItems: [
                {
                    title: "Добавить в систему пациента или ЛПУ",
                    icon: "<path d='M12 4v16m8-8H4' stroke='currentColor' stroke-width='2' fill='none'/><circle cx='12' cy='12' r='10' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Сформировать рекомендации пациенту",
                    icon: "<path d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Отправить сообщение пациенту или группе пациентов",
                    icon: "<path d='M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Редактирование данных ЛПУ или пациента",
                    icon: "<path d='M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7' stroke='currentColor' stroke-width='2' fill='none'/><path d='M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },

                { type: "divider" },

                {
                    title: "Календарь планировщик пациентов",
                    icon: "<rect x='3' y='4' width='18' height='18' rx='2' ry='2' stroke='currentColor' stroke-width='2' fill='none'/><line x1='16' y1='2' x2='16' y2='6' stroke='currentColor' stroke-width='2'/><line x1='8' y1='2' x2='8' y2='6' stroke='currentColor' stroke-width='2'/><line x1='3' y1='10' x2='21' y2='10' stroke='currentColor' stroke-width='2'/>",
                    type: "button"
                },
                {
                    title: "Помощь искусственного интеллекта",
                    icon: "<path d='M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Статанализ данных",
                    icon: "<path d='M3 3v18h18' stroke='currentColor' stroke-width='2' fill='none'/><path d='M18.4 7.6a2 2 0 010 2.8l-8 8-4 1 1-4 8-8a2 2 0 012.8 0z' stroke='currentColor' stroke-width='2' fill='none'/><path d='M14 5l5 5' stroke='currentColor' stroke-width='2'/>",
                    type: "button"
                },

                { type: "divider" },

                {
                    title: "Список маломобильных пациентов",
                    icon: "<circle cx='12' cy='12' r='10' stroke='currentColor' stroke-width='2' fill='none'/><circle cx='12' cy='10' r='3' stroke='currentColor' stroke-width='2' fill='none'/><path d='M7 20.662V19a2 2 0 012-2h6a2 2 0 012 2v1.662' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Вход в чат пациентов",
                    icon: "<path d='M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Печать из выделенного окна",
                    icon: "<path d='M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2' stroke='currentColor' stroke-width='2' fill='none'/><path d='M6 14h12v8H6z' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                },
                {
                    title: "Сохранить данные в различных форматах",
                    icon: "<path d='M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z' stroke='currentColor' stroke-width='2' fill='none'/><polyline points='17 21 17 13 7 13 7 21' stroke='currentColor' stroke-width='2' fill='none'/><polyline points='7 3 7 8 15 8' stroke='currentColor' stroke-width='2' fill='none'/>",
                    type: "button"
                }
            ]
        }
    },
    computed: {
        isPatientHistory() {
            return this.$route && this.$route.path.startsWith('/patient/')
        },
        backButtonTitle() {
            return this.isPatientHistory
                ? 'Вернуться к списку пациентов'
                : 'Отмена действия, возврат'
        }
    },
    methods: {
        handleBackButton() {
            if (this.isPatientHistory && this.$router) {
                this.$router.push('/')
            }
        },
        handleButtonClick(title) {
            console.log('Clicked:', title)
            // Здесь можно добавить логику для каждой кнопки
        }
    }
}