<template>
    <!-- Вариант E: Kanban/Columns — горизонтальные колонки статусов -->
    <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 shadow-sm dark:bg-gray-900">
            <div class="flex h-14 items-center justify-between">
                <div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Доска</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">8 точек обслуживания</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        + Фильтр
                    </button>
                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">АН</div>
                </div>
            </div>
        </header>

        <!-- Горизонтальный скролл колонок -->
        <main class="flex-1 overflow-x-auto overflow-y-hidden">
            <div class="flex h-full gap-3 px-4 py-4" style="min-width: max-content;">
                <!-- Колонка -->
                <div v-for="column in columns" :key="column.title" class="flex w-72 shrink-0 flex-col">
                    <!-- Заголовок колонки -->
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" :class="column.dotClass"></span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ column.title }}</span>
                            <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-gray-200 px-1.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                {{ column.items.length }}
                            </span>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Карточки -->
                    <div class="flex-1 space-y-2 overflow-y-auto pb-4">
                        <div v-for="item in column.items" :key="item.id"
                            class="rounded-xl bg-white p-3 shadow-sm active:shadow-md dark:bg-gray-900"
                        >
                            <!-- Цветная метка сверху -->
                            <div class="mb-2 flex items-center justify-between">
                                <span class="rounded px-2 py-0.5 text-xs font-medium" :class="item.tagClass">{{ item.tag }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ item.daysAgo }}</span>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ item.name }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ item.address }}</p>

                            <!-- Прогресс воды -->
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-xs mb-1">
                                    <span class="text-gray-400 dark:text-gray-500">Вода</span>
                                    <span class="font-medium" :class="item.waterTextClass">{{ item.water }}</span>
                                </div>
                                <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-1.5 rounded-full" :class="item.waterBarClass" :style="{ width: item.waterPercent + '%' }"></div>
                                </div>
                            </div>

                            <!-- Низ карточки -->
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex -space-x-1">
                                    <div class="h-5 w-5 rounded-full border border-white bg-blue-400 dark:border-gray-900" :title="item.operator"></div>
                                </div>
                                <div class="flex items-center gap-2 text-gray-400 dark:text-gray-500">
                                    <span v-if="item.hasPhotos" class="flex items-center gap-0.5 text-xs">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
                                        </svg>
                                        2
                                    </span>
                                    <span v-if="item.hasComment" class="flex items-center gap-0.5 text-xs">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                        </svg>
                                        1
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
const columns = [
    {
        title: 'Срочно',
        dotClass: 'bg-red-500',
        items: [
            {
                id: 3, name: 'Больница №3', address: 'ул. Советская, 78',
                tag: 'Критично', tagClass: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                daysAgo: '5 дн. назад', water: '0.3 бут.', waterPercent: 15,
                waterBarClass: 'bg-red-500', waterTextClass: 'text-red-500',
                operator: 'Иван', hasPhotos: false, hasComment: true,
            },
            {
                id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1',
                tag: 'Мало воды', tagClass: 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
                daysAgo: '4 дн. назад', water: '0.6 бут.', waterPercent: 30,
                waterBarClass: 'bg-orange-500', waterTextClass: 'text-orange-500',
                operator: 'Пётр', hasPhotos: true, hasComment: false,
            },
        ],
    },
    {
        title: 'Скоро',
        dotClass: 'bg-yellow-500',
        items: [
            {
                id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5',
                tag: 'Через 1 день', tagClass: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400',
                daysAgo: '3 дн. назад', water: '1 бут.', waterPercent: 50,
                waterBarClass: 'bg-yellow-500', waterTextClass: 'text-yellow-600',
                operator: 'Иван', hasPhotos: true, hasComment: true,
            },
            {
                id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12',
                tag: 'Плановое', tagClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                daysAgo: '2 дн. назад', water: '1.4 бут.', waterPercent: 60,
                waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600',
                operator: 'Пётр', hasPhotos: false, hasComment: false,
            },
            {
                id: 7, name: 'Бизнес-центр Высота', address: 'ул. Деловая, 10',
                tag: 'Плановое', tagClass: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                daysAgo: '2 дн. назад', water: '1.2 бут.', waterPercent: 55,
                waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600',
                operator: 'Иван', hasPhotos: false, hasComment: true,
            },
        ],
    },
    {
        title: 'Ок',
        dotClass: 'bg-green-500',
        items: [
            {
                id: 5, name: 'Университет, корпус Б', address: 'ул. Академическая, 22',
                tag: 'Обслужен', tagClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                daysAgo: 'Сегодня', water: '2 полных', waterPercent: 100,
                waterBarClass: 'bg-green-500', waterTextClass: 'text-green-600',
                operator: 'Иван', hasPhotos: true, hasComment: true,
            },
            {
                id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45',
                tag: 'Обслужен', tagClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                daysAgo: 'Вчера', water: '2 полных', waterPercent: 100,
                waterBarClass: 'bg-green-500', waterTextClass: 'text-green-600',
                operator: 'Пётр', hasPhotos: true, hasComment: false,
            },
            {
                id: 8, name: 'Фитнес-клуб Энергия', address: 'ул. Спортивная, 3',
                tag: 'Обслужен', tagClass: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
                daysAgo: 'Вчера', water: '1.8 бут.', waterPercent: 90,
                waterBarClass: 'bg-green-500', waterTextClass: 'text-green-600',
                operator: 'Пётр', hasPhotos: false, hasComment: false,
            },
        ],
    },
];
</script>
