<template>
    <div class="px-4 py-4">
        <!-- Заголовок с счётчиками -->
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Точки обслуживания</h1>
            <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                <span class="flex h-2 w-2 rounded-full bg-green-400"></span>
                {{ onlineCount }}
                <span class="ml-2 flex h-2 w-2 rounded-full bg-gray-300"></span>
                {{ offlineCount }}
            </div>
        </div>

        <!-- Загрузка -->
        <p v-if="loading" class="text-center text-sm text-gray-400 dark:text-gray-500">Загрузка...</p>

        <!-- Пустой список -->
        <p v-else-if="!terminals.length" class="text-center text-sm text-gray-400 dark:text-gray-500">
            Терминалов нет. Администратор может загрузить список через раздел «Терминалы».
        </p>

        <!-- Список терминалов -->
        <div v-else class="space-y-2">
            <div v-for="terminal in terminals" :key="terminal.id"
                class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-900"
            >
                <!-- Свёрнутая строка -->
                <button @click="toggle(terminal.id)"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left active:bg-gray-50 dark:active:bg-gray-800"
                >
                    <div class="h-10 w-1 shrink-0 rounded-full" :class="statusBarClass(terminal.state)"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ terminal.comment || 'Без описания' }}</p>
                        <p v-if="terminal.tid" class="text-xs text-gray-400 dark:text-gray-500">TID: {{ terminal.tid }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <span
                            class="rounded px-1.5 py-0.5 text-xs font-medium"
                            :class="stateBadgeClass(terminal.state)"
                        >{{ stateLabel(terminal.state) }}</span>
                        <svg class="h-4 w-4 text-gray-300 transition-transform dark:text-gray-600"
                            :class="expandedId === terminal.id ? 'rotate-180' : ''"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </button>

                <!-- Раскрытый контент -->
                <div v-if="expandedId === terminal.id"
                    class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800"
                >
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Последний онлайн</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(terminal.last_online_at) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Состояние</p>
                            <p class="mt-1 text-sm font-semibold" :class="stateTextClass(terminal.state)">{{ stateLabel(terminal.state) }}</p>
                        </div>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="flex gap-2">
                        <button class="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-blue-500 py-2.5 text-sm font-medium text-white active:bg-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
                            </svg>
                            Обслужить
                        </button>
                        <button class="flex items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:active:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            История
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import apiClient from '@/api/client';

const terminals = ref([]);
const loading = ref(true);
const expandedId = ref(null);

const toggle = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

async function fetchTerminals() {
    try {
        const { data } = await apiClient.get('/terminals');
        terminals.value = data.terminals;
    } finally {
        loading.value = false;
    }
}

const onlineCount = computed(() => terminals.value.filter(t => t.state === 1).length);
const offlineCount = computed(() => terminals.value.filter(t => t.state !== 1).length);

function statusBarClass(state) {
    return {
        1: 'bg-green-500',
        2: 'bg-red-500',
        3: 'bg-gray-400',
    }[state] || 'bg-gray-300';
}

function stateBadgeClass(state) {
    return {
        1: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        2: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        3: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    }[state] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
}

function stateTextClass(state) {
    return {
        1: 'text-green-600 dark:text-green-400',
        2: 'text-red-600 dark:text-red-400',
        3: 'text-gray-500 dark:text-gray-400',
    }[state] || 'text-gray-500 dark:text-gray-400';
}

function stateLabel(state) {
    return {
        0: 'Неизвестно',
        1: 'Онлайн',
        2: 'Офлайн',
        3: 'Нет связи',
        4: 'Заблокирован',
        5: 'Отключён',
    }[state] || 'Неизвестно';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffHours < 1) return 'Только что';
    if (diffHours < 24) return `${diffHours} ч. назад`;
    if (diffDays === 1) return 'Вчера';
    if (diffDays < 7) return `${diffDays} дн. назад`;
    return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' });
}

onMounted(fetchTerminals);
</script>
