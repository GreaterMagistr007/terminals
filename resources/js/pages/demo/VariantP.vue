<template>
    <!-- Вариант P: Stock & Ingredients Overview — админский обзор остатков по всем аппаратам -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 py-3 shadow-sm dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Остатки ингредиентов</h1>
                <div class="flex items-center gap-2">
                    <button class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 active:bg-gray-200 dark:hover:bg-gray-800 dark:active:bg-gray-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                    </button>
                    <button class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 active:bg-gray-200 dark:hover:bg-gray-800 dark:active:bg-gray-700">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Сводные карточки -->
        <div class="flex gap-3 overflow-x-auto px-4 py-4">
            <div class="flex shrink-0 flex-col rounded-2xl bg-white p-3.5 shadow-sm dark:bg-gray-900 min-w-[140px]">
                <div class="mb-1 flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">На складе</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">87 420 \u20BD</p>
            </div>
            <div class="flex shrink-0 flex-col rounded-2xl bg-white p-3.5 shadow-sm dark:bg-gray-900 min-w-[140px]">
                <div class="mb-1 flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                    <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Мало остатков</p>
                <div class="flex items-center gap-1.5">
                    <p class="text-lg font-bold text-red-500">{{ lowStockCount }}</p>
                    <span class="rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-bold text-red-600 dark:bg-red-900/40 dark:text-red-400">!</span>
                </div>
            </div>
            <div class="flex shrink-0 flex-col rounded-2xl bg-white p-3.5 shadow-sm dark:bg-gray-900 min-w-[140px]">
                <div class="mb-1 flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30">
                    <svg class="h-4 w-4 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500">Нужна загрузка</p>
                <p class="text-lg font-bold text-orange-500">{{ needLoadingCount }}</p>
            </div>
        </div>

        <!-- Легенда цветов -->
        <div class="flex items-center justify-center gap-4 px-4 pb-2">
            <div class="flex items-center gap-1.5">
                <span class="h-2 w-4 rounded-sm bg-green-500"></span>
                <span class="text-xs text-gray-400 dark:text-gray-500">&gt;50%</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2 w-4 rounded-sm bg-yellow-400"></span>
                <span class="text-xs text-gray-400 dark:text-gray-500">20-50%</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="h-2 w-4 rounded-sm bg-red-500"></span>
                <span class="text-xs text-gray-400 dark:text-gray-500">&lt;20%</span>
            </div>
        </div>

        <!-- Список точек -->
        <main class="flex-1 overflow-y-auto px-4 pb-4">
            <div class="space-y-3">
                <div
                    v-for="point in points"
                    :key="point.id"
                    class="rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900"
                    :class="point.hasLow ? 'ring-1 ring-red-200 dark:ring-red-900/50' : ''"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="point.bgClass">
                                <span class="text-xs font-bold" :class="point.textClass">{{ point.initials }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Обслужен: {{ point.lastService }}</p>
                            </div>
                        </div>
                        <button
                            v-if="point.hasLow"
                            class="shrink-0 rounded-lg bg-red-500 px-3 py-1.5 text-xs font-semibold text-white active:bg-red-600 transition-colors"
                        >
                            Загрузить
                        </button>
                    </div>

                    <!-- Бары ингредиентов -->
                    <div class="space-y-2">
                        <div v-for="ing in point.ingredients" :key="ing.name" class="flex items-center gap-2">
                            <span class="w-16 shrink-0 text-xs text-gray-400 dark:text-gray-500 text-right">{{ ing.name }}</span>
                            <div class="flex-1 h-3 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :class="getBarColor(ing.percent)"
                                    :style="{ width: ing.percent + '%' }"
                                ></div>
                            </div>
                            <span
                                class="w-9 shrink-0 text-right text-xs font-semibold"
                                :class="getTextColor(ing.percent)"
                            >{{ ing.percent }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Итоги за неделю -->
            <div class="mt-6 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Расход за неделю</p>
                <div class="grid grid-cols-4 gap-3">
                    <div v-for="total in weeklyTotals" :key="total.name" class="text-center">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ total.amount }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ total.name }}</p>
                        <p class="text-xs" :class="total.trend > 0 ? 'text-red-500' : 'text-green-500'">
                            {{ total.trend > 0 ? '+' : '' }}{{ total.trend }}%
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const points = [
    {
        id: 1,
        name: 'Больница №3',
        initials: 'Б3',
        bgClass: 'bg-red-100 dark:bg-red-900/30',
        textClass: 'text-red-600 dark:text-red-400',
        lastService: '16.03',
        hasLow: true,
        ingredients: [
            { name: 'Кофе', percent: 15 },
            { name: 'Молоко', percent: 8 },
            { name: 'Сахар', percent: 62 },
            { name: 'Шоколад', percent: 5 },
        ],
    },
    {
        id: 2,
        name: 'Автосалон Восток',
        initials: 'АВ',
        bgClass: 'bg-yellow-100 dark:bg-yellow-900/30',
        textClass: 'text-yellow-600 dark:text-yellow-400',
        lastService: '17.03',
        hasLow: true,
        ingredients: [
            { name: 'Кофе', percent: 35 },
            { name: 'Молоко', percent: 42 },
            { name: 'Сахар', percent: 78 },
            { name: 'Шоколад', percent: 18 },
        ],
    },
    {
        id: 3,
        name: 'ТЦ Мега',
        initials: 'ТМ',
        bgClass: 'bg-green-100 dark:bg-green-900/30',
        textClass: 'text-green-600 dark:text-green-400',
        lastService: '18.03',
        hasLow: false,
        ingredients: [
            { name: 'Кофе', percent: 81 },
            { name: 'Молоко', percent: 67 },
            { name: 'Сахар', percent: 93 },
            { name: 'Шоколад', percent: 55 },
        ],
    },
    {
        id: 4,
        name: 'Университет',
        initials: 'УН',
        bgClass: 'bg-blue-100 dark:bg-blue-900/30',
        textClass: 'text-blue-600 dark:text-blue-400',
        lastService: '18.03',
        hasLow: false,
        ingredients: [
            { name: 'Кофе', percent: 52 },
            { name: 'Молоко', percent: 61 },
            { name: 'Сахар', percent: 74 },
            { name: 'Шоколад', percent: 88 },
        ],
    },
    {
        id: 5,
        name: 'Офис Сбербанк',
        initials: 'ОС',
        bgClass: 'bg-emerald-100 dark:bg-emerald-900/30',
        textClass: 'text-emerald-600 dark:text-emerald-400',
        lastService: '17.03',
        hasLow: true,
        ingredients: [
            { name: 'Кофе', percent: 28 },
            { name: 'Молоко', percent: 14 },
            { name: 'Сахар', percent: 45 },
            { name: 'Шоколад', percent: 33 },
        ],
    },
    {
        id: 6,
        name: 'Вокзал',
        initials: 'ВК',
        bgClass: 'bg-purple-100 dark:bg-purple-900/30',
        textClass: 'text-purple-600 dark:text-purple-400',
        lastService: '15.03',
        hasLow: true,
        ingredients: [
            { name: 'Кофе', percent: 11 },
            { name: 'Молоко', percent: 19 },
            { name: 'Сахар', percent: 34 },
            { name: 'Шоколад', percent: 7 },
        ],
    },
]

const weeklyTotals = [
    { name: 'Кофе', amount: '12.4 кг', trend: 8 },
    { name: 'Молоко', amount: '18.7 л', trend: 12 },
    { name: 'Сахар', amount: '6.2 кг', trend: -3 },
    { name: 'Шоколад', amount: '4.1 кг', trend: 5 },
]

const lowStockCount = computed(() => {
    let count = 0
    points.forEach(p => {
        p.ingredients.forEach(i => {
            if (i.percent < 20) count++
        })
    })
    return count
})

const needLoadingCount = computed(() => {
    return points.filter(p => p.hasLow).length
})

function getBarColor(percent) {
    if (percent < 20) return 'bg-red-500'
    if (percent < 50) return 'bg-yellow-400'
    return 'bg-green-500'
}

function getTextColor(percent) {
    if (percent < 20) return 'text-red-500'
    if (percent < 50) return 'text-yellow-500'
    return 'text-green-500'
}
</script>
