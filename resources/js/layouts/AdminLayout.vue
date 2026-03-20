<template>
    <div class="flex min-h-screen bg-gray-100 dark:bg-gray-950">
        <!-- Оверлей для мобильного меню -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-black/50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>

        <!-- Сайдбар -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-white shadow-lg transition-transform duration-200 dark:bg-gray-900 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Логотип -->
            <div class="flex h-14 items-center gap-2 border-b border-gray-200 px-4 dark:border-gray-800">
                <router-link to="/admin/terminals" class="text-lg font-bold text-gray-900 dark:text-white" @click="sidebarOpen = false">
                    Terminals
                </router-link>
                <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">admin</span>
            </div>

            <!-- Навигация -->
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <router-link
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="isActive(item.routeName)
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                        : 'text-gray-600 hover:bg-gray-50 active:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 dark:active:bg-gray-700'"
                    @click="sidebarOpen = false"
                >
                    <span v-html="item.icon" class="h-5 w-5 shrink-0"></span>
                    {{ item.label }}
                </router-link>

            </nav>

            <!-- Пользователь -->
            <div class="border-t border-gray-200 px-3 py-3 dark:border-gray-800">
                <div class="flex items-center gap-3 rounded-lg px-3 py-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-500 text-sm font-medium text-white">
                        {{ initials }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ authStore.user?.name }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Администратор</p>
                    </div>
                    <button
                        @click="logout"
                        class="shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-red-500 active:bg-gray-200 dark:hover:bg-gray-800 dark:hover:text-red-400 transition-colors"
                        title="Выход"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Контент -->
        <div class="flex flex-1 flex-col lg:pl-64">
            <!-- Мобильный хедер -->
            <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-gray-200 bg-white px-4 dark:border-gray-800 dark:bg-gray-900 lg:hidden">
                <button
                    @click="sidebarOpen = true"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ currentPageTitle }}</span>
            </header>

            <!-- Контент страницы -->
            <main class="flex-1">
                <router-view />
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const sidebarOpen = ref(false);

const navItems = [
    {
        to: '/admin/points',
        routeName: 'admin-points',
        label: 'Точки',
        icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>',
    },
    {
        to: '/admin/terminals',
        routeName: 'admin-terminals',
        label: 'Терминалы',
        icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" /></svg>',
    },
    {
        to: '/admin/users',
        routeName: 'admin-users',
        label: 'Пользователи',
        icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
    },
];

const currentPageTitle = computed(() => {
    // Вложенные маршруты привязываем к родительскому пункту меню
    if (route.name === 'admin-point-settings') {
        return 'Настройки точки';
    }
    const item = navItems.find(i => i.routeName === route.name);
    return item?.label || 'Администрирование';
});

const initials = computed(() => {
    const name = authStore.user?.name || '';
    return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
});

function isActive(routeName) {
    // Подсвечивать «Точки» и для вложенного маршрута настроек
    if (routeName === 'admin-points' && route.name === 'admin-point-settings') {
        return true;
    }
    return route.name === routeName;
}

async function logout() {
    sidebarOpen.value = false;
    await authStore.logout();
    router.replace('/login');
}
</script>
