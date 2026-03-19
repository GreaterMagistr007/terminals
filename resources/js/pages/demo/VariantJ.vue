<template>
    <!-- Вариант J: Segmented control + Grid — переключатель вверху, сетка карточек -->
    <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 pb-4 pt-4 shadow-sm dark:bg-gray-900">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Точки</h1>
                <button class="rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                </button>
            </div>

            <!-- Segmented control -->
            <div class="flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                <button v-for="segment in segments" :key="segment.label"
                    @click="activeSegment = segment.label"
                    :class="activeSegment === segment.label
                        ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 dark:text-gray-400'"
                    class="flex-1 rounded-lg py-2 text-center text-sm font-medium transition-all"
                >
                    {{ segment.label }}
                    <span v-if="segment.count"
                        class="ml-1 inline-flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-xs"
                        :class="activeSegment === segment.label
                            ? 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400'
                            : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-500'"
                    >{{ segment.count }}</span>
                </button>
            </div>
        </header>

        <!-- Сетка карточек -->
        <main class="flex-1 overflow-y-auto px-4 py-4">
            <div class="grid grid-cols-2 gap-3">
                <div v-for="point in filteredPoints" :key="point.id"
                    class="overflow-hidden rounded-2xl bg-white shadow-sm active:shadow-md dark:bg-gray-900"
                >
                    <!-- Цветная полоска сверху -->
                    <div class="h-1" :class="point.topBorder"></div>

                    <div class="p-3">
                        <!-- Иконка/эмодзи -->
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-2xl">{{ point.emoji }}</span>
                            <span v-if="point.urgent"
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30"
                            >
                                <svg class="h-3 w-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </span>
                        </div>

                        <!-- Название -->
                        <p class="text-sm font-semibold text-gray-900 leading-tight dark:text-white">{{ point.name }}</p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ point.lastService }}</p>

                        <!-- Полоска воды -->
                        <div class="mt-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-400 dark:text-gray-500">
                                    <svg class="inline h-3 w-3 mr-0.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                    </svg>
                                    Вода
                                </span>
                                <span class="font-medium" :class="point.waterTextClass">{{ point.water }}</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-2 rounded-full transition-all" :class="point.waterBarClass" :style="{ width: point.waterPercent + '%' }"></div>
                            </div>
                        </div>

                        <!-- Кнопка -->
                        <button class="mt-3 w-full rounded-lg py-2 text-xs font-medium transition-colors"
                            :class="point.urgent
                                ? 'bg-red-50 text-red-600 active:bg-red-100 dark:bg-red-900/20 dark:text-red-400'
                                : 'bg-gray-50 text-gray-600 active:bg-gray-100 dark:bg-gray-800 dark:text-gray-400'"
                        >
                            {{ point.urgent ? 'Обслужить!' : 'Подробнее' }}
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const activeSegment = ref('Все');

const segments = [
    { label: 'Все', count: 8 },
    { label: 'Срочные', count: 2 },
    { label: 'Мои', count: 4 },
];

const points = [
    {
        id: 3, name: 'Больница №3', emoji: '\u2615', lastService: '5 дней назад',
        water: '0.3', waterPercent: 15, waterBarClass: 'bg-red-500', waterTextClass: 'text-red-500',
        topBorder: 'bg-red-500', urgent: true, mine: true,
    },
    {
        id: 6, name: 'Вокзал (зал ожидания)', emoji: '\uD83D\uDE82', lastService: '4 дня назад',
        water: '0.6', waterPercent: 30, waterBarClass: 'bg-orange-500', waterTextClass: 'text-orange-500',
        topBorder: 'bg-orange-500', urgent: true, mine: false,
    },
    {
        id: 4, name: 'Автосалон Восток', emoji: '\uD83D\uDE97', lastService: '3 дня назад',
        water: '1.0', waterPercent: 50, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600',
        topBorder: 'bg-yellow-400', urgent: false, mine: true,
    },
    {
        id: 2, name: 'Офис Сбербанк', emoji: '\uD83C\uDFE2', lastService: '2 дня назад',
        water: '1.4', waterPercent: 60, waterBarClass: 'bg-blue-400', waterTextClass: 'text-blue-500',
        topBorder: 'bg-blue-400', urgent: false, mine: false,
    },
    {
        id: 5, name: 'Университет, корпус Б', emoji: '\uD83C\uDF93', lastService: 'Сегодня',
        water: '2.0', waterPercent: 100, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500',
        topBorder: 'bg-green-500', urgent: false, mine: true,
    },
    {
        id: 1, name: 'ТЦ Мега', emoji: '\uD83D\uDED2', lastService: 'Вчера',
        water: '2.0', waterPercent: 100, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500',
        topBorder: 'bg-green-500', urgent: false, mine: true,
    },
    {
        id: 7, name: 'Бизнес-центр Высота', emoji: '\uD83C\uDFE2', lastService: '2 дня назад',
        water: '1.2', waterPercent: 55, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600',
        topBorder: 'bg-yellow-400', urgent: false, mine: false,
    },
    {
        id: 8, name: 'Фитнес-клуб Энергия', emoji: '\uD83C\uDFCB\uFE0F', lastService: 'Вчера',
        water: '1.8', waterPercent: 90, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500',
        topBorder: 'bg-green-400', urgent: false, mine: false,
    },
];

const filteredPoints = computed(() => {
    if (activeSegment.value === 'Срочные') return points.filter(p => p.urgent);
    if (activeSegment.value === 'Мои') return points.filter(p => p.mine);
    return points;
});
</script>
