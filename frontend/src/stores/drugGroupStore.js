import { defineStore } from 'pinia';
import apiClient from '@/api/client';

export const useDrugGroupStore = defineStore('drugGroup', {
    state: () => ({
        drugGroups: [],
        loading: false,
        error: null,
    }),

    getters: {
        drugGroupOptions: (state) => {
            return [
                { value: '', label: 'Все категории' },
                ...state.drugGroups.map(g => ({ value: g.id, label: g.fullName || g.name }))
            ];
        }
    },

    actions: {
        async loadDrugGroups() {
            this.loading = true;
            this.error = null;
            try {
                const response = await apiClient.get('/drug_groups');
                const groups = response.data?.member ?? response.data;
                this.drugGroups = Array.isArray(groups) ? groups : [];
            } catch (err) {
                this.error = err.message;
                console.error('Failed to load drug groups', err);
            } finally {
                this.loading = false;
            }
        }
    }
})