<template>
    <!-- Вариант C: Dashboard + Summary Cards + Compact List -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-900">
        <!-- Шапка -->
        <header class="bg-blue-600 px-4 pb-6 pt-4 dark:bg-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-200">Добрый день,</p>
                    <p class="text-lg font-bold text-white">Мой Белый Господин</p>
                </div>
                <button class="rounded-full bg-white/20 p-2">
                    <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </button>
            </div>

            <!-- Сводка -->
            <div class="mt-4 flex gap-3">
                <div class="flex-1 rounded-xl bg-white/15 p-3 backdrop-blur">
                    <p class="text-2xl font-bold text-white">3</p>
                    <p class="text-xs text-blue-200">Обслужено сегодня</p>
                </div>
                <div class="flex-1 rounded-xl bg-white/15 p-3 backdrop-blur">
                    <p class="text-2xl font-bold text-white">2</p>
                    <p class="text-xs text-blue-200">Требуют внимания</p>
                </div>
                <div class="flex-1 rounded-xl bg-white/15 p-3 backdrop-blur">
                    <p class="text-2xl font-bold text-white">18</p>
                    <p class="text-xs text-blue-200">Всего точек</p>
                </div>
            </div>
        </header>

        <!-- Контент с отрицательным margin для наезда на шапку -->
        <main class="flex-1 -mt-2 rounded-t-2xl bg-gray-50 px-4 pt-4 dark:bg-gray-900">
            <!-- Быстрые действия -->
            <div class="mb-4 flex gap-3">
                <button class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-white py-3 shadow-sm dark:bg-gray-800">
                    <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Обслужить</span>
                </button>
                <button class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-white py-3 shadow-sm dark:bg-gray-800">
                    <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Отчёт</span>
                </button>
            </div>

            <!-- Список точек -->
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Ближайшие точки</h2>
                <button class="text-xs text-blue-500 font-medium">Все</button>
            </div>

            <div class="space-y-2">
                <div v-for="point in points" :key="point.id"
                    class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm active:bg-gray-50 dark:bg-gray-800"
                >
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="point.bgClass">
                        <svg class="h-5 w-5" :class="point.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                            <span class="ml-2 shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ point.distance }}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ point.lastService }}</span>
                            <span class="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                            <span class="text-xs" :class="point.waterClass">{{ point.water }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Нижний бар — минимальный -->
        <nav class="fixed bottom-0 left-0 right-0 border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-md justify-around py-2">
                <button v-for="tab in tabs" :key="tab.label"
                    @click="activeTab = tab.label"
                    :class="activeTab === tab.label ? 'text-blue-500' : 'text-gray-400'"
                    class="flex flex-col items-center gap-0.5 px-4 py-1 text-xs transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="tab.icon" />
                    </svg>
                    {{ tab.label }}
                </button>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const activeTab = ref('Главная');

const tabs = [
    { label: 'Главная', icon: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25' },
    { label: 'Точки', icon: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z' },
    { label: 'Профиль', icon: 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z' },
];

const points = [
    { id: 5, name: 'Университет, корпус Б', distance: '0.3 км', lastService: 'Сегодня', water: '2 полных', waterClass: 'text-green-500', bgClass: 'bg-green-100 dark:bg-green-900/30', iconClass: 'text-green-600 dark:text-green-400' },
    { id: 1, name: 'ТЦ Мега', distance: '1.2 км', lastService: 'Вчера', water: '2 полных', waterClass: 'text-green-500', bgClass: 'bg-green-100 dark:bg-green-900/30', iconClass: 'text-green-600 dark:text-green-400' },
    { id: 3, name: 'Больница №3', distance: '2.5 км', lastService: '5 дней', water: 'мало воды', waterClass: 'text-red-500', bgClass: 'bg-red-100 dark:bg-red-900/30', iconClass: 'text-red-600 dark:text-red-400' },
    { id: 2, name: 'Офис Сбербанк', distance: '3.1 км', lastService: '2 дня', water: '1+0.4', waterClass: 'text-yellow-500', bgClass: 'bg-yellow-100 dark:bg-yellow-900/30', iconClass: 'text-yellow-600 dark:text-yellow-400' },
    { id: 4, name: 'Автосалон Восток', distance: '4.8 км', lastService: '3 дня', water: '1 бутылка', waterClass: 'text-yellow-500', bgClass: 'bg-gray-100 dark:bg-gray-700', iconClass: 'text-gray-500 dark:text-gray-400' },
    { id: 6, name: 'Вокзал (зал ожидания)', distance: '5.3 км', lastService: '4 дня', water: 'мало воды', waterClass: 'text-red-500', bgClass: 'bg-red-100 dark:bg-red-900/30', iconClass: 'text-red-600 dark:text-red-400' },
];
</script>
