export default {
    name: 'RiskScale',
    props: {
        riskScores: {
            type: Object,
            required: true,
            validator(value) {
                return 'cha2ds2Vasc' in value && 'hasBled' in value && 'score' in value;
            }
        }
    },
    methods: {
        calculateProgress(value, max) {
            return `${(value / max) * 100}%`;
        },

        // CHA2DS2VASc логика
        getCha2ds2VascColor(score) {
            if (score === 0) return '#2ecc71';
            if (score === 1) return '#f39c12';
            return '#e74c3c';
        },

        getCha2ds2VascInterpretation(score) {
            if (score === 0) return 'Низкий риск инсульта';
            if (score === 1) return 'Умеренный риск инсульта';
            if (score >= 2) return 'Высокий риск инсульта';
            return 'Не определено';
        },

        // HAS-BLED логика
        getHasBledColor(score) {
            if (score <= 1) return '#2ecc71';
            if (score === 2) return '#f39c12';
            return '#e74c3c';
        },

        getHasBledInterpretation(score) {
            if (score <= 1) return 'Низкий риск кровотечения';
            if (score === 2) return 'Умеренный риск кровотечения';
            if (score >= 3) return 'Высокий риск кровотечения';
            return 'Не определено';
        },

        // SCORE логика (риск сердечно-сосудистых осложнений)
        getScoreColor(score) {
            if (score <= 2) return '#2ecc71';      // Низкий риск
            if (score <= 4) return '#f39c12';      // Умеренный риск
            return '#e74c3c';                      // Высокий риск
        },

        getScoreInterpretation(score) {
            if (score <= 2) return 'Низкий риск СС осложнений';
            if (score <= 4) return 'Умеренный риск СС осложнений';
            if (score >= 5) return 'Высокий риск СС осложнений';
            return 'Не определено';
        }
    }
}