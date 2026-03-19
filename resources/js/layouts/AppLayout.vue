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
                    >Терминалы</router-link>
                    <router-link
                        v-if="authStore.isAdmin"
                        to="/admin/users"
                        @click="showMenu = false"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-600 active:bg-gray-200 dark:bg-gray-700 dark:text-gray-300"
                    >Пользователи</router-link>
                    <button
                        @click="logout"
                        class="rounded-lg bg-red-50 px-3 py-1.5 text-xs text-red-500 active:bg-red-100 dark:bg-red-900/20 dark:text-red-400"
                    >Выход</button>
                </div>
            </div>
        </header>

        <!-- Контент -->
        <main class="flex-1">
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
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
