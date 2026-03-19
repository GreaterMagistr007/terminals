<template>
    <!-- Вариант O: Machine Dashboard Card — детальная карточка аппарата/точки -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="bg-white px-4 py-3 shadow-sm dark:bg-gray-900">
            <div class="flex items-center gap-3">
                <button class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-bold text-gray-900 truncate dark:text-white">{{ machine.name }}</p>
                        <span
                            class="shrink-0 flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                            :class="machine.online
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full" :class="machine.online ? 'bg-green-500' : 'bg-red-500'"></span>
                            {{ machine.online ? 'Онлайн' : 'Офлайн' }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 truncate dark:text-gray-500">{{ machine.address }}</p>
                </div>
                <button class="flex h-9 w-9 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                </button>
            </div>
        </header>

        <!-- Сводные карточки -->
        <div class="flex gap-3 overflow-x-auto px-4 py-4 -mx-0">
            <div class="flex shrink-0 flex-col rounded-2xl bg-white p-3 shadow-sm dark:bg-gray-900 min-w-[130px]">
                <div class="flex items-center gap-1.5 mb-1">
                    <svg class="h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                    </svg>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Продажи</span>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">47</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">4 230 \u20BD</p>
            </div>
            <div class="flex shrink-0 flex-col rounded-2xl bg-white p-3 shadow-sm dark:bg-gray-900 min-w-[130px]">
                <div class="flex items-center gap-1.5 mb-1">
                    <svg class="h-4 w-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384-3.19A1.5 1.5 0 015.25 10.7V6.75m.75-3h10.5a2.25 2.25 0 012.25 2.25v10.5a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6A2.25 2.25 0 016 3.75z" />
                    </svg>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Без обслуж.</span>
                </div>
                <p class="text-lg font-bold" :class="machine.daysSinceService > 5 ? 'text-red-500' : 'text-gray-900 dark:text-white'">{{ machine.daysSinceService }} дн.</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">{{ machine.lastServiceDate }}</p>
            </div>
            <div class="flex shrink-0 flex-col rounded-2xl bg-white p-3 shadow-sm dark:bg-gray-900 min-w-[130px]">
                <div class="flex items-center gap-1.5 mb-1">
                    <svg class="h-4 w-4 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Вода</span>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">1 + 0.4</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">осн. + зап.</p>
            </div>
        </div>

        <!-- Табы -->
        <div class="flex border-b border-gray-200 bg-white px-1 dark:border-gray-800 dark:bg-gray-900">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                class="flex-1 py-3 text-center text-sm font-medium transition-colors"
                :class="activeTab === tab.id
                    ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'text-gray-400 dark:text-gray-500'"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Контент табов -->
        <main class="flex-1 overflow-y-auto px-4 pt-4 pb-24">
            <!-- Обзор -->
            <div v-if="activeTab === 'overview'">
                <!-- Последнее обслуживание -->
                <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Последнее обслуживание</p>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384-3.19A1.5 1.5 0 015.25 10.7V6.75" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">Алексей Николаев</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">16 марта 2026, 14:30</p>
                        </div>
                    </div>
                </div>

                <!-- Последняя инкассация -->
                <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Последняя инкассация</p>
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">8 540 \u20BD</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">14 марта 2026, 10:00</p>
                        </div>
                    </div>
                </div>

                <!-- Статус воды -->
                <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Вода</p>
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Основная</span>
                                <span class="text-xs font-bold text-blue-600 dark:text-blue-400">100%</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full bg-blue-500 transition-all" style="width: 100%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Запасная</span>
                                <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400">40%</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full bg-cyan-500 transition-all" style="width: 40%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Статус ингредиентов -->
                <div class="mb-4 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Ингредиенты</p>
                    <div class="space-y-3">
                        <div v-for="ing in machineIngredients" :key="ing.name">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ ing.name }}</span>
                                <span
                                    class="text-xs font-bold"
                                    :class="ing.percent < 20 ? 'text-red-500' : ing.percent < 50 ? 'text-yellow-500' : 'text-green-500'"
                                >{{ ing.percent }}%</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="ing.percent < 20 ? 'bg-red-500' : ing.percent < 50 ? 'bg-yellow-500' : 'bg-green-500'"
                                    :style="{ width: ing.percent + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Остатки -->
            <div v-else-if="activeTab === 'stock'">
                <p class="text-center text-sm text-gray-400 dark:text-gray-500 py-12">Детальные остатки ингредиентов</p>
            </div>

            <!-- Продажи -->
            <div v-else-if="activeTab === 'sales'">
                <p class="text-center text-sm text-gray-400 dark:text-gray-500 py-12">Статистика продаж</p>
            </div>

            <!-- История -->
            <div v-else-if="activeTab === 'history'">
                <p class="text-center text-sm text-gray-400 dark:text-gray-500 py-12">История обслуживания</p>
            </div>
        </main>

        <!-- Кнопка обслужить -->
        <div class="fixed inset-x-0 bottom-0 border-t border-gray-200 bg-white px-4 pb-6 pt-4 dark:border-gray-800 dark:bg-gray-900">
            <button class="w-full rounded-xl bg-blue-500 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-500/25 active:bg-blue-600 active:scale-[0.98] transition-all">
                <span class="flex items-center justify-center gap-2">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384-3.19A1.5 1.5 0 015.25 10.7V6.75a.75.75 0 01.75-.75h12a.75.75 0 01.75.75v3.95a1.5 1.5 0 01-.786 1.28l-5.384 3.19a1.5 1.5 0 01-1.58 0z" />
                    </svg>
                    Обслужить
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const machine = {
    name: 'Больница №3',
    address: 'ул. Ленина, 45, корпус 2',
    online: true,
    daysSinceService: 3,
    lastServiceDate: '16.03.2026',
}

const tabs = [
    { id: 'overview', label: 'Обзор' },
    { id: 'stock', label: 'Остатки' },
    { id: 'sales', label: 'Продажи' },
    { id: 'history', label: 'История' },
]

const activeTab = ref('overview')

const machineIngredients = [
    { name: 'Кофе', percent: 73 },
    { name: 'Молоко', percent: 45 },
    { name: 'Сахар', percent: 89 },
    { name: 'Шоколад', percent: 12 },
]
</script>
