export function calculateAge(birthday) {
    if (!birthday) return null
    try {
        const birth = new Date(birthday)
        const today = new Date()
        let age = today.getFullYear() - birth.getFullYear()
        const monthDiff = today.getMonth() - birth.getMonth()
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) age--
        return age
    } catch {
        return null
    }
}

export function formatPhone(phone) {
    if (!phone) return '—'

    return phone
}

export function formatDate(dateStr) {
    if (!dateStr) return ''
    try {
        return new Date(dateStr).toLocaleDateString('ru-RU')
    } catch {
        return dateStr
    }
}