import { defineStore } from 'pinia';
import apiClient from '@/api/client';

const USER_CACHE_KEY = 'auth-user-cache';

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
                this._saveToStorage();
            } catch {
                // При ошибке сети — восстановить из кеша
                if (!this.user) {
                    this._loadFromStorage();
                }
            } finally {
                this.loaded = true;
            }
        },

        async logout() {
            try {
                await apiClient.post('/auth/logout');
            } finally {
                this.user = null;
                try { localStorage.removeItem(USER_CACHE_KEY); } catch {}
            }
        },

        async loginViaBotToken(token) {
            const { data } = await apiClient.post(`/auth/telegram-bot/${token}`);
            this.user = data.user;
            this._saveToStorage();
            return data.user;
        },

        _saveToStorage() {
            try {
                if (this.user) {
                    localStorage.setItem(USER_CACHE_KEY, JSON.stringify(this.user));
                }
            } catch {}
        },

        _loadFromStorage() {
            try {
                const cached = localStorage.getItem(USER_CACHE_KEY);
                if (cached) {
                    this.user = JSON.parse(cached);
                }
            } catch {}
        },
    },
});
