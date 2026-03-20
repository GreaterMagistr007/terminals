<template>
    <div class="px-4 py-4">
        <!-- Заголовок с счётчиками -->
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Точки обслуживания</h1>
            <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                <span class="flex h-2 w-2 rounded-full bg-green-400"></span>
                {{ onlineCount }} ок
                <span class="ml-2 flex h-2 w-2 rounded-full bg-red-400"></span>
                {{ urgentCount }} сроч.
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
                    <div class="h-10 w-1 shrink-0 rounded-full" :class="statusBarClass(terminal)"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ terminal.comment || 'Без описания' }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">продаж с последнего обслуживания: 0</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatVisitDate(terminal.last_online_at) }}</span>
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
                    <!-- Вода и последний визит -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Вода</p>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-1.5 rounded-full bg-gray-300" style="width: 0%"></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-400">—</span>
                            </div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Последний визит</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatVisitDate(terminal.last_online_at) }}</p>
                        </div>
                    </div>

                    <!-- Ингредиенты -->
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Ингредиенты</p>
                        <p class="text-xs text-gray-300 dark:text-gray-600">Данные появятся после первого обслуживания</p>
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

const IRKUTSK_TZ = 'Asia/Irkutsk';

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
const urgentCount = computed(() => terminals.value.filter(t => t.state !== 1).length);

function statusBarClass(terminal) {
    // TODO: заменить на логику по данным обслуживания
    return {
        1: 'bg-green-500',
        2: 'bg-red-500',
        3: 'bg-gray-400',
    }[terminal.state] || 'bg-gray-300';
}

/**
 * Форматирование даты по иркутскому времени.
 * < 1 часа: "N минут"
 * Сегодня (по Иркутску): "N часов"
 * Вчера (по Иркутску): "Вчера"
 * 2-7 дней: "N дней"
 * > 7 дней: "28 января"
 */
function formatVisitDate(dateStr) {
    if (!dateStr) return '—';

    const date = new Date(dateStr);
    const now = new Date();

    // Получаем дату в иркутском часовом поясе
    const irkNow = new Date(now.toLocaleString('en-US', { timeZone: IRKUTSK_TZ }));
    const irkDate = new Date(date.toLocaleString('en-US', { timeZone: IRKUTSK_TZ }));

    const diffMs = now - date;
    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);

    // Менее часа — минуты
    if (diffMinutes < 60) {
        if (diffMinutes < 1) return 'Только что';
        return `${diffMinutes} ${pluralize(diffMinutes, 'минута', 'минуты', 'минут')}`;
    }

    // Начало сегодняшнего дня по Иркутску
    const todayStart = new Date(irkNow);
    todayStart.setHours(0, 0, 0, 0);

    const yesterdayStart = new Date(todayStart);
    yesterdayStart.setDate(yesterdayStart.getDate() - 1);

    // Сегодня по Иркутску — часы
    if (irkDate >= todayStart) {
        return `${diffHours} ${pluralize(diffHours, 'час', 'часа', 'часов')}`;
    }

    // Вчера по Иркутску
    if (irkDate >= yesterdayStart) {
        return 'Вчера';
    }

    // Дни (до 7)
    const diffDays = Math.floor((todayStart - irkDate) / 86400000) + 1;
    if (diffDays <= 7) {
        return `${diffDays} ${pluralize(diffDays, 'день', 'дня', 'дней')}`;
    }

    // Дата
    return date.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        timeZone: IRKUTSK_TZ,
    });
}

/** Склонение русских числительных */
function pluralize(n, one, few, many) {
    const abs = Math.abs(n) % 100;
    const lastDigit = abs % 10;
    if (abs > 10 && abs < 20) return many;
    if (lastDigit === 1) return one;
    if (lastDigit >= 2 && lastDigit <= 4) return few;
    return many;
}

onMounted(fetchTerminals);
</script>
