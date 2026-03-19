<template>
    <!-- Вариант K: Expandable accordion — раскрывающийся список -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 shadow-sm dark:bg-gray-900">
            <div class="flex h-14 items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">Точки обслуживания</h1>
                </div>
                <div class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <span class="flex h-2 w-2 rounded-full bg-green-400"></span>
                    {{ okCount }} ок
                    <span class="ml-2 flex h-2 w-2 rounded-full bg-red-400"></span>
                    {{ urgentCount }} сроч.
                </div>
            </div>
        </header>

        <!-- Список -->
        <main class="flex-1 overflow-y-auto px-4 py-4">
            <div class="space-y-2">
                <div v-for="point in points" :key="point.id"
                    class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-900"
                >
                    <!-- Свёрнутая строка -->
                    <button @click="toggle(point.id)"
                        class="flex w-full items-center gap-3 px-4 py-3 text-left active:bg-gray-50 dark:active:bg-gray-800"
                    >
                        <!-- Индикатор статуса -->
                        <div class="h-10 w-1 shrink-0 rounded-full" :class="point.statusBar"></div>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ point.address }}</p>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ point.lastService }}</span>
                            <!-- Стрелка -->
                            <svg class="h-4 w-4 text-gray-300 transition-transform dark:text-gray-600"
                                :class="expandedId === point.id ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </button>

                    <!-- Раскрытый контент -->
                    <div v-if="expandedId === point.id"
                        class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800"
                    >
                        <!-- Детали -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xs text-gray-400 dark:text-gray-500">Вода</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-1.5 rounded-full" :class="point.waterBarClass" :style="{ width: point.waterPercent + '%' }"></div>
                                    </div>
                                    <span class="text-sm font-semibold" :class="point.waterTextClass">{{ point.water }}</span>
                                </div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                                <p class="text-xs text-gray-400 dark:text-gray-500">Последний визит</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ point.lastService }}</p>
                            </div>
                        </div>

                        <!-- Ингредиенты -->
                        <div class="mb-4">
                            <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Ингредиенты</p>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="ing in point.ingredients" :key="ing.name"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs"
                                    :class="ing.ok
                                        ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                                        : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="ing.ok ? 'bg-green-400' : 'bg-red-400'"></span>
                                    {{ ing.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Комментарий -->
                        <p v-if="point.comment" class="mb-4 rounded-lg bg-yellow-50 p-3 text-xs text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                            {{ point.comment }}
                        </p>

                        <!-- Кнопки действий -->
                        <div class="flex gap-2">
                            <button class="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-blue-500 py-2.5 text-sm font-medium text-white active:bg-blue-600">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384 3.183A2.045 2.045 0 013 16.456V5.544a2.045 2.045 0 013.036-1.897l5.384 3.183M11.42 15.17l5.384 3.183A2.045 2.045 0 0021 16.456V5.544a2.045 2.045 0 00-4.196-1.897L11.42 15.17z" />
                                </svg>
                                Обслужить
                            </button>
                            <button class="flex items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:active:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                История
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const expandedId = ref(null);

const toggle = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const points = [
    {
        id: 3, name: 'Больница №3', address: 'ул. Советская, 78',
        lastService: '5 дней назад', statusBar: 'bg-red-500',
        water: '0.3', waterPercent: 15, waterBarClass: 'bg-red-500', waterTextClass: 'text-red-500',
        ingredients: [
            { name: 'Молоко', ok: false },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: false },
            { name: 'Кофе', ok: true },
        ],
        comment: 'Высокий расход воды, аппарат работает на пределе. Нужна срочная доставка.',
    },
    {
        id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1',
        lastService: '4 дня назад', statusBar: 'bg-orange-500',
        water: '0.6', waterPercent: 30, waterBarClass: 'bg-orange-500', waterTextClass: 'text-orange-500',
        ingredients: [
            { name: 'Молоко', ok: false },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: true },
            { name: 'Кофе', ok: true },
        ],
        comment: 'Нужно привезти молоко.',
    },
    {
        id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5',
        lastService: '3 дня назад', statusBar: 'bg-yellow-400',
        water: '1.0', waterPercent: 50, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600',
        ingredients: [
            { name: 'Молоко', ok: true },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: false },
            { name: 'Кофе', ok: true },
        ],
        comment: null,
    },
    {
        id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12',
        lastService: '2 дня назад', statusBar: 'bg-yellow-400',
        water: '1.4', waterPercent: 60, waterBarClass: 'bg-blue-400', waterTextClass: 'text-blue-500',
        ingredients: [
            { name: 'Молоко', ok: true },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: true },
            { name: 'Кофе', ok: true },
        ],
        comment: null,
    },
    {
        id: 5, name: 'Университет, корпус Б', address: 'ул. Академическая, 22',
        lastService: 'Сегодня', statusBar: 'bg-green-500',
        water: '2.0', waterPercent: 100, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500',
        ingredients: [
            { name: 'Молоко', ok: true },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: true },
            { name: 'Кофе', ok: true },
        ],
        comment: null,
    },
    {
        id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45',
        lastService: 'Вчера', statusBar: 'bg-green-500',
        water: '2.0', waterPercent: 100, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500',
        ingredients: [
            { name: 'Молоко', ok: true },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: true },
            { name: 'Кофе', ok: true },
        ],
        comment: null,
    },
    {
        id: 7, name: 'Бизнес-центр Высота', address: 'ул. Деловая, 10',
        lastService: '2 дня назад', statusBar: 'bg-yellow-400',
        water: '1.2', waterPercent: 55, waterBarClass: 'bg-yellow-400', waterTextClass: 'text-yellow-600',
        ingredients: [
            { name: 'Молоко', ok: true },
            { name: 'Сахар', ok: false },
            { name: 'Шоколад', ok: true },
            { name: 'Кофе', ok: true },
        ],
        comment: 'Заканчивается сахар, привезти в следующий визит.',
    },
    {
        id: 8, name: 'Фитнес-клуб Энергия', address: 'ул. Спортивная, 3',
        lastService: 'Вчера', statusBar: 'bg-green-500',
        water: '1.8', waterPercent: 90, waterBarClass: 'bg-green-500', waterTextClass: 'text-green-500',
        ingredients: [
            { name: 'Молоко', ok: true },
            { name: 'Сахар', ok: true },
            { name: 'Шоколад', ok: true },
            { name: 'Кофе', ok: true },
        ],
        comment: null,
    },
];

const urgentCount = computed(() => points.filter(p => p.waterPercent < 40).length);
const okCount = computed(() => points.filter(p => p.waterPercent >= 40).length);
</script>
