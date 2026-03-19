<template>
    <!-- Вариант L: Stories + List — горизонтальные stories сверху, список снизу -->
    <div class="flex min-h-screen flex-col bg-white dark:bg-gray-950">
        <!-- Шапка -->
        <header class="px-4 pt-4 pb-2">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Terminals</h1>
                <div class="flex items-center gap-2">
                    <button class="relative rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500"></span>
                    </button>
                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">АН</div>
                </div>
            </div>
        </header>

        <!-- Stories (горизонтальный скролл) -->
        <div class="px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">Требуют внимания</p>
            <div class="flex gap-4 overflow-x-auto pb-2 -mx-4 px-4">
                <div v-for="story in stories" :key="story.id" class="flex shrink-0 flex-col items-center gap-1.5">
                    <!-- Кольцо story -->
                    <div class="rounded-full p-0.5" :class="story.ringClass">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white dark:bg-gray-950">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full" :class="story.bgClass">
                                <span class="text-sm font-bold" :class="story.textClass">{{ story.initials }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="max-w-16 text-center text-xs text-gray-600 truncate dark:text-gray-400">{{ story.shortName }}</span>
                </div>

                <!-- Кнопка "Добавить" -->
                <div class="flex shrink-0 flex-col items-center gap-1.5">
                    <div class="flex h-[4.25rem] w-[4.25rem] items-center justify-center rounded-full border-2 border-dashed border-gray-200 dark:border-gray-700">
                        <svg class="h-6 w-6 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Добавить</span>
                </div>
            </div>
        </div>

        <!-- Разделитель -->
        <div class="border-b border-gray-100 dark:border-gray-900"></div>

        <!-- Список точек -->
        <main class="flex-1 overflow-y-auto">
            <div class="px-4 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Все точки</h2>
                    <button class="flex items-center gap-1 text-xs text-blue-500 font-medium">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5-4.5L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                        </svg>
                        Сортировка
                    </button>
                </div>
            </div>

            <div v-for="point in listPoints" :key="point.id"
                class="flex items-center gap-3 px-4 py-3 active:bg-gray-50 dark:active:bg-gray-900"
            >
                <!-- Аватар -->
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="point.bgClass">
                    <span class="text-sm font-semibold" :class="point.textClass">{{ point.initials }}</span>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                        <span v-if="point.urgent"
                            class="shrink-0 rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400"
                        >Срочно</span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ point.address }}</span>
                    </div>
                </div>

                <div class="shrink-0 text-right">
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ point.lastService }}</p>
                    <div class="flex items-center gap-1 mt-1 justify-end">
                        <!-- Мини-бар воды -->
                        <div class="h-1 w-8 rounded-full bg-gray-200 dark:bg-gray-800">
                            <div class="h-1 rounded-full" :class="point.waterBarClass" :style="{ width: point.waterPercent + '%' }"></div>
                        </div>
                        <span class="text-xs" :class="point.waterColor">{{ point.water }}</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
/** Stories — срочные / недавние точки, кольцо показывает статус */
const stories = [
    { id: 3, initials: 'Б3', shortName: 'Больница', ringClass: 'bg-gradient-to-br from-red-500 to-orange-500', bgClass: 'bg-red-100 dark:bg-red-900/30', textClass: 'text-red-700 dark:text-red-400' },
    { id: 6, initials: 'ВЗ', shortName: 'Вокзал', ringClass: 'bg-gradient-to-br from-orange-500 to-yellow-500', bgClass: 'bg-orange-100 dark:bg-orange-900/30', textClass: 'text-orange-700 dark:text-orange-400' },
    { id: 4, initials: 'АВ', shortName: 'Автосалон', ringClass: 'bg-gradient-to-br from-yellow-400 to-amber-500', bgClass: 'bg-yellow-100 dark:bg-yellow-900/30', textClass: 'text-yellow-700 dark:text-yellow-400' },
    { id: 5, initials: 'УБ', shortName: 'Универ', ringClass: 'bg-gradient-to-br from-green-400 to-emerald-500', bgClass: 'bg-green-100 dark:bg-green-900/30', textClass: 'text-green-700 dark:text-green-400' },
    { id: 1, initials: 'ТМ', shortName: 'ТЦ Мега', ringClass: 'bg-gradient-to-br from-green-400 to-emerald-500', bgClass: 'bg-green-100 dark:bg-green-900/30', textClass: 'text-green-700 dark:text-green-400' },
];

/** Полный список точек */
const listPoints = [
    {
        id: 3, name: 'Больница №3', initials: 'Б3', address: 'ул. Советская, 78',
        lastService: '5 дней', water: '0.3', waterPercent: 15,
        waterBarClass: 'bg-red-500', waterColor: 'text-red-500',
        bgClass: 'bg-red-100 dark:bg-red-900/30', textClass: 'text-red-700 dark:text-red-400',
        urgent: true,
    },
    {
        id: 6, name: 'Вокзал (зал ожидания)', initials: 'ВЗ', address: 'Привокзальная пл., 1',
        lastService: '4 дня', water: '0.6', waterPercent: 30,
        waterBarClass: 'bg-orange-500', waterColor: 'text-orange-500',
        bgClass: 'bg-orange-100 dark:bg-orange-900/30', textClass: 'text-orange-700 dark:text-orange-400',
        urgent: true,
    },
    {
        id: 4, name: 'Автосалон Восток', initials: 'АВ', address: 'ул. Промышленная, 5',
        lastService: '3 дня', water: '1.0', waterPercent: 50,
        waterBarClass: 'bg-yellow-400', waterColor: 'text-yellow-600',
        bgClass: 'bg-yellow-100 dark:bg-yellow-900/30', textClass: 'text-yellow-700 dark:text-yellow-400',
        urgent: false,
    },
    {
        id: 2, name: 'Офис Сбербанк', initials: 'ОС', address: 'пр. Мира, 12',
        lastService: '2 дня', water: '1.4', waterPercent: 60,
        waterBarClass: 'bg-blue-400', waterColor: 'text-blue-500',
        bgClass: 'bg-blue-100 dark:bg-blue-900/30', textClass: 'text-blue-700 dark:text-blue-400',
        urgent: false,
    },
    {
        id: 5, name: 'Университет, корпус Б', initials: 'УБ', address: 'ул. Академическая, 22',
        lastService: 'Сегодня', water: '2.0', waterPercent: 100,
        waterBarClass: 'bg-green-500', waterColor: 'text-green-500',
        bgClass: 'bg-green-100 dark:bg-green-900/30', textClass: 'text-green-700 dark:text-green-400',
        urgent: false,
    },
    {
        id: 1, name: 'ТЦ Мега', initials: 'ТМ', address: 'ул. Ленина, 45',
        lastService: 'Вчера', water: '2.0', waterPercent: 100,
        waterBarClass: 'bg-green-500', waterColor: 'text-green-500',
        bgClass: 'bg-green-100 dark:bg-green-900/30', textClass: 'text-green-700 dark:text-green-400',
        urgent: false,
    },
    {
        id: 7, name: 'Бизнес-центр Высота', initials: 'БВ', address: 'ул. Деловая, 10',
        lastService: '2 дня', water: '1.2', waterPercent: 55,
        waterBarClass: 'bg-yellow-400', waterColor: 'text-yellow-600',
        bgClass: 'bg-yellow-100 dark:bg-yellow-900/30', textClass: 'text-yellow-700 dark:text-yellow-400',
        urgent: false,
    },
    {
        id: 8, name: 'Фитнес-клуб Энергия', initials: 'ФЭ', address: 'ул. Спортивная, 3',
        lastService: 'Вчера', water: '1.8', waterPercent: 90,
        waterBarClass: 'bg-green-500', waterColor: 'text-green-500',
        bgClass: 'bg-green-100 dark:bg-green-900/30', textClass: 'text-green-700 dark:text-green-400',
        urgent: false,
    },
];
</script>
