<template>
    <!-- Вариант H: Map-first — карта сверху, выдвижной лист снизу -->
    <div class="relative flex min-h-screen flex-col bg-gray-100 dark:bg-gray-900">
        <!-- Область карты (плейсхолдер) -->
        <div class="relative flex-shrink-0 bg-gray-300 dark:bg-gray-800" :style="{ height: mapHeight + 'px' }">
            <!-- Имитация карты -->
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-lg font-light text-gray-400 dark:text-gray-600">Карта</span>
            </div>

            <!-- Точки на карте -->
            <div v-for="marker in markers" :key="marker.id"
                class="absolute flex flex-col items-center"
                :style="{ top: marker.y + '%', left: marker.x + '%' }"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white shadow-md dark:border-gray-700"
                    :class="marker.bgClass"
                >
                    <div class="h-2 w-2 rounded-full bg-white"></div>
                </div>
                <span class="mt-0.5 rounded bg-white/90 px-1 text-xs font-medium text-gray-700 shadow-sm dark:bg-gray-800/90 dark:text-gray-300">
                    {{ marker.label }}
                </span>
            </div>

            <!-- Кнопки управления картой -->
            <div class="absolute right-3 top-12 flex flex-col gap-2">
                <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-md dark:bg-gray-700">
                    <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                </button>
                <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-md dark:bg-gray-700">
                    <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                    </svg>
                </button>
                <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-white shadow-md dark:bg-gray-700">
                    <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </button>
            </div>

            <!-- Верхний бар поверх карты -->
            <div class="absolute left-0 right-0 top-0 flex items-center justify-between px-4 pt-3">
                <div class="rounded-xl bg-white/90 px-3 py-2 shadow-md backdrop-blur dark:bg-gray-800/90">
                    <span class="text-sm font-bold text-gray-900 dark:text-white">Terminals</span>
                </div>
                <div class="flex items-center gap-2">
                    <button class="flex h-9 w-9 items-center justify-center rounded-full bg-white/90 shadow-md backdrop-blur dark:bg-gray-800/90">
                        <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Выдвижной лист (sheet) -->
        <div class="relative -mt-3 flex-1 rounded-t-2xl bg-white shadow-[0_-4px_20px_rgba(0,0,0,0.1)] dark:bg-gray-900 dark:shadow-[0_-4px_20px_rgba(0,0,0,0.4)]">
            <!-- Ручка для перетаскивания -->
            <div class="flex justify-center py-3 cursor-grab" @click="toggleSheet">
                <div class="h-1 w-10 rounded-full bg-gray-300 dark:bg-gray-600"></div>
            </div>

            <!-- Быстрые фильтры -->
            <div class="flex gap-2 px-4 pb-3">
                <button v-for="filter in filters" :key="filter.label"
                    @click="activeFilter = filter.label"
                    :class="activeFilter === filter.label
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                    class="rounded-full px-3 py-1.5 text-xs font-medium transition-colors"
                >
                    {{ filter.label }}
                    <span v-if="filter.count" class="ml-1 opacity-70">{{ filter.count }}</span>
                </button>
            </div>

            <!-- Список точек -->
            <div class="overflow-y-auto px-4" :style="{ maxHeight: sheetExpanded ? 'calc(100vh - 180px)' : '360px' }">
                <div v-for="point in filteredPoints" :key="point.id"
                    class="flex items-center gap-3 border-b border-gray-50 py-3 last:border-b-0 active:bg-gray-50 dark:border-gray-800 dark:active:bg-gray-800"
                >
                    <!-- Статус-маркер -->
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full" :class="point.markerBg">
                        <svg class="h-5 w-5" :class="point.markerIcon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ point.name }}</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ point.address }}</span>
                        </div>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ point.lastService }}</p>
                        <p class="text-xs font-medium" :class="point.waterClass">{{ point.water }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const sheetExpanded = ref(false);
const activeFilter = ref('Все');
const mapHeight = computed(() => sheetExpanded.value ? 180 : 280);

const toggleSheet = () => {
    sheetExpanded.value = !sheetExpanded.value;
};

const filters = [
    { label: 'Все', count: 8 },
    { label: 'Срочные', count: 2 },
    { label: 'Рядом', count: 3 },
];

const markers = [
    { id: 1, label: 'ТЦ', x: 35, y: 30, bgClass: 'bg-green-500' },
    { id: 3, label: 'Б3', x: 65, y: 25, bgClass: 'bg-red-500' },
    { id: 5, label: 'УБ', x: 25, y: 55, bgClass: 'bg-green-500' },
    { id: 6, label: 'ВЗ', x: 70, y: 60, bgClass: 'bg-orange-500' },
    { id: 4, label: 'АВ', x: 50, y: 45, bgClass: 'bg-yellow-500' },
    { id: 2, label: 'ОС', x: 45, y: 70, bgClass: 'bg-green-500' },
    { id: 7, label: 'БВ', x: 20, y: 35, bgClass: 'bg-yellow-500' },
    { id: 8, label: 'ФЭ', x: 80, y: 45, bgClass: 'bg-green-500' },
];

const points = [
    { id: 3, name: 'Больница №3', address: 'ул. Советская, 78', lastService: '5 дн. назад', water: '0.3 бут.', waterClass: 'text-red-500', markerBg: 'bg-red-100 dark:bg-red-900/30', markerIcon: 'text-red-600 dark:text-red-400', status: 'urgent' },
    { id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1', lastService: '4 дн. назад', water: '0.6 бут.', waterClass: 'text-orange-500', markerBg: 'bg-orange-100 dark:bg-orange-900/30', markerIcon: 'text-orange-600 dark:text-orange-400', status: 'urgent' },
    { id: 5, name: 'Университет, корпус Б', address: 'ул. Академическая, 22', lastService: 'Сегодня', water: '2 полных', waterClass: 'text-green-500', markerBg: 'bg-green-100 dark:bg-green-900/30', markerIcon: 'text-green-600 dark:text-green-400', status: 'ok' },
    { id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45', lastService: 'Вчера', water: '2 полных', waterClass: 'text-green-500', markerBg: 'bg-green-100 dark:bg-green-900/30', markerIcon: 'text-green-600 dark:text-green-400', status: 'ok' },
    { id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12', lastService: '2 дня', water: '1.4 бут.', waterClass: 'text-yellow-500', markerBg: 'bg-yellow-100 dark:bg-yellow-900/30', markerIcon: 'text-yellow-600 dark:text-yellow-400', status: 'ok' },
    { id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5', lastService: '3 дня', water: '1 бут.', waterClass: 'text-yellow-500', markerBg: 'bg-yellow-100 dark:bg-yellow-900/30', markerIcon: 'text-yellow-600 dark:text-yellow-400', status: 'ok' },
    { id: 7, name: 'Бизнес-центр Высота', address: 'ул. Деловая, 10', lastService: '2 дня', water: '1.2 бут.', waterClass: 'text-yellow-500', markerBg: 'bg-yellow-100 dark:bg-yellow-900/30', markerIcon: 'text-yellow-600 dark:text-yellow-400', status: 'ok' },
    { id: 8, name: 'Фитнес-клуб Энергия', address: 'ул. Спортивная, 3', lastService: 'Вчера', water: '1.8 бут.', waterClass: 'text-green-500', markerBg: 'bg-green-100 dark:bg-green-900/30', markerIcon: 'text-green-600 dark:text-green-400', status: 'ok' },
];

const filteredPoints = computed(() => {
    if (activeFilter.value === 'Срочные') return points.filter(p => p.status === 'urgent');
    if (activeFilter.value === 'Рядом') return points.slice(0, 3);
    return points;
});
</script>
