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

export function formatPhone(value) {
    if (!value) return '-';
    const digits = value.replace(/\D/g, '');
    if (digits.length === 0) return '';
    if (digits.length <= 1) return digits;
    if (digits.length <= 4) return `8(${digits.slice(1,4)}`;
    if (digits.length <= 7) return `8(${digits.slice(1,4)})${digits.slice(4,7)}`;
    if (digits.length <= 9) return `8(${digits.slice(1,4)})${digits.slice(4,7)}-${digits.slice(7,9)}`;
    return `8(${digits.slice(1,4)})${digits.slice(4,7)}-${digits.slice(7,9)}-${digits.slice(9,11)}`;
}

export function formatDate(dateStr) {
    if (!dateStr) return '—';
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

export function formatPassport(value) {
    if (!value) return '';
    const digits = value.replace(/\D/g, '');
    if (digits.length <= 4) return digits;
    return `${digits.slice(0,4)} ${digits.slice(4,10)}`;
}

export function formatSnils(value) {
    if (!value) return '';
    const digits = value.replace(/\D/g, '');
    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return `${digits.slice(0,3)}-${digits.slice(3,6)}`;
    if (digits.length <= 9) return `${digits.slice(0,3)}-${digits.slice(3,6)}-${digits.slice(6,9)}`;
    return `${digits.slice(0,3)}-${digits.slice(3,6)}-${digits.slice(6,9)} ${digits.slice(9,11)}`;
}

