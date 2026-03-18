import { defineStore } from 'pinia';
import apiClient from '@/api/client';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loaded: false,
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null,
        isAdmin: (state) => state.user?.role === 'admin',
        isOperator: (state) => state.user?.role === 'operator',
    },

    actions: {
        async fetchUser() {
            try {
                const { data } = await apiClient.get('/auth/me');
                this.user = data.user;
            } catch {
                this.user = null;
            } finally {
                this.loaded = true;
            }
        },

        async logout() {
            try {
                await apiClient.post('/auth/logout');
            } finally {
                this.user = null;
            }
        },

        async loginViaBotToken(token) {
            const { data } = await apiClient.post(`/auth/telegram-bot/${token}`);
            this.user = data.user;
            return data.user;
        },
    },
});
