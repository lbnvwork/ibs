export function parseApiError(err) {
    const response = err.response?.data;
    if (!response) return err.message || 'Неизвестная ошибка';

    if (response.violations && Array.isArray(response.violations)) {
        const messages = response.violations.map(v => `${v.propertyPath}: ${v.message}`).join('\n');
        return messages;
    }

    if (response.detail) return response.detail;

    if (response.title) return response.title;

    return 'Ошибка сервера';
}