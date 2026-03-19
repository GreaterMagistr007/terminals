<template>
    <!-- Вариант M: Split status header — сегментированная полоса статусов, группированный список -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 pt-4 pb-5 dark:bg-gray-900">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Обзор точек</h1>
                <div class="flex items-center gap-2">
                    <button class="rounded-full p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                    </button>
                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">АН</div>
                </div>
            </div>

            <!-- Сегментированная полоса статусов -->
            <div class="mb-3">
                <div class="flex h-3 overflow-hidden rounded-full">
                    <div class="bg-red-500 transition-all" :style="{ width: redPercent + '%' }"></div>
                    <div class="bg-yellow-400 transition-all" :style="{ width: yellowPercent + '%' }"></div>
                    <div class="bg-green-500 transition-all" :style="{ width: greenPercent + '%' }"></div>
                </div>
            </div>

            <!-- Легенда -->
            <div class="flex justify-between">
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-red-500"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Срочно</span>
                    <span class="text-xs font-bold text-red-500">{{ redCount }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-yellow-400"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Внимание</span>
                    <span class="text-xs font-bold text-yellow-500">{{ yellowCount }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-full bg-green-500"></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">В норме</span>
                    <span class="text-xs font-bold text-green-500">{{ greenCount }}</span>
                </div>
            </div>
        </header>

        <!-- Группированный список -->
        <main class="flex-1 overflow-y-auto px-4 pt-4 pb-4">
            <div v-for="group in groups" :key="group.title" class="mb-6 last:mb-0">
                <!-- Заголовок группы -->
                <div class="flex items-center gap-2 mb-2">
                    <span class="h-2 w-2 rounded-full" :class="group.dotClass"></span>
                    <span class="text-xs font-semibold uppercase tracking-wider" :class="group.titleClass">{{ group.title }}</span>
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-xs font-bold text-white" :class="group.badgeBg">
                        {{ group.items.length }}
                    </span>
                    <div class="flex-1 border-b" :class="group.borderClass"></div>
                </div>

                <!-- Карточки -->
                <div class="space-y-2">
                    <div v-for="item in group.items" :key="item.id"
                        class="flex items-center gap-3 rounded-xl bg-white p-3 shadow-sm active:bg-gray-50 dark:bg-gray-900 dark:active:bg-gray-800"
                    >
                        <!-- Статус-индикатор -->
                        <div class="h-full w-1 self-stretch rounded-full" :class="group.barClass"></div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-gray-900 truncate dark:text-white">{{ item.name }}</p>
                                <span class="ml-2 shrink-0 text-xs text-gray-400 dark:text-gray-500">{{ item.lastService }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5 dark:text-gray-500">{{ item.address }}</p>

                            <!-- Детали -->
                            <div class="mt-2 flex items-center gap-3">
                                <!-- Вода -->
                                <div class="flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                    </svg>
                                    <div class="h-1.5 w-12 rounded-full bg-gray-200 dark:bg-gray-800">
                                        <div class="h-1.5 rounded-full" :class="item.waterBarClass" :style="{ width: item.waterPercent + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-medium" :class="item.waterTextClass">{{ item.water }}</span>
                                </div>

                                <!-- Оператор -->
                                <div class="flex items-center gap-1">
                                    <div class="h-4 w-4 rounded-full flex items-center justify-center text-xs font-bold text-white" :class="item.operatorBg">
                                        {{ item.operatorInitials }}
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ item.operator }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Chevron -->
                        <svg class="h-4 w-4 shrink-0 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const allPoints = [
    // Срочные (красные)
    { id: 3, name: 'Больница №3', address: 'ул. Советская, 78', lastService: '5 дней', water: '0.3', waterPercent: 15, waterBarClass: 'bg-red-500', waterTextClass: 'text-red-500', operator: 'Иван', operatorInitials: 'ИП', operatorBg: 'bg-blue-500', status: 'red' },
    { id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1', lastService: '4 дня', water: '0.6', waterPercent: 30, waterBarClass: 'bg-red-400', waterTextClass: 'text-red-500', operator: 'Пётр', operatorInitials: 'ПС', operatorBg: 'bg-purple-500', status: 'red' },
    // Внимание (жёлтые)
    { id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5', lastService: '3 дня', water: '1.0', waterPercent: 50, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600', operator: 'Иван', operatorInitials: 'ИП', operatorBg: 'bg-blue-500', status: 'yellow' },
    { id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12', lastService: '2 дня', water: '1.4', waterPercent: 60, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600', operator: 'Пётр', operatorInitials: 'ПС', operatorBg: 'bg-purple-500', status: 'yellow' },
    { id: 7, name: 'Бизнес-центр Высота', address: 'ул. Деловая, 10', lastService: '2 дня', water: '1.2', waterPercent: 55, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600', operator: 'Иван', operatorInitials: 'ИП', operatorBg: 'bg-blue-500', status: 'yellow' },
    // В норме (зелёные)
    { id: 5, name: 'Университет, корпус Б', address: 'ул. Академическая, 22', lastService: 'Сегодня', water: '2.0', waterPercent: 100, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500', operator: 'Иван', operatorInitials: 'ИП', operatorBg: 'bg-blue-500', status: 'green' },
    { id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45', lastService: 'Вчера', water: '2.0', waterPercent: 100, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500', operator: 'Пётр', operatorInitials: 'ПС', operatorBg: 'bg-purple-500', status: 'green' },
    { id: 8, name: 'Фитнес-клуб Энергия', address: 'ул. Спортивная, 3', lastService: 'Вчера', water: '1.8', waterPercent: 90, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500', operator: 'Пётр', operatorInitials: 'ПС', operatorBg: 'bg-purple-500', status: 'green' },
];

const redCount = computed(() => allPoints.filter(p => p.status === 'red').length);
const yellowCount = computed(() => allPoints.filter(p => p.status === 'yellow').length);
const greenCount = computed(() => allPoints.filter(p => p.status === 'green').length);
const total = computed(() => allPoints.length);

const redPercent = computed(() => (redCount.value / total.value) * 100);
const yellowPercent = computed(() => (yellowCount.value / total.value) * 100);
const greenPercent = computed(() => (greenCount.value / total.value) * 100);

const groups = computed(() => [
    {
        title: 'Срочно',
        dotClass: 'bg-red-500',
        titleClass: 'text-red-500',
        badgeBg: 'bg-red-500',
        barClass: 'bg-red-500',
        borderClass: 'border-red-200 dark:border-red-900/40',
        items: allPoints.filter(p => p.status === 'red'),
    },
    {
        title: 'Требует внимания',
        dotClass: 'bg-yellow-400',
        titleClass: 'text-yellow-600 dark:text-yellow-500',
        badgeBg: 'bg-yellow-400',
        barClass: 'bg-yellow-400',
        borderClass: 'border-yellow-200 dark:border-yellow-900/40',
        items: allPoints.filter(p => p.status === 'yellow'),
    },
    {
        title: 'В норме',
        dotClass: 'bg-green-500',
        titleClass: 'text-green-600 dark:text-green-500',
        badgeBg: 'bg-green-500',
        barClass: 'bg-green-500',
        borderClass: 'border-green-200 dark:border-green-900/40',
        items: allPoints.filter(p => p.status === 'green'),
    },
]);
</script>
