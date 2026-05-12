import {defineStore} from 'pinia';
import apiClient from '@/api/client';
import router from '@/router';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: null,
        loading: false,
        error: null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        getUser: (state) => state.user,
        userDisplayName: (state) => {
            if (!state.user) return '';
            return state.user.userName || state.user.login || 'Пользователь';
        }
    },

    actions: {
        decodeToken(token) {
            try {
                const base64Url = token.split('.')[1];
                const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                return JSON.parse(atob(base64));
            } catch (e) {
                console.error('Failed to decode token', e);
                return null;
            }
        },

        async fetchUserProfile() {
            if (!this.user || !this.user.id) return;
            try {
                const response = await apiClient.get(`/users/${this.user.id}`);
                this.user = { ...this.user, ...response.data };
            } catch (err) {
                console.error('Failed to fetch user profile', err);
            }
        },

        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                const response = await apiClient.post('/login', credentials);
                const token = response.data.token;
                this.token = token;
                localStorage.setItem('token', token);

                this.user = this.decodeToken(token);
                await this.fetchUserProfile();

                router.push('/');
            } catch (err) {
                this.error = err.response?.data?.message || 'Ошибка входа';
                console.error('Login error:', err);
            } finally {
                this.loading = false;
            }
        },

        logout() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            router.push('/login');
        },

        async initAuth() {
            const token = localStorage.getItem('token');
            if (token) {
                this.token = token;
                const payload = this.decodeToken(token);
                if (payload) {
                    this.user = payload;
                    await this.fetchUserProfile();
                } else {
                    this.logout();
                }
            }
        }
    }
});