export function parseApiError(err) {
    const response = err.response?.data;
    if (response?.violations) {
        return { violations: response.violations };
    }
    if (response?.detail) {
        const detail = response.detail.includes('NULL')
            ? 'Это значение не должно быть пустым.'
            : response.detail;
        return { generalError: detail };
    }
    return { generalError: 'Ошибка сервера' };
}