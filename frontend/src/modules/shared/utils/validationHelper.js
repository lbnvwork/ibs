export function validateForm(data, rules, extraChecks = null) {
    const errors = {};

    for (const [field, rule] of Object.entries(rules)) {
        const value = data[field];
        if (rule.required && (value === null || value === undefined || value === '')) {
            errors[field] = rule.message;
        } else if (rule.validator && !rule.validator(value)) {
            errors[field] = rule.errorMsg;
        }
    }

    if (extraChecks) {
        extraChecks(errors, data);
    }

    return errors;
}