<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Терминалы Vendista</h1>
                <button
                    @click="syncTerminals"
                    :disabled="syncing"
                    class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50 transition-colors"
                >
                    {{ syncing ? 'Обновление...' : 'Обновить список терминалов' }}
                </button>
            </div>

            <!-- Список терминалов -->
            <div class="space-y-2">
                <div
                    v-for="terminal in terminals"
                    :key="terminal.id"
                    class="flex items-center justify-between rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                            {{ terminal.comment || 'Без описания' }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <span v-if="terminal.tid">TID: {{ terminal.tid }}</span>
                            <span v-if="terminal.serial_number" class="ml-2">S/N: {{ terminal.serial_number }}</span>
                            <span class="ml-2">ID: {{ terminal.vendista_id }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            :class="stateClass(terminal.state)"
                            class="rounded px-2 py-1 text-xs font-medium"
                        >
                            {{ stateLabel(terminal.state) }}
                        </span>
                    </div>
                </div>
            </div>

            <p v-if="!terminals.length && !loading" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Терминалов пока нет. Нажмите «Обновить список терминалов» для загрузки.
            </p>

            <!-- Модальное окно с отчётом -->
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
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';

const terminals = ref([]);
const loading = ref(true);
const syncing = ref(false);
const showReport = ref(false);
const syncError = ref('');
const report = ref({ added: 0, updated: 0, deleted: 0 });

const stateLabels = {
    0: 'Неизвестно',
    1: 'Онлайн',
    2: 'Офлайн',
    3: 'Ошибка',
    4: 'Заблокирован',
    5: 'Отключён',
    6: 'Инициализация',
    7: 'Обновление',
};

function stateLabel(state) {
    return stateLabels[state] || 'Неизвестно';
}

function stateClass(state) {
    const classes = {
        1: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        2: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        3: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        4: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        5: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[state] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
}

async function fetchTerminals() {
    try {
        const { data } = await apiClient.get('/admin/vendista/terminals');
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
