<template>
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Хедер -->
        <header class="bg-white shadow dark:bg-gray-800">
            <div class="flex h-12 items-center justify-between px-4">
                <router-link to="/" class="text-lg font-bold text-gray-900 dark:text-white">Terminals</router-link>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500 dark:text-gray-400">онлайн</span>
                    <button @click="showMenu = !showMenu" class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-medium">
                        {{ initials }}
                    </button>
                </div>
            </div>
            <!-- Выпадающее меню -->
            <div v-if="showMenu" class="border-t border-gray-100 bg-white px-4 py-2 dark:border-gray-700 dark:bg-gray-800">
                <p class="mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ authStore.user?.name }}</p>
                <div class="flex flex-wrap gap-2">
                    <router-link
                        v-if="authStore.isAdmin"
                        to="/admin/terminals"
                        @click="showMenu = false"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-600 active:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
                    >Админка</router-link>
                    <button
                        @click="logout"
                        class="rounded-lg bg-red-50 px-3 py-1.5 text-xs text-red-500 active:bg-red-100 dark:bg-red-900/20 dark:text-red-400"
                    >Выход</button>
                </div>
            </div>
        </header>

        <!-- Контент -->
        <main class="flex-1 overflow-y-auto pb-16">
            <router-view />
        </main>

        <!-- Футер -->
        <nav class="fixed bottom-0 left-0 right-0 border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto flex max-w-md justify-around py-2">
                <router-link to="/"
                    :class="route.name === 'home' ? 'text-blue-500' : 'text-gray-400'"
                    class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    Терминалы
                </router-link>
                <router-link to="/sales"
                    :class="route.name === 'sales' ? 'text-blue-500' : 'text-gray-400'"
                    class="flex flex-col items-center gap-0.5 px-3 py-1 text-xs transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    Продажи
                </router-link>
            </div>
        </nav>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const showMenu = ref(false);

const initials = computed(() => {
    const name = authStore.user?.name || '';
    return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
});

async function logout() {
    showMenu.value = false;
    await authStore.logout();
    router.replace('/login');
}
</script>
