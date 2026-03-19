<template>
    <!-- Вариант D: Telegram/WhatsApp style — чат-список с аватарами, бейджи срочности, FAB -->
    <div class="flex min-h-screen flex-col bg-white dark:bg-gray-900">
        <!-- Верхний бар -->
        <header class="bg-white px-4 dark:bg-gray-900">
            <div class="flex h-14 items-center justify-between border-b border-gray-100 dark:border-gray-800">
                <span class="text-xl font-bold text-gray-900 dark:text-white">Точки</span>
                <div class="flex items-center gap-2">
                    <button class="rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </button>
                    <button class="rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Список чатов -->
        <main class="flex-1 overflow-y-auto">
            <div v-for="point in points" :key="point.id"
                class="relative flex items-center gap-3 px-4 py-3 active:bg-gray-50 dark:active:bg-gray-800"
            >
                <!-- Аватар с инициалами -->
                <div class="relative shrink-0">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full text-sm font-semibold text-white"
                        :class="point.avatarBg"
                    >
                        {{ point.initials }}
                    </div>
                    <!-- Онлайн/статус индикатор -->
                    <span v-if="point.online"
                        class="absolute bottom-0 right-0 h-3.5 w-3.5 rounded-full border-2 border-white bg-green-400 dark:border-gray-900"
                    ></span>
                </div>

                <!-- Контент -->
                <div class="flex-1 min-w-0 border-b border-gray-50 pb-3 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <p class="font-semibold text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                        <span class="ml-2 shrink-0 text-xs" :class="point.urgent ? 'text-green-500 font-medium' : 'text-gray-400 dark:text-gray-500'">{{ point.time }}</span>
                    </div>
                    <div class="flex items-center justify-between mt-0.5">
                        <p class="text-sm text-gray-500 truncate dark:text-gray-400">
                            <span v-if="point.lastMessage.includes('фото')" class="inline-flex items-center gap-1">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                                {{ point.lastMessage }}
                            </span>
                            <span v-else>{{ point.lastMessage }}</span>
                        </p>
                        <!-- Бейдж непрочитанных / срочности -->
                        <span v-if="point.badge"
                            class="ml-2 flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1.5 text-xs font-bold text-white"
                            :class="point.urgent ? 'bg-red-500' : 'bg-green-500'"
                        >
                            {{ point.badge }}
                        </span>
                        <!-- Закреплено -->
                        <svg v-if="point.pinned && !point.badge" class="ml-2 h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </main>

        <!-- FAB — плавающая кнопка -->
        <button class="fixed bottom-6 right-6 flex h-14 w-14 items-center justify-center rounded-full bg-green-500 shadow-lg shadow-green-500/30 active:bg-green-600">
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
        </button>
    </div>
</template>

<script setup>
const points = [
    {
        id: 3, name: 'Больница №3', initials: 'Б3', avatarBg: 'bg-red-500',
        time: '10 мин', lastMessage: 'Мало воды! Нужно обслуживание',
        badge: '!', urgent: true, pinned: false, online: false,
    },
    {
        id: 6, name: 'Вокзал (зал ожидания)', initials: 'ВЗ', avatarBg: 'bg-orange-500',
        time: '2ч', lastMessage: 'Нужны ингредиенты: молоко, сахар',
        badge: '2', urgent: true, pinned: false, online: false,
    },
    {
        id: 5, name: 'Университет, корпус Б', initials: 'УБ', avatarBg: 'bg-blue-500',
        time: '09:15', lastMessage: '2 фото прикреплено',
        badge: null, urgent: false, pinned: true, online: true,
    },
    {
        id: 1, name: 'ТЦ Мега', initials: 'ТМ', avatarBg: 'bg-purple-500',
        time: 'Вчера', lastMessage: 'Обслуживание завершено. Вода полная.',
        badge: null, urgent: false, pinned: true, online: true,
    },
    {
        id: 2, name: 'Офис Сбербанк', initials: 'ОС', avatarBg: 'bg-green-600',
        time: 'Вт', lastMessage: 'Принёс 2 бутылки воды, заменил фильтр',
        badge: null, urgent: false, pinned: false, online: false,
    },
    {
        id: 4, name: 'Автосалон Восток', initials: 'АВ', avatarBg: 'bg-cyan-600',
        time: 'Пн', lastMessage: 'Всё в норме, следующий визит через 3 дня',
        badge: null, urgent: false, pinned: false, online: false,
    },
    {
        id: 7, name: 'Бизнес-центр Высота', initials: 'БВ', avatarBg: 'bg-indigo-500',
        time: 'Пн', lastMessage: 'Заменил молоко и шоколад',
        badge: null, urgent: false, pinned: false, online: false,
    },
    {
        id: 8, name: 'Фитнес-клуб Энергия', initials: 'ФЭ', avatarBg: 'bg-teal-500',
        time: '12.03', lastMessage: '1 фото прикреплено',
        badge: null, urgent: false, pinned: false, online: false,
    },
];
</script>
