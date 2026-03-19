<template>
    <!-- Вариант F: Minimal/Notion style — ультра-чистый, монохром + один акцент -->
    <div class="flex min-h-screen flex-col bg-white dark:bg-neutral-950">
        <!-- Шапка -->
        <header class="px-5 pt-12 pb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-light tracking-tight text-neutral-900 dark:text-neutral-100">Точки обслуживания</h1>
                    <p class="mt-1 text-sm text-neutral-400">{{ points.length }} объектов</p>
                </div>
                <div class="flex items-center gap-1">
                    <!-- Переключатель вид: список/сетка -->
                    <button @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : 'text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300'"
                        class="rounded-md p-2 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <button @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-neutral-100 text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : 'text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-300'"
                        class="rounded-md p-2 transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Поиск -->
            <div class="mt-5 flex items-center gap-2 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-800">
                <svg class="h-4 w-4 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                </svg>
                <span class="text-sm text-neutral-300 dark:text-neutral-600">Поиск по точкам...</span>
            </div>
        </header>

        <!-- Режим «Список» -->
        <main v-if="viewMode === 'list'" class="flex-1 px-5">
            <div v-for="(point, index) in points" :key="point.id">
                <div class="flex items-center gap-4 py-4 active:opacity-60">
                    <!-- Статус-точка -->
                    <span class="h-2 w-2 shrink-0 rounded-full" :class="point.dotClass"></span>

                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ point.name }}</p>
                        <p class="text-xs text-neutral-400 mt-0.5">{{ point.address }}</p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-xs text-neutral-400">{{ point.lastService }}</p>
                        <p class="text-xs mt-0.5" :class="point.waterColor">{{ point.water }}</p>
                    </div>

                    <svg class="h-4 w-4 shrink-0 text-neutral-300 dark:text-neutral-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
                <div v-if="index < points.length - 1" class="ml-6 border-b border-neutral-100 dark:border-neutral-900"></div>
            </div>
        </main>

        <!-- Режим «Сетка» -->
        <main v-else class="flex-1 px-5 pb-8">
            <div class="grid grid-cols-2 gap-3">
                <div v-for="point in points" :key="point.id"
                    class="rounded-lg border border-neutral-100 p-4 active:bg-neutral-50 dark:border-neutral-800 dark:active:bg-neutral-900"
                >
                    <div class="flex items-center gap-2 mb-3">
                        <span class="h-2 w-2 rounded-full" :class="point.dotClass"></span>
                        <span class="text-xs text-neutral-400">{{ point.lastService }}</span>
                    </div>
                    <p class="text-sm font-medium text-neutral-900 dark:text-neutral-100 leading-tight">{{ point.name }}</p>
                    <p class="mt-1 text-xs text-neutral-400">{{ point.address }}</p>
                    <div class="mt-3 flex items-center justify-between">
                        <span class="text-xs" :class="point.waterColor">{{ point.water }}</span>
                        <span v-if="point.urgent"
                            class="rounded px-1.5 py-0.5 text-xs font-medium bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
                        >!</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Минималистичная кнопка внизу -->
        <div class="sticky bottom-0 px-5 pb-6 pt-2 bg-gradient-to-t from-white via-white dark:from-neutral-950 dark:via-neutral-950">
            <button class="w-full rounded-lg bg-neutral-900 py-3 text-sm font-medium text-white transition-colors active:bg-neutral-700 dark:bg-neutral-100 dark:text-neutral-900 dark:active:bg-neutral-300">
                + Новое обслуживание
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const viewMode = ref('list');

const points = [
    { id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45', lastService: 'Вчера', water: '2 полных', waterColor: 'text-neutral-400', dotClass: 'bg-green-500', urgent: false },
    { id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12', lastService: '2 дня', water: '1.4 бут.', waterColor: 'text-neutral-400', dotClass: 'bg-green-500', urgent: false },
    { id: 3, name: 'Больница №3', address: 'ул. Советская, 78', lastService: '5 дней', water: '0.3 бут.', waterColor: 'text-orange-500', dotClass: 'bg-red-500', urgent: true },
    { id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5', lastService: '3 дня', water: '1 бут.', waterColor: 'text-neutral-400', dotClass: 'bg-yellow-500', urgent: false },
    { id: 5, name: 'Университет, корп. Б', address: 'ул. Академическая, 22', lastService: 'Сегодня', water: '2 полных', waterColor: 'text-neutral-400', dotClass: 'bg-green-500', urgent: false },
    { id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1', lastService: '4 дня', water: '0.6 бут.', waterColor: 'text-orange-500', dotClass: 'bg-orange-500', urgent: true },
    { id: 7, name: 'Бизнес-центр Высота', address: 'ул. Деловая, 10', lastService: '2 дня', water: '1.2 бут.', waterColor: 'text-neutral-400', dotClass: 'bg-yellow-500', urgent: false },
    { id: 8, name: 'Фитнес-клуб Энергия', address: 'ул. Спортивная, 3', lastService: 'Вчера', water: '1.8 бут.', waterColor: 'text-neutral-400', dotClass: 'bg-green-500', urgent: false },
];
</script>
