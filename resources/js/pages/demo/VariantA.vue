<template>
    <!-- Вариант A: Bottom Tab Navigation (Material / Android style) -->
    <div class="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-900">
        <!-- Верхний бар — компактный -->
        <header class="bg-white shadow dark:bg-gray-800">
            <div class="flex h-12 items-center justify-between px-4">
                <span class="text-lg font-bold text-gray-900 dark:text-white">Terminals</span>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">онлайн</span>
                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">МБ</div>
                </div>
            </div>
        </header>

        <!-- Контент -->
        <main class="flex-1 overflow-y-auto px-4 pb-20 pt-4">
            <h2 class="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Точки обслуживания</h2>

            <div class="space-y-3">
                <div v-for="point in points" :key="point.id"
                    class="rounded-xl bg-white p-4 shadow-sm active:bg-gray-50 dark:bg-gray-800 dark:active:bg-gray-750"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ point.name }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ point.address }}</p>
                        </div>
                        <span :class="point.statusClass" class="ml-3 mt-0.5 h-3 w-3 shrink-0 rounded-full"></span>
                    </div>
                    <div class="mt-3 flex items-center gap-4 text-xs text-gray-400 dark:text-gray-500">
                        <span>{{ point.lastService }}</span>
                        <span>{{ point.water }}</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Нижний таб-бар -->
        <nav class="fixed bottom-0 left-0 right-0 border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-md justify-around py-2">
                <button v-for="tab in tabs" :key="tab.label"
                    @click="activeTab = tab.label"
                    :class="activeTab === tab.label ? 'text-blue-500' : 'text-gray-400'"
                    class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs transition-colors"
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

const activeTab = ref('Точки');

const tabs = [
    { label: 'Точки', icon: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z' },
    { label: 'Терминалы', icon: 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25' },
    { label: 'Команда', icon: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z' },
    { label: 'Ещё', icon: 'M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z' },
];

const points = [
    { id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45', lastService: 'Вчера, 14:30', water: '2 полных', statusClass: 'bg-green-400' },
    { id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12', lastService: '2 дня назад', water: '1+0.4', statusClass: 'bg-green-400' },
    { id: 3, name: 'Больница №3', address: 'ул. Советская, 78', lastService: '5 дней назад', water: '0.3', statusClass: 'bg-red-400' },
    { id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5', lastService: '3 дня назад', water: '1', statusClass: 'bg-yellow-400' },
    { id: 5, name: 'Университет, корпус Б', address: 'ул. Академическая, 22', lastService: 'Сегодня, 09:15', water: '2 полных', statusClass: 'bg-green-400' },
    { id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1', lastService: '4 дня назад', water: '0.6', statusClass: 'bg-yellow-400' },
];
</script>
