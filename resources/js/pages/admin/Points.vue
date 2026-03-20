<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Точки</h1>
                <button
                    @click="syncTerminals"
                    :disabled="syncing"
                    class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50 transition-colors"
                >
                    {{ syncing ? 'Обновление...' : 'Обновить список терминалов' }}
                </button>
            </div>

            <!-- Индикатор загрузки -->
            <div v-if="loading" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Загрузка...
            </div>

            <!-- Список точек -->
            <div v-else-if="terminals.length" class="space-y-2">
                <router-link
                    v-for="terminal in sortedTerminals"
                    :key="terminal.id"
                    :to="{ name: 'admin-point-settings', params: { id: terminal.id } }"
                    class="flex items-center justify-between rounded-lg bg-white p-4 shadow transition-colors hover:bg-gray-50 active:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-750 dark:active:bg-gray-700"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ terminal.comment || 'Без описания' }}
                        </p>
                        <p v-if="terminal.settings?.address" class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                            {{ terminal.settings.address }}
                        </p>
                    </div>
                    <div class="ml-3 flex shrink-0 flex-wrap items-center justify-end gap-1">
                        <span
                            v-if="!terminal.ingredients?.length"
                            class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400"
                        >
                            Не настроены ингредиенты
                        </span>
                        <span
                            v-if="!terminal.settings?.warehouse_id"
                            class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400"
                        >
                            Не выбран склад отгрузки
                        </span>
                        <span
                            v-if="!terminal.settings?.address && !(terminal.settings?.latitude && terminal.settings?.longitude)"
                            class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400"
                        >
                            Не обозначен адрес
                        </span>
                        <span
                            v-if="terminal.settings?.hidden"
                            class="rounded bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/40 dark:text-orange-400"
                        >
                            Скрыт
                        </span>
                        <span
                            v-if="terminal.settings && !terminal.settings.uses_water"
                            class="rounded bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/40 dark:text-purple-400"
                        >
                            Без воды
                        </span>
                        <!-- Стрелка -->
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                </router-link>
            </div>

            <p v-else class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Терминалов пока нет. Нажмите «Обновить список терминалов» для загрузки.
            </p>

            <!-- Модальное окно с отчётом синхронизации -->
            <div v-if="showReport" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showReport = false">
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Результат синхронизации</h2>

                    <div v-if="syncError" class="rounded bg-red-100 p-3 text-red-800 dark:bg-red-900 dark:text-red-200">
                        {{ syncError }}
                    </div>

                    <div v-else class="space-y-2">
                        <div class="flex justify-between rounded bg-green-100 p-3 dark:bg-green-900">
                            <span class="text-green-800 dark:text-green-200">Добавлено</span>
                            <span class="font-bold text-green-800 dark:text-green-200">{{ report.added }}</span>
                        </div>
                        <div class="flex justify-between rounded bg-yellow-100 p-3 dark:bg-yellow-900">
                            <span class="text-yellow-800 dark:text-yellow-200">Обновлено</span>
                            <span class="font-bold text-yellow-800 dark:text-yellow-200">{{ report.updated }}</span>
                        </div>
                        <div class="flex justify-between rounded bg-red-100 p-3 dark:bg-red-900">
                            <span class="text-red-800 dark:text-red-200">Удалено</span>
                            <span class="font-bold text-red-800 dark:text-red-200">{{ report.deleted }}</span>
                        </div>
                    </div>

                    <button
                        @click="showReport = false"
                        class="mt-4 w-full rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors"
                    >
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/client';

const terminals = ref([]);

/** Отсортированный список: скрытые точки в конце */
const sortedTerminals = computed(() => {
    return [...terminals.value].sort((a, b) => {
        const aHidden = a.settings?.hidden ? 1 : 0;
        const bHidden = b.settings?.hidden ? 1 : 0;
        if (aHidden !== bHidden) return aHidden - bHidden;
        return (a.comment || '').localeCompare(b.comment || '');
    });
});
const loading = ref(true);
const syncing = ref(false);
const showReport = ref(false);
const syncError = ref('');
const report = ref({ added: 0, updated: 0, deleted: 0 });



async function fetchTerminals() {
    try {
        const { data } = await apiClient.get('/admin/points');
        terminals.value = data.terminals;
    } finally {
        loading.value = false;
    }
}

async function syncTerminals() {
    syncing.value = true;
    syncError.value = '';

    try {
        const { data } = await apiClient.post('/admin/vendista/terminals/sync');
        if (data.success) {
            report.value = data.report;
            await fetchTerminals();
        } else {
            syncError.value = data.error || 'Неизвестная ошибка';
        }
    } catch (error) {
        syncError.value = error.response?.data?.error || 'Не удалось выполнить синхронизацию';
    } finally {
        syncing.value = false;
        showReport.value = true;
    }
}

onMounted(fetchTerminals);
</script>
