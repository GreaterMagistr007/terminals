<template>
    <!-- Вариант I: Timeline/Feed — вертикальная лента событий -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 shadow-sm dark:bg-gray-900">
            <div class="flex h-14 items-center justify-between">
                <div class="flex items-center gap-3">
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">Лента</h1>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        12 событий
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button class="rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Лента -->
        <main class="flex-1 overflow-y-auto px-4 pb-20 pt-4">
            <div v-for="(group, groupIndex) in timelineGroups" :key="group.date">
                <!-- Заголовок даты -->
                <div class="flex items-center gap-3 mb-4" :class="groupIndex > 0 ? 'mt-6' : ''">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ group.date }}</span>
                    <div class="flex-1 border-b border-gray-200 dark:border-gray-800"></div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ group.events.length }} записей</span>
                </div>

                <!-- Таймлайн -->
                <div class="relative ml-4">
                    <!-- Вертикальная линия -->
                    <div class="absolute left-3 top-0 bottom-0 w-px bg-gray-200 dark:bg-gray-800"></div>

                    <div v-for="(event, index) in group.events" :key="event.id" class="relative mb-4 last:mb-0">
                        <!-- Точка на линии -->
                        <div class="absolute left-0 top-4 flex h-6 w-6 items-center justify-center">
                            <div class="h-3 w-3 rounded-full border-2 border-white dark:border-gray-950" :class="event.dotClass"></div>
                        </div>

                        <!-- Карточка события -->
                        <div class="ml-10 rounded-xl bg-white p-4 shadow-sm active:bg-gray-50 dark:bg-gray-900 dark:active:bg-gray-800">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded px-1.5 py-0.5 text-xs font-medium" :class="event.typeClass">{{ event.type }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ event.time }}</span>
                                    </div>
                                    <p class="mt-1.5 font-medium text-gray-900 dark:text-white">{{ event.point }}</p>
                                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ event.description }}</p>
                                </div>
                            </div>

                            <!-- Детали -->
                            <div v-if="event.details.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="detail in event.details" :key="detail"
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                                >
                                    {{ detail }}
                                </span>
                            </div>

                            <!-- Оператор -->
                            <div class="mt-3 flex items-center gap-2">
                                <div class="h-5 w-5 rounded-full flex items-center justify-center text-xs font-bold text-white" :class="event.operatorBg">
                                    {{ event.operatorInitials }}
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ event.operator }}</span>
                                <div class="flex-1"></div>
                                <!-- Фото индикатор -->
                                <span v-if="event.photos" class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                    </svg>
                                    {{ event.photos }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- FAB — добавить запись -->
        <button class="fixed bottom-6 right-6 flex h-14 w-14 items-center justify-center rounded-full bg-blue-500 shadow-lg shadow-blue-500/30 active:bg-blue-600">
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>
    </div>
</template>

<script setup>
const timelineGroups = [
    {
        date: 'Сегодня',
        events: [
            {
                id: 1, time: '14:30', point: 'Университет, корпус Б',
                type: 'Обслуживание', typeClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                dotClass: 'bg-green-500',
                description: 'Полное обслуживание. Заменена вода, добавлены ингредиенты.',
                details: ['Вода: 2 бутылки', 'Молоко: заменено', 'Сахар: добавлен'],
                operator: 'Иван Петров', operatorInitials: 'ИП', operatorBg: 'bg-blue-500',
                photos: 2,
            },
            {
                id: 2, time: '11:15', point: 'ТЦ Мега',
                type: 'Проверка', typeClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                dotClass: 'bg-blue-500',
                description: 'Плановая проверка, всё в норме.',
                details: ['Вода: ок', 'Аппарат: рабочий'],
                operator: 'Иван Петров', operatorInitials: 'ИП', operatorBg: 'bg-blue-500',
                photos: 1,
            },
            {
                id: 3, time: '09:00', point: 'Больница №3',
                type: 'Срочное', typeClass: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                dotClass: 'bg-red-500',
                description: 'Заканчивается вода. Нужна доставка до конца дня.',
                details: ['Вода: 0.3 бутылки', 'Молоко: закончилось'],
                operator: 'Система', operatorInitials: 'С', operatorBg: 'bg-gray-500',
                photos: 0,
            },
        ],
    },
    {
        date: 'Вчера',
        events: [
            {
                id: 4, time: '16:45', point: 'Офис Сбербанк',
                type: 'Обслуживание', typeClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                dotClass: 'bg-green-500',
                description: 'Обслуживание завершено. Привёз 2 бутылки.',
                details: ['Вода: 2 бутылки', 'Фильтр: заменён'],
                operator: 'Пётр Сидоров', operatorInitials: 'ПС', operatorBg: 'bg-purple-500',
                photos: 2,
            },
            {
                id: 5, time: '13:20', point: 'Автосалон Восток',
                type: 'Доставка', typeClass: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
                dotClass: 'bg-yellow-500',
                description: 'Доставлены ингредиенты. Вода ещё есть.',
                details: ['Шоколад: 2 уп.', 'Молоко: 3 л'],
                operator: 'Пётр Сидоров', operatorInitials: 'ПС', operatorBg: 'bg-purple-500',
                photos: 0,
            },
            {
                id: 6, time: '10:00', point: 'Фитнес-клуб Энергия',
                type: 'Обслуживание', typeClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                dotClass: 'bg-green-500',
                description: 'Замена воды и чистка аппарата.',
                details: ['Вода: 2 бутылки', 'Чистка: выполнена'],
                operator: 'Иван Петров', operatorInitials: 'ИП', operatorBg: 'bg-blue-500',
                photos: 2,
            },
        ],
    },
    {
        date: '17 марта',
        events: [
            {
                id: 7, time: '15:30', point: 'Вокзал (зал ожидания)',
                type: 'Обслуживание', typeClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                dotClass: 'bg-green-500',
                description: 'Полное обслуживание. Высокий расход воды.',
                details: ['Вода: 2 бутылки', 'Расход: высокий'],
                operator: 'Пётр Сидоров', operatorInitials: 'ПС', operatorBg: 'bg-purple-500',
                photos: 1,
            },
            {
                id: 8, time: '11:00', point: 'Бизнес-центр Высота',
                type: 'Проверка', typeClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                dotClass: 'bg-blue-500',
                description: 'Запланированная проверка, без замечаний.',
                details: ['Статус: ок'],
                operator: 'Иван Петров', operatorInitials: 'ИП', operatorBg: 'bg-blue-500',
                photos: 0,
            },
        ],
    },
];
</script>
