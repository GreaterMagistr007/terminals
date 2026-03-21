/**
 * Pinia store для данных терминалов.
 * Хранит список терминалов в памяти + localStorage для офлайн-доступа.
 */
import { defineStore } from 'pinia';
import apiClient from '@/api/client';

const STORAGE_KEY = 'terminals-cache';

export const useTerminalsStore = defineStore('terminals', {
    state: () => ({
        terminals: [],
        loaded: false,
    }),

    getters: {
        /** Получить терминал по ID */
        getById: (state) => (id) => {
            return state.terminals.find(t => t.id === Number(id)) || null;
        },
    },

    actions: {
        /** Загрузить терминалы (API с fallback на localStorage) */
        async fetch() {
            try {
                const { data } = await apiClient.get('/terminals');
                if (data.terminals) {
                    this.terminals = data.terminals;
                    this.loaded = true;
                    this._saveToStorage();
                }
            } catch {
                // При ошибке сети — восстановить из localStorage
                if (!this.terminals.length) {
                    this._loadFromStorage();
                }
            }
        },

        /** Принудительное обновление (POST /terminals/refresh) */
        async refresh() {
            const { data } = await apiClient.post('/terminals/refresh');
            if (data.terminals) {
                this.terminals = data.terminals;
                this._saveToStorage();
            }
            return data;
        },

        /** Обновить данные конкретного терминала (для Service.vue) */
        async fetchOne(id) {
            try {
                const { data } = await apiClient.get(`/terminals/${id}`);
                if (data.terminal) {
                    // Обновить в общем списке
                    const idx = this.terminals.findIndex(t => t.id === data.terminal.id);
                    if (idx !== -1) {
                        this.terminals[idx] = { ...this.terminals[idx], ...data.terminal };
                    }
                    this._saveToStorage();
                    return data.terminal;
                }
            } catch {
                // Вернуть из кеша
                return this.getById(id);
            }
            return null;
        },

        _saveToStorage() {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(this.terminals));
            } catch {
                // localStorage переполнен — игнорируем
            }
        },

        _loadFromStorage() {
            try {
                const cached = localStorage.getItem(STORAGE_KEY);
                if (cached) {
                    this.terminals = JSON.parse(cached);
                    this.loaded = true;
                }
            } catch {
                // Повреждённые данные — игнорируем
            }
        },
    },
});
