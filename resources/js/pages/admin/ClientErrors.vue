<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Клиентские ошибки</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-model="filterSource"
                        @change="fetchErrors"
                        class="rounded border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Все источники</option>
                        <option v-for="s in sources" :key="s" :value="s">{{ s }}</option>
                    </select>
                    <button
                        @click="fetchErrors"
                        class="rounded bg-blue-500 px-4 py-2 text-sm text-white hover:bg-blue-600 transition-colors"
                    >
                        Обновить
                    </button>
                    <button
                        @click="confirmClearAll"
                        :disabled="clearing || !errors.length"
                        class="rounded bg-red-500 px-4 py-2 text-sm text-white hover:bg-red-600 disabled:opacity-50 transition-colors"
                    >
                        {{ clearing ? 'Очистка…' : 'Очистить все' }}
                    </button>
                </div>
            </div>

            <p v-if="loading" class="text-center text-sm text-gray-500 dark:text-gray-400">Загрузка…</p>

            <p v-else-if="!errors.length" class="text-center text-sm text-gray-500 dark:text-gray-400">
                Записей нет.
            </p>

            <div v-else class="space-y-2">
                <div
                    v-for="err in errors"
                    :key="err.id"
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex flex-wrap items-center gap-2">
                                <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                    {{ err.source }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDate(err.created_at) }}
                                </span>
                                <span v-if="err.user" class="text-xs text-gray-600 dark:text-gray-300">
                                    {{ err.user.name }}
                                </span>
                            </div>
                            <p class="break-words text-sm font-medium text-gray-900 dark:text-white">
                                {{ err.message }}
                            </p>
                            <p v-if="err.url" class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ err.url }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <button
                                @click="toggle(err.id)"
                                class="rounded bg-gray-100 px-3 py-1 text-xs text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                {{ expandedId === err.id ? 'Скрыть' : 'Подробнее' }}
                            </button>
                            <button
                                @click="deleteOne(err)"
                                class="rounded bg-red-100 px-3 py-1 text-xs text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60 transition-colors"
                            >
                                Удалить
                            </button>
                        </div>
                    </div>

                    <div v-if="expandedId === err.id" class="mt-3 space-y-2 border-t border-gray-100 pt-3 dark:border-gray-700">
                        <div v-if="err.user_agent">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">User-Agent</p>
                            <p class="break-words text-xs text-gray-700 dark:text-gray-300">{{ err.user_agent }}</p>
                        </div>
                        <div v-if="err.ip">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">IP</p>
                            <p class="text-xs text-gray-700 dark:text-gray-300">{{ err.ip }}</p>
                        </div>
                        <div v-if="err.context">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">Контекст</p>
                            <pre class="overflow-x-auto rounded bg-gray-50 p-2 text-xs text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ formatContext(err.context) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/client';

const errors = ref([]);
const loading = ref(true);
const clearing = ref(false);
const expandedId = ref(null);
const filterSource = ref('');

const sources = computed(() => {
    const set = new Set(errors.value.map(e => e.source).filter(Boolean));
    return Array.from(set).sort();
});

async function fetchErrors() {
    loading.value = true;
    try {
        const params = {};
        if (filterSource.value) params.source = filterSource.value;
        const { data } = await apiClient.get('/admin/client-errors', { params });
        errors.value = data.errors || [];
    } finally {
        loading.value = false;
    }
}

function toggle(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

async function deleteOne(err) {
    if (!confirm('Удалить запись?')) return;
    await apiClient.delete(`/admin/client-errors/${err.id}`);
    errors.value = errors.value.filter(e => e.id !== err.id);
}

async function confirmClearAll() {
    if (!confirm('Удалить ВСЕ записи клиентских ошибок?')) return;
    clearing.value = true;
    try {
        await apiClient.delete('/admin/client-errors/clear');
        errors.value = [];
    } finally {
        clearing.value = false;
    }
}

function formatDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleString('ru-RU', {
        timeZone: 'Asia/Irkutsk',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function formatContext(ctx) {
    try {
        return JSON.stringify(ctx, null, 2);
    } catch {
        return String(ctx);
    }
}

onMounted(fetchErrors);
</script>
