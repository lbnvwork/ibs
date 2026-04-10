import { mkb10Api } from '@/api/mkb10';

const MIN_SEARCH_LENGTH = 3;

export default {
    name: 'MultiDiagnosisSelect',
    props: {
        modelValue: { type: Array, default: () => [] },
    },
    data() {
        return {
            selectedCodes: [],
            search: '',
            options: [],
            popular: [],
            loading: false,
            searchTimeout: null,
            isOpen: false,
        };
    },
    mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeDestroy() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    watch: {
        modelValue: {
            immediate: true,
            handler(val) {
                const safeVal = Array.isArray(val) ? val : [];
                if (JSON.stringify(safeVal) !== JSON.stringify(this.selectedCodes)) {
                    this.selectedCodes = [...safeVal];
                }
            },
        },
        selectedCodes: {
            handler(val) {
                const safeVal = Array.isArray(val) ? val : [];
                if (JSON.stringify(safeVal) !== JSON.stringify(this.modelValue)) {
                    this.$emit('update:modelValue', safeVal);
                }
            },
            deep: true,
        },
    },
    computed: {
        validOptions() {
            if (!this.search || this.search.length < MIN_SEARCH_LENGTH) return this.options;
            const lowerSearch = this.search.toLowerCase();
            return this.options.filter(opt => 
                opt.mkbCode.toLowerCase().includes(lowerSearch) ||
                opt.mkbName.toLowerCase().includes(lowerSearch)
            );
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
                this.popular = popularData;
                if (!this.search || this.search.length < MIN_SEARCH_LENGTH) {
                    this.options = [...this.popular];
                }
            } catch (err) {
                console.error('Ошибка загрузки популярных диагнозов', err);
            }
        },
        onSearchInput() {
            if (this.searchTimeout) clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => this.doSearch(), 300);
        },
        async doSearch() {
            if (!this.search || this.search.length < MIN_SEARCH_LENGTH) {
                this.options = [...this.popular];
                return;
            }
            this.loading = true;
            try {
                const data = await mkb10Api.search(this.search);
                const results = Array.isArray(data) ? data : (data.member || []);
                const normalized = results
                    .filter(item => item != null)
                    .map(item => ({
                        id: item.id,
                        mkbCode: item.mkbCode || item.mkb_code,
                        mkbName: item.mkbName || item.mkb_name,
                    }))
                    .filter(item => item.mkbCode && item.mkbName);
                this.options = normalized;
                if (normalized.length) {
                    this.openOptions();
                }
            } catch (err) {
                console.error('Ошибка поиска диагнозов', err);
            } finally {
                this.loading = false;
            }
        },
        toggleOptions() {
            this.isOpen = !this.isOpen;
        },
        openOptions() {
            this.isOpen = true;
        },
        closeOptions() {
            this.isOpen = false;
            this.search = '';
            this.options = [...this.popular];
        },
        removeDiagnosis(code) {
            this.selectedCodes = this.selectedCodes.filter(c => c !== code);
        },
        getShortLabel(code) {
            const found = [...this.options, ...this.popular].find(opt => opt && opt.mkbCode === code);
            return found ? found.mkbCode : code;
        },
        handleClickOutside(event) {
            if (this.$refs.selectContainer && !this.$refs.selectContainer.contains(event.target)) {
                this.closeOptions();
            }
        },
    },
};