/**
 * Pinia store для данных терминалов.
 * Стратегия: сначала кеш (localStorage), потом обновление с сервера в фоне.
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
        /** Загрузить терминалы: мгновенно из кеша, затем обновить с сервера */
        async fetch() {
            // Сначала показать кеш (мгновенно)
            if (!this.terminals.length) {
                this._loadFromStorage();
            }

            // Потом попробовать обновить с сервера
            try {
                const { data } = await apiClient.get('/terminals');
                if (data.terminals) {
                    this.terminals = data.terminals;
                    this.loaded = true;
                    this._saveToStorage();
                }
            } catch {
                // Нет сети — данные уже из кеша
                this.loaded = true;
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

        /** Данные конкретного терминала: из кеша или с сервера */
        async fetchOne(id) {
            // Сначала из кеша в памяти
            if (!this.terminals.length) {
                this._loadFromStorage();
            }

            const cached = this.getById(id);

            // Попробовать обновить с сервера (не блокируя если есть кеш)
            if (navigator.onLine) {
                try {
                    const { data } = await apiClient.get(`/terminals/${id}`);
                    if (data.terminal) {
                        const idx = this.terminals.findIndex(t => t.id === data.terminal.id);
                        if (idx !== -1) {
                            this.terminals[idx] = { ...this.terminals[idx], ...data.terminal };
                        }
                        this._saveToStorage();
                        return data.terminal;
                    }
                } catch {
                    // Ошибка сети — вернём кеш
                }
            }

            return cached;
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
