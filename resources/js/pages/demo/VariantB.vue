<template>
    <!-- Вариант B: iOS-style Large Title + Search + Compact List -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-900">
        <!-- Шапка с крупным заголовком -->
        <header class="bg-white px-4 pb-3 pt-3 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">МБ</div>
                <div class="flex gap-2">
                    <button class="rounded-full bg-gray-100 p-2 dark:bg-gray-700">
                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                    </button>
                    <button class="rounded-full bg-gray-100 p-2 dark:bg-gray-700">
                        <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>
            </div>
            <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">Точки</h1>
            <!-- Поиск -->
            <div class="mt-3 relative">
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <input type="text" placeholder="Поиск по точкам" class="w-full rounded-lg bg-gray-100 py-2 pl-9 pr-3 text-sm text-gray-700 placeholder-gray-400 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-500" />
            </div>
        </header>

        <!-- Список -->
        <main class="flex-1 px-4 pt-4">
            <!-- Секция: требуют внимания -->
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-red-500">Требуют внимания</p>
            <div class="mb-4 rounded-xl bg-white shadow-sm dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="point in urgentPoints" :key="point.id" class="flex items-center gap-3 px-4 py-3 active:bg-gray-50 dark:active:bg-gray-750">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="point.bgClass">
                        <span class="text-lg">{{ point.emoji }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ point.subtitle }}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>

            <!-- Секция: все точки -->
            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Все точки</p>
            <div class="rounded-xl bg-white shadow-sm dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                <div v-for="point in allPoints" :key="point.id" class="flex items-center gap-3 px-4 py-3 active:bg-gray-50 dark:active:bg-gray-750">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                        <span class="text-lg">{{ point.emoji }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ point.subtitle }}</p>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
const urgentPoints = [
    { id: 3, name: 'Больница №3', subtitle: '5 дней без обслуживания · воды мало', emoji: '🏥', bgClass: 'bg-red-100 dark:bg-red-900/30' },
    { id: 6, name: 'Вокзал (зал ожидания)', subtitle: '4 дня без обслуживания', emoji: '🚂', bgClass: 'bg-yellow-100 dark:bg-yellow-900/30' },
];

const allPoints = [
    { id: 5, name: 'Университет, корпус Б', subtitle: 'Сегодня, 09:15 · 2 полных', emoji: '🎓' },
    { id: 1, name: 'ТЦ Мега', subtitle: 'Вчера, 14:30 · 2 полных', emoji: '🏬' },
    { id: 2, name: 'Офис Сбербанк', subtitle: '2 дня назад · 1+0.4', emoji: '🏦' },
    { id: 4, name: 'Автосалон Восток', subtitle: '3 дня назад · 1 бутылка', emoji: '🚗' },
    { id: 7, name: 'Школа №12', subtitle: 'Вчера, 11:00 · 1+0.8', emoji: '🏫' },
    { id: 8, name: 'Фитнес-клуб Энергия', subtitle: '2 дня назад · 2 полных', emoji: '💪' },
];
</script>
