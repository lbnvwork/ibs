import Multiselect from '@vueform/multiselect';
import '@vueform/multiselect/themes/default.css';
import { mkb10Api } from '@/api/mkb10';

export default {
    name: 'DiagnosisSelect',
    components: { Multiselect },
    props: {
        modelValue: { type: Array, default: () => [] },
    },
    data() {
        return {
            selectedCodes: [], // массив строк (кодов)
            options: [],
            loading: false,
            popular: [],
        };
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(val) {
                // Синхронизация из родителя
                const safeVal = Array.isArray(val) ? val : [];
                if (JSON.stringify(safeVal) !== JSON.stringify(this.selectedCodes)) {
                    this.selectedCodes = [...safeVal];
                }
            },
        },
        selectedCodes: {
            handler(val) {
                // Отправляем в родитель
                const safeVal = Array.isArray(val) ? val : [];
                if (JSON.stringify(safeVal) !== JSON.stringify(this.modelValue)) {
                    this.$emit('update:modelValue', safeVal);
                }
            },
            deep: true,
        },
    },
    async created() {
        await this.loadPopular();
    },
    methods: {
        async loadPopular() {
            try {
                const data = await mkb10Api.getPopular();
                const popularData = Array.isArray(data) ? data : (data.member || []);
                this.popular = popularData; // уже camelCase
                this.options = [...this.popular];
                console.log('Popular loaded:', this.popular);
            } catch (err) {
                console.error('Ошибка загрузки популярных диагнозов', err);
            }
        },
        onSearch: debounce(async function (search) {
            if (!search || search.length < 2) {
                this.options = [...this.popular];
                return;
            }
            this.loading = true;
            try {
                const data = await mkb10Api.search(search);
                const results = Array.isArray(data) ? data : (data.member || []);
                this.options = results; // уже camelCase
                console.log('Search results:', results);
            } catch (err) {
                console.error('Ошибка поиска диагнозов', err);
            } finally {
                this.loading = false;
            }
        }, 300),
    },
};

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}