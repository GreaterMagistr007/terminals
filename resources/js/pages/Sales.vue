<template>
    <div class="px-4 py-4">
        <!-- Заголовок с кнопкой обновления -->
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Продажи</h1>
            <button
                @click="refreshSales"
                :disabled="refreshing"
                class="rounded-lg p-1.5 text-gray-400 active:bg-gray-100 disabled:opacity-40 dark:text-gray-500 dark:active:bg-gray-800"
            >
                <svg class="h-5 w-5" :class="{ 'animate-spin': refreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
                </svg>
            </button>
        </div>

        <!-- Итого за сегодня -->
        <div v-if="totals" class="mb-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900">
            <p class="text-xs text-gray-400 dark:text-gray-500">Сегодня, {{ formattedDate }}</p>
            <div class="mt-2 flex items-baseline gap-3">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(totals.total_sum) }}</span>
                <span class="text-sm text-gray-400 dark:text-gray-500">{{ totals.total_count }} {{ pluralize(totals.total_count, 'стакан', 'стакана', 'стаканов') }}</span>
            </div>
        </div>

        <!-- Загрузка -->
        <p v-if="loading" class="text-center text-sm text-gray-400 dark:text-gray-500">Загрузка...</p>

        <!-- Пустой список -->
        <p v-else-if="!sales.length" class="text-center text-sm text-gray-400 dark:text-gray-500">
            Продаж за сегодня нет.
        </p>

        <!-- Список точек -->
        <div v-else class="space-y-2">
            <div v-for="(item, idx) in sales" :key="item.term_id"
                class="flex items-center gap-3 rounded-xl bg-white px-4 py-3 shadow-sm dark:bg-gray-900"
            >
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ idx + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ item.terminal_name }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ item.total_count }} {{ pluralize(item.total_count, 'стакан', 'стакана', 'стаканов') }}</p>
                </div>
                <span class="shrink-0 text-sm font-bold text-gray-900 dark:text-white">{{ formatMoney(item.total_sum) }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import apiClient from '@/api/client';

const CACHE_KEY = 'sales-cache';

const sales = ref([]);
const totals = ref(null);
const formattedDate = ref('');
const loading = ref(true);
const refreshing = ref(false);

function formatMoney(kopecks) {
    const rubles = Math.floor(kopecks / 100);
    return rubles.toLocaleString('ru-RU') + ' \u20BD';
}

function pluralize(n, one, few, many) {
    const abs = Math.abs(n) % 100;
    const lastDigit = abs % 10;
    if (abs > 10 && abs < 20) return many;
    if (lastDigit === 1) return one;
    if (lastDigit >= 2 && lastDigit <= 4) return few;
    return many;
}

function applyData(data) {
    sales.value = data.sales;
    totals.value = data.totals;
    if (data.date) {
        const d = new Date(data.date + 'T00:00:00');
        formattedDate.value = d.toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
    }
    try {
        localStorage.setItem(CACHE_KEY, JSON.stringify({ sales: data.sales, totals: data.totals, date: data.date }));
    } catch { /* переполнение — игнорируем */ }
}

function loadFromCache() {
    try {
        const cached = localStorage.getItem(CACHE_KEY);
        if (cached) {
            const data = JSON.parse(cached);
            applyData(data);
        }
    } catch { /* повреждённые данные */ }
}

async function fetchSales() {
    try {
        const { data } = await apiClient.get('/sales/today');
        applyData(data);
    } catch {
        loadFromCache();
    } finally {
        loading.value = false;
    }
}

async function refreshSales() {
    refreshing.value = true;
    try {
        const { data } = await apiClient.post('/sales/refresh');
        applyData(data);
    } catch {
        // Нет сети — данные остаются из кеша
    } finally {
        refreshing.value = false;
    }
}

onMounted(() => {
    fetchSales();
    document.addEventListener('vendista:updated', fetchSales);
});

onBeforeUnmount(() => {
    document.removeEventListener('vendista:updated', fetchSales);
});
</script>
