export function buildIndicators(testHistory, vitals) {
    const parts = [];

    // МНО
    if (testHistory?.mno !== null && testHistory?.mno !== undefined) {
        parts.push(
            `<span class="indicator-item indicator-mno">` +
            `<span class="indicator-label">МНО</span>` +
            `<span class="indicator-value">${testHistory.mno}</span>` +
            `</span>`
        );
    }

    if (vitals) {
        if (vitals.hb !== null && vitals.hb !== undefined) {
            parts.push(
                `<span class="indicator-item">` +
                `<span class="indicator-label">Hb</span>` +
                `<span class="indicator-value">${vitals.hb}</span>` +
                `</span>`
            );
        }
        if (vitals.heartRate !== null && vitals.heartRate !== undefined) {
            parts.push(
                `<span class="indicator-item">` +
                `<span class="indicator-label">ЧСС</span>` +
                `<span class="indicator-value">${vitals.heartRate}</span>` +
                `</span>`
            );
        }
        if (vitals.systolicPressure !== null && vitals.diastolicPressure !== null) {
            parts.push(
                `<span class="indicator-item">` +
                `<span class="indicator-label">АД</span>` +
                `<span class="indicator-value">${vitals.systolicPressure}/${vitals.diastolicPressure}</span>` +
                `</span>`
            );
        }
        if (vitals.saturation !== null && vitals.saturation !== undefined) {
            parts.push(
                `<span class="indicator-item">` +
                `<span class="indicator-label">SpO₂</span>` +
                `<span class="indicator-value">${vitals.saturation}%</span>` +
                `</span>`
            );
        }
    }

    if (parts.length === 0) {
        return '—';
    }

    return `<span class="indicator-wrapper">${parts.join(' ')}</span>`;
}