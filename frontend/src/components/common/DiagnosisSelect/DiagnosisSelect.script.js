import vSelect from 'vue-select';
import 'vue-select/dist/vue-select.css';
import { mkb10Api } from '@/api/mkb10';

export default {
    name: 'DiagnosisSelect',
    components: { vSelect },
    props: {
        modelValue: { type: Array, default: () => [] },
    },
    data() {
        return {
            selected: [],
            options: [],
            loading: false,
            popular: [],
        };
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(val) {
                if (Array.isArray(val) && val.length) {
                    const allOptions = [...this.options, ...this.popular];
                    this.selected = val
                        .map(code => allOptions.find(d => d.mkbCode === code))
                        .filter(Boolean);
                } else {
                    this.selected = [];
                }
            },
        },
        selected(val) {
            this.$emit('update:modelValue', val.map(d => d.mkbCode));
        },
    },
    async created() {
        await this.loadPopular();
    },
    methods: {
        async loadPopular() {
            try {
                const data = await mkb10Api.getPopular();
                this.popular = Array.isArray(data) ? data : (data.member || []);
                this.options = [...this.popular];
            } catch (err) {
                console.error('Ошибка загрузки популярных диагнозов', err);
            }
        },
        onSearch: debounce(async function (search, loading) {
            if (!search || search.length < 2) {
                this.options = [...this.popular];
                return;
            }
            loading(true);
            try {
                const data = await mkb10Api.search(search);
                const results = Array.isArray(data) ? data : (data.member || []);
                 const normalized = results.map(item => ({
                    id: item.id,
                    mkbCode: item.mkb_code,
                    mkbName: item.mkb_name,
                }));
                this.options = normalized;
            } catch (err) {
                console.error('Ошибка поиска диагнозов', err);
            } finally {
                loading(false);
            }
        }, 300),
        getOptionLabel(option) {
            return `${option.mkbCode} — ${option.mkbName}`;
        },
        getOptionKey(option) {
            return option.id;
        },
    },
};

function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}