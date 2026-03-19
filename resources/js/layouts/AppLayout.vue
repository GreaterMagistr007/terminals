<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Навигация -->
        <nav class="bg-white shadow dark:bg-gray-800">
            <div class="mx-auto max-w-4xl px-4">
                <div class="flex h-14 items-center justify-between">
                    <router-link to="/" class="text-lg font-bold text-gray-900 dark:text-white">
                        Terminals
                    </router-link>
                    <div class="flex items-center gap-4">
                        <router-link
                            v-if="authStore.isAdmin"
                            to="/admin/terminals"
                            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        >
                            Терминалы
                        </router-link>
                        <router-link
                            v-if="authStore.isAdmin"
                            to="/admin/users"
                            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                        >
                            Пользователи
                        </router-link>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ authStore.user?.name }}</span>
                        <button
                            @click="logout"
                            class="text-sm text-red-500 hover:text-red-700"
                        >
                            Выход
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Контент -->
        <main>
            <router-view />
        </main>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

async function logout() {
    await authStore.logout();
    router.replace('/login');
}
</script>
