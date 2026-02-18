import { calculateAge, formatPhone } from '@/utils/formatters'

/**
 * Для боковой панели (PatientListPanel)
 */
export function transformForListPanel(patient) {
    if (!patient) return null

    const { lastname, firstname, secondName } = patient

    // Формируем фамилию + инициалы
    let name = lastname || ''
    if (firstname) {
        name += ' ' + firstname.charAt(0).toUpperCase() + '.'
    }
    if (secondName) {
        name += ' ' + secondName.charAt(0).toUpperCase() + '.'
    }
    if (!name.trim()) name = 'Без имени'

    return {
        id: patient.id,
        name: name,
        status: patient.status || 'активный'
    }
}

/**
 * Для таблицы мониторинга (PatientMonitoring)
 * Обратите внимание: здесь мы ожидаем, что в объекте пациента уже есть поля,
 * которые в реальности могут приходить из другого эндпоинта (/test_histories).
 * Сейчас используем моковую структуру, позже адаптируем под реальное API.
 */
export function transformForMonitoring(patient) {
    if (!patient) return null
    const fullName = [patient.lastname, patient.firstname, patient.secondName]
        .filter(Boolean).join(' ').trim() || 'Без имени'
    const age = calculateAge(patient.birthday)
    return {
        id: patient.id,
        name: fullName,
        age: age ? `${age} лет` : '',
        smsStatus: patient.smsStatus || '📱✓', // временно, будет из связанных данных
        diagnosis: patient.diagnosis || 'Диагноз не указан', // тоже временно
        indicators: patient.indicators || '<span>MHO - 2.5</span>', // заглушка
        comment: patient.comment || '—',
        highlightRed: patient.highlightRed || false,
        highlightBlue: patient.highlightBlue || false
    }
}