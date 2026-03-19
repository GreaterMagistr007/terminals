<template>
    <!-- Вариант G: Dark theme gaming/neon — тёмный фон, неоновые акценты, свечение -->
    <div class="flex min-h-screen flex-col" style="background-color: #0f172a;">
        <!-- Градиентная шапка -->
        <header class="relative overflow-hidden px-4 pb-5 pt-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0f172a 100%);">
            <!-- Декоративное свечение -->
            <div class="absolute -top-10 left-1/2 h-32 w-64 -translate-x-1/2 rounded-full opacity-20" style="background: radial-gradient(circle, #22d3ee 0%, transparent 70%);"></div>

            <div class="relative flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-white">Terminals</h1>
                    <p class="text-xs" style="color: #67e8f9;">Система обслуживания</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <svg class="h-5 w-5" style="color: #67e8f9;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full text-xs font-bold text-white" style="background-color: #f43f5e; box-shadow: 0 0 8px #f43f5e80;">2</span>
                    </div>
                    <div class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold" style="background: linear-gradient(135deg, #22d3ee, #06b6d4); color: #0f172a;">
                        АН
                    </div>
                </div>
            </div>

            <!-- Статистика -->
            <div class="relative mt-4 grid grid-cols-3 gap-2">
                <div v-for="stat in stats" :key="stat.label"
                    class="rounded-xl p-3 text-center"
                    :style="'background: ' + stat.bg + '; border: 1px solid ' + stat.border + ';'"
                >
                    <p class="text-xl font-bold" :style="'color: ' + stat.valueColor + '; text-shadow: 0 0 10px ' + stat.glow + ';'">{{ stat.value }}</p>
                    <p class="text-xs mt-0.5" style="color: #94a3b8;">{{ stat.label }}</p>
                </div>
            </div>
        </header>

        <!-- Список точек -->
        <main class="flex-1 px-4 pt-4 pb-20">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold uppercase tracking-wider" style="color: #64748b;">Точки</h2>
                <button class="text-xs font-medium" style="color: #22d3ee;">Все &rarr;</button>
            </div>

            <div class="space-y-2">
                <div v-for="point in points" :key="point.id"
                    class="rounded-xl p-3 active:opacity-80"
                    :style="'background: #1e293b; border: 1px solid ' + point.borderColor + ';'"
                >
                    <div class="flex items-start gap-3">
                        <!-- Иконка со свечением -->
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                            :style="'background: ' + point.iconBg + '; box-shadow: 0 0 12px ' + point.glow + ';'"
                        >
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-white truncate">{{ point.name }}</p>
                                <span class="ml-2 shrink-0 text-xs" style="color: #64748b;">{{ point.lastService }}</span>
                            </div>
                            <p class="text-xs mt-0.5" style="color: #64748b;">{{ point.address }}</p>

                            <!-- Водомер -->
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1 flex-1 rounded-full" style="background: #334155;">
                                    <div class="h-1 rounded-full transition-all"
                                        :style="'width: ' + point.waterPercent + '%; background: ' + point.waterBar + '; box-shadow: 0 0 6px ' + point.waterBar + '80;'"
                                    ></div>
                                </div>
                                <span class="text-xs font-mono shrink-0" :style="'color: ' + point.waterBar + ';'">{{ point.water }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Нижний таб-бар с неоновым свечением -->
        <nav class="fixed bottom-0 left-0 right-0" style="background: #0f172a; border-top: 1px solid #1e293b;">
            <div class="mx-auto flex max-w-md justify-around py-2">
                <button v-for="tab in tabs" :key="tab.label"
                    @click="activeTab = tab.label"
                    class="flex flex-col items-center gap-0.5 px-4 py-1 text-xs transition-all"
                    :style="activeTab === tab.label
                        ? 'color: #22d3ee; filter: drop-shadow(0 0 6px #22d3ee80);'
                        : 'color: #475569;'"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="tab.icon" />
                    </svg>
                    {{ tab.label }}
                    <!-- Неоновая точка под активной вкладкой -->
                    <span v-if="activeTab === tab.label"
                        class="h-1 w-4 rounded-full"
                        style="background: #22d3ee; box-shadow: 0 0 8px #22d3ee;"
                    ></span>
                </button>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const activeTab = ref('Точки');

const stats = [
    { value: '3', label: 'Сегодня', bg: '#22d3ee10', border: '#22d3ee30', valueColor: '#22d3ee', glow: '#22d3ee60' },
    { value: '2', label: 'Срочных', bg: '#f43f5e10', border: '#f43f5e30', valueColor: '#f43f5e', glow: '#f43f5e60' },
    { value: '18', label: 'Всего', bg: '#a78bfa10', border: '#a78bfa30', valueColor: '#a78bfa', glow: '#a78bfa60' },
];

const tabs = [
    { label: 'Точки', icon: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z' },
    { label: 'Задачи', icon: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { label: 'Отчёт', icon: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z' },
    { label: 'Ещё', icon: 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
];

const points = [
    { id: 3, name: 'Больница №3', address: 'ул. Советская, 78', lastService: '5 дн.', water: '0.3', waterPercent: 15, waterBar: '#f43f5e', iconBg: '#be123c', glow: '#f43f5e40', borderColor: '#f43f5e30' },
    { id: 6, name: 'Вокзал (зал ожидания)', address: 'Привокзальная пл., 1', lastService: '4 дн.', water: '0.6', waterPercent: 30, waterBar: '#f97316', iconBg: '#c2410c', glow: '#f9731640', borderColor: '#f9731630' },
    { id: 4, name: 'Автосалон Восток', address: 'ул. Промышленная, 5', lastService: '3 дн.', water: '1.0', waterPercent: 50, waterBar: '#eab308', iconBg: '#a16207', glow: '#eab30840', borderColor: '#eab30830' },
    { id: 2, name: 'Офис Сбербанк', address: 'пр. Мира, 12', lastService: '2 дн.', water: '1.4', waterPercent: 60, waterBar: '#22d3ee', iconBg: '#0e7490', glow: '#22d3ee40', borderColor: '#1e293b' },
    { id: 5, name: 'Университет, корпус Б', address: 'ул. Академическая, 22', lastService: 'Сег.', water: '2.0', waterPercent: 100, waterBar: '#22c55e', iconBg: '#15803d', glow: '#22c55e40', borderColor: '#1e293b' },
    { id: 1, name: 'ТЦ Мега', address: 'ул. Ленина, 45', lastService: 'Вчера', water: '2.0', waterPercent: 100, waterBar: '#22c55e', iconBg: '#15803d', glow: '#22c55e40', borderColor: '#1e293b' },
    { id: 7, name: 'Бизнес-центр Высота', address: 'ул. Деловая, 10', lastService: '2 дн.', water: '1.2', waterPercent: 55, waterBar: '#22d3ee', iconBg: '#0e7490', glow: '#22d3ee40', borderColor: '#1e293b' },
    { id: 8, name: 'Фитнес-клуб Энергия', address: 'ул. Спортивная, 3', lastService: 'Вчера', water: '1.8', waterPercent: 90, waterBar: '#22c55e', iconBg: '#15803d', glow: '#22c55e40', borderColor: '#1e293b' },
];
</script>
