<template>
    <!-- Вариант Q: Sales Analytics Dashboard — аналитика продаж для админа -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 py-3 shadow-sm dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Аналитика продаж</h1>
                <button class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Табы периода -->
        <div class="flex gap-2 px-4 py-3 overflow-x-auto">
            <button
                v-for="period in periods"
                :key="period.id"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition-all"
                :class="activePeriod === period.id
                    ? 'bg-blue-500 text-white shadow-md shadow-blue-500/25'
                    : 'bg-white text-gray-500 dark:bg-gray-900 dark:text-gray-400'"
                @click="activePeriod = period.id"
            >
                {{ period.label }}
            </button>
        </div>

        <main class="flex-1 overflow-y-auto px-4 pb-6">
            <!-- Большие числа -->
            <div class="mb-4 grid grid-cols-3 gap-3">
                <div class="rounded-2xl bg-white p-3 text-center shadow-sm dark:bg-gray-900">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Продажи</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">1 247</p>
                    <p class="text-xs text-green-500 font-medium">+12%</p>
                </div>
                <div class="rounded-2xl bg-white p-3 text-center shadow-sm dark:bg-gray-900">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Выручка</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">112 340</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">\u20BD</p>
                </div>
                <div class="rounded-2xl bg-white p-3 text-center shadow-sm dark:bg-gray-900">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Ср. чек</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">90</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">\u20BD</p>
                </div>
            </div>

            <!-- График выручки по дням -->
            <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Выручка по дням</p>
                <div class="flex items-end justify-between gap-2" style="height: 140px;">
                    <div
                        v-for="day in chartDays"
                        :key="day.label"
                        class="flex flex-1 flex-col items-center gap-1"
                    >
                        <!-- Значение над баром -->
                        <span class="text-xs font-medium" :class="day.isMax ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500'">
                            {{ day.shortValue }}
                        </span>
                        <!-- Бар -->
                        <div class="w-full flex-1 flex items-end">
                            <div
                                class="w-full rounded-t-lg transition-all duration-500"
                                :class="day.isMax
                                    ? 'bg-blue-500 shadow-md shadow-blue-500/20'
                                    : 'bg-blue-200 dark:bg-blue-900/40'"
                                :style="{ height: day.heightPercent + '%' }"
                            ></div>
                        </div>
                        <!-- Подпись -->
                        <span class="text-xs font-medium" :class="day.isMax ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500'">
                            {{ day.label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ТОП продуктов -->
            <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">ТОП продуктов</p>
                <div class="space-y-3">
                    <div v-for="(product, index) in topProducts" :key="product.name" class="flex items-center gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="index === 0
                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                : index === 1
                                    ? 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
                                    : index === 2
                                        ? 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400'
                                        : 'bg-gray-50 text-gray-400 dark:bg-gray-800 dark:text-gray-500'"
                        >{{ index + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ product.name }}</span>
                                <span class="shrink-0 ml-2 text-xs text-gray-400 dark:text-gray-500">{{ product.count }} шт ({{ product.percent }}%)</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div
                                    class="h-full rounded-full bg-blue-500 transition-all duration-700"
                                    :style="{ width: product.percent + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- По точкам -->
            <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">По точкам</p>
                <div class="space-y-2">
                    <div
                        v-for="point in pointsSales"
                        :key="point.name"
                        class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2.5 dark:bg-gray-800"
                    >
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg" :class="point.bgClass">
                                <span class="text-xs font-bold" :class="point.textClass">{{ point.initials }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 ml-2">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ point.revenue }}</span>
                            <span class="flex items-center gap-0.5 text-xs font-semibold" :class="point.trendUp ? 'text-green-500' : 'text-red-500'">
                                <svg v-if="point.trendUp" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                                </svg>
                                <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 4.5l15 15m0 0V8.25m0 11.25H8.25" />
                                </svg>
                                {{ point.trendValue }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- По типу оплаты -->
            <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">По типу оплаты</p>

                <!-- Стэкед-бар -->
                <div class="mb-3 flex h-8 overflow-hidden rounded-full">
                    <div
                        class="flex items-center justify-center bg-emerald-500 text-xs font-bold text-white transition-all"
                        :style="{ width: '35%' }"
                    >35%</div>
                    <div
                        class="flex items-center justify-center bg-blue-500 text-xs font-bold text-white transition-all"
                        :style="{ width: '58%' }"
                    >58%</div>
                    <div
                        class="flex items-center justify-center bg-purple-500 text-xs font-bold text-white transition-all"
                        :style="{ width: '7%' }"
                    ></div>
                </div>

                <!-- Легенда -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm bg-emerald-500"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Наличные</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">35%</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm bg-blue-500"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Безнал</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">58%</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="h-3 w-3 rounded-sm bg-purple-500"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">QR</span>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">7%</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const periods = [
    { id: 'today', label: 'Сегодня' },
    { id: 'week', label: 'Неделя' },
    { id: 'month', label: 'Месяц' },
    { id: 'custom', label: 'Период' },
]

const activePeriod = ref('week')

const chartData = [
    { label: 'Пн', value: 14200 },
    { label: 'Вт', value: 15800 },
    { label: 'Ср', value: 18700 },
    { label: 'Чт', value: 16300 },
    { label: 'Пт', value: 21400 },
    { label: 'Сб', value: 15100 },
    { label: 'Вс', value: 10840 },
]

const maxValue = Math.max(...chartData.map(d => d.value))

const chartDays = chartData.map(d => ({
    label: d.label,
    value: d.value,
    shortValue: (d.value / 1000).toFixed(1) + 'к',
    heightPercent: Math.round((d.value / maxValue) * 100),
    isMax: d.value === maxValue,
}))

const topProducts = [
    { name: 'Американо', count: 312, percent: 25 },
    { name: 'Капучино', count: 287, percent: 23 },
    { name: 'Латте', count: 198, percent: 16 },
    { name: 'Чай чёрный', count: 156, percent: 12 },
    { name: 'Горячий шоколад', count: 94, percent: 8 },
]

const pointsSales = [
    { name: 'ТЦ Мега', initials: 'ТМ', bgClass: 'bg-blue-100 dark:bg-blue-900/30', textClass: 'text-blue-600 dark:text-blue-400', revenue: '24 870 \u20BD', trendUp: true, trendValue: 15 },
    { name: 'Университет', initials: 'УН', bgClass: 'bg-purple-100 dark:bg-purple-900/30', textClass: 'text-purple-600 dark:text-purple-400', revenue: '19 340 \u20BD', trendUp: true, trendValue: 8 },
    { name: 'Больница №3', initials: 'Б3', bgClass: 'bg-red-100 dark:bg-red-900/30', textClass: 'text-red-600 dark:text-red-400', revenue: '16 210 \u20BD', trendUp: false, trendValue: 4 },
    { name: 'Вокзал', initials: 'ВК', bgClass: 'bg-orange-100 dark:bg-orange-900/30', textClass: 'text-orange-600 dark:text-orange-400', revenue: '15 890 \u20BD', trendUp: true, trendValue: 22 },
    { name: 'Автосалон Восток', initials: 'АВ', bgClass: 'bg-yellow-100 dark:bg-yellow-900/30', textClass: 'text-yellow-600 dark:text-yellow-400', revenue: '12 430 \u20BD', trendUp: false, trendValue: 7 },
    { name: 'Офис Сбербанк', initials: 'ОС', bgClass: 'bg-green-100 dark:bg-green-900/30', textClass: 'text-green-600 dark:text-green-400', revenue: '11 280 \u20BD', trendUp: true, trendValue: 3 },
]
</script>
