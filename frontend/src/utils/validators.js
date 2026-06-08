export function isValidPhone(phone) {
    const digits = phone.replace(/\D/g, '');
    return digits.length === 11 && (digits[0] === '8' || digits[0] === '7');
}

export function isValidSnils(snils) {
    const digits = snils.replace(/\D/g, '');
    return digits.length === 11;
}

export function isValidPassport(passport) {
    const parts = passport.split(' ');
    if (parts.length !== 2) return false;
    const series = parts[0].replace(/\D/g, '');
    const number = parts[1].replace(/\D/g, '');
    return series.length === 4 && number.length === 6;
}

export function isValidEmail(email) {
    if (!email) return true; // необязательное поле
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}