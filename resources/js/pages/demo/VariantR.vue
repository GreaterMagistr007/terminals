<template>
    <!-- Вариант R: Operator Home + Quick Actions — домашний экран оператора -->
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950 pb-20">
        <!-- Шапка -->
        <header class="bg-white px-4 pt-4 pb-3 dark:bg-gray-900">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-400 dark:text-gray-500">{{ formattedDate }}</p>
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white">Привет, Алексей</h1>
                </div>
                <button class="relative flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 active:bg-gray-300 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 transition-colors">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <span class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-900"></span>
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto px-4 pt-4">
            <!-- Алерт-баннер -->
            <div
                v-if="showAlert"
                class="mb-4 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/30"
            >
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                    <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-red-800 dark:text-red-300">Больница №3</p>
                    <p class="text-xs text-red-600 dark:text-red-400">Мало воды, 5 дней без обслуживания</p>
                </div>
                <button
                    class="shrink-0 rounded-full p-1 text-red-400 hover:bg-red-100 active:bg-red-200 dark:hover:bg-red-900/30 transition-colors"
                    @click="showAlert = false"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Мои задачи -->
            <div class="mb-6">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Мои задачи</h2>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">{{ tasks.length }}</span>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="task in tasks"
                        :key="task.id"
                        class="flex items-center gap-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900 transition-all active:scale-[0.99]"
                        :class="'border-l-4 ' + task.borderClass"
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="task.iconBgClass">
                            <!-- Иконки задач -->
                            <svg v-if="task.type === 'load'" class="h-5 w-5" :class="task.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <svg v-else-if="task.type === 'service'" class="h-5 w-5" :class="task.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17l-5.384-3.19A1.5 1.5 0 015.25 10.7V6.75a.75.75 0 01.75-.75h12a.75.75 0 01.75.75v3.95a1.5 1.5 0 01-.786 1.28l-5.384 3.19a1.5 1.5 0 01-1.58 0z" />
                            </svg>
                            <svg v-else-if="task.type === 'collect'" class="h-5 w-5" :class="task.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ task.title }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ task.subtitle }}</p>
                        </div>
                        <button class="shrink-0 rounded-lg bg-blue-500 px-3 py-1.5 text-xs font-semibold text-white active:bg-blue-600 shadow-sm shadow-blue-500/20 transition-colors">
                            Выполнить
                        </button>
                    </div>
                </div>
            </div>

            <!-- Сегодня обслужено -->
            <div class="mb-6">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Сегодня обслужено</h2>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ completedTasks.length }} из 5</span>
                </div>
                <div class="flex gap-3 overflow-x-auto -mx-4 px-4 pb-2">
                    <div
                        v-for="completed in completedTasks"
                        :key="completed.id"
                        class="flex shrink-0 items-center gap-2.5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 dark:border-green-900/50 dark:bg-green-950/30"
                    >
                        <svg class="h-5 w-5 shrink-0 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ completed.name }}</p>
                            <p class="text-xs text-green-600 dark:text-green-500">{{ completed.time }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Быстрые действия -->
            <div class="mb-6">
                <h2 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Быстрые действия</h2>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="action in quickActions"
                        :key="action.label"
                        class="flex flex-col items-center gap-2.5 rounded-2xl bg-white p-5 shadow-sm active:bg-gray-50 active:scale-[0.97] dark:bg-gray-900 dark:active:bg-gray-800 transition-all"
                    >
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="action.bgClass">
                            <!-- Обслужить -->
                            <svg v-if="action.icon === 'wrench'" class="h-6 w-6" :class="action.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
                            </svg>
                            <!-- Инкассация -->
                            <svg v-else-if="action.icon === 'money'" class="h-6 w-6" :class="action.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            <!-- Загрузить -->
                            <svg v-else-if="action.icon === 'box'" class="h-6 w-6" :class="action.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <!-- Отчёт -->
                            <svg v-else-if="action.icon === 'chart'" class="h-6 w-6" :class="action.iconClass" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ action.label }}</span>
                    </button>
                </div>
            </div>
        </main>

        <!-- Нижний таббар -->
        <nav class="fixed inset-x-0 bottom-0 flex border-t border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <button
                v-for="tab in bottomTabs"
                :key="tab.id"
                class="flex flex-1 flex-col items-center gap-1 py-2.5 transition-colors"
                :class="activeBottomTab === tab.id
                    ? 'text-blue-600 dark:text-blue-400'
                    : 'text-gray-400 dark:text-gray-500'"
                @click="activeBottomTab = tab.id"
            >
                <!-- Главная -->
                <svg v-if="tab.icon === 'home'" class="h-5 w-5" :fill="activeBottomTab === tab.id ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <!-- Точки -->
                <svg v-else-if="tab.icon === 'points'" class="h-5 w-5" :fill="activeBottomTab === tab.id ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                </svg>
                <!-- История -->
                <svg v-else-if="tab.icon === 'history'" class="h-5 w-5" :fill="activeBottomTab === tab.id ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <!-- Профиль -->
                <svg v-else-if="tab.icon === 'profile'" class="h-5 w-5" :fill="activeBottomTab === tab.id ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
                <span class="text-xs font-medium">{{ tab.label }}</span>
            </button>
        </nav>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const showAlert = ref(true)

const formattedDate = computed(() => {
    const days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота']
    const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря']
    const now = new Date()
    return `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]}`
})

const tasks = [
    {
        id: 1,
        title: 'Загрузить Больница №3',
        subtitle: 'Кофе, молоко, шоколад \u2014 критически мало',
        type: 'load',
        borderClass: 'border-red-500',
        iconBgClass: 'bg-red-100 dark:bg-red-900/30',
        iconClass: 'text-red-600 dark:text-red-400',
    },
    {
        id: 2,
        title: 'Обслужить Автосалон Восток',
        subtitle: '4 дня без обслуживания',
        type: 'service',
        borderClass: 'border-yellow-400',
        iconBgClass: 'bg-yellow-100 dark:bg-yellow-900/30',
        iconClass: 'text-yellow-600 dark:text-yellow-400',
    },
    {
        id: 3,
        title: 'Инкассация ТЦ Мега',
        subtitle: 'Накоплено ~12 400 \u20BD',
        type: 'collect',
        borderClass: 'border-blue-500',
        iconBgClass: 'bg-blue-100 dark:bg-blue-900/30',
        iconClass: 'text-blue-600 dark:text-blue-400',
    },
]

const completedTasks = [
    { id: 1, name: 'Университет', time: '09:15' },
    { id: 2, name: 'Офис Сбербанк', time: '11:30' },
]

const quickActions = [
    { label: 'Обслужить', icon: 'wrench', bgClass: 'bg-blue-100 dark:bg-blue-900/30', iconClass: 'text-blue-600 dark:text-blue-400' },
    { label: 'Инкассация', icon: 'money', bgClass: 'bg-green-100 dark:bg-green-900/30', iconClass: 'text-green-600 dark:text-green-400' },
    { label: 'Загрузить', icon: 'box', bgClass: 'bg-orange-100 dark:bg-orange-900/30', iconClass: 'text-orange-600 dark:text-orange-400' },
    { label: 'Отчёт', icon: 'chart', bgClass: 'bg-purple-100 dark:bg-purple-900/30', iconClass: 'text-purple-600 dark:text-purple-400' },
]

const bottomTabs = [
    { id: 'home', label: 'Главная', icon: 'home' },
    { id: 'points', label: 'Точки', icon: 'points' },
    { id: 'history', label: 'История', icon: 'history' },
    { id: 'profile', label: 'Профиль', icon: 'profile' },
]

const activeBottomTab = ref('home')
</script>
