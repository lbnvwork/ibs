export function calculateAge(birthday) {
    if (!birthday) return null;
    try {
        const birth = new Date(birthday);
        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) age--;
        
        return age;
    } catch {
        return null;
    }
}

export function formatPhone(phone) {
    if (!phone) return '—';

    return phone;
}

export function formatDate(dateStr) {
    if (!dateStr) return '';
    try {
        return new Date(dateStr).toLocaleDateString('ru-RU');
    } catch {
        return dateStr;
    }
}

export function formatAge(age) {
    if (age === null || age === undefined) return '—';
    const lastDigit = age % 10;
    const lastTwo = age % 100;
 
    if (lastTwo >= 11 && lastTwo <= 14) 
        return `${age} лет`;
    if (lastDigit === 1) 
        return `${age} год`;
    if (lastDigit >= 2 && lastDigit <= 4) 
        return `${age} года`;
    return `${age} лет`;
}