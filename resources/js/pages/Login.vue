<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
            <h1 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">
                Terminals
            </h1>
            <p class="mb-6 text-center text-gray-600 dark:text-gray-400">
                Войдите через Telegram
            </p>

            <div v-if="error" class="mb-4 rounded bg-red-100 p-3 text-sm text-red-700 dark:bg-red-900 dark:text-red-300">
                {{ error }}
            </div>

            <div v-if="loading" class="text-center text-gray-500 dark:text-gray-400">
                Авторизация...
            </div>

            <div v-else class="space-y-4">
                <!-- Telegram Login Widget (работает только на HTTPS с доменом) -->
                <div id="telegram-login-widget" class="flex justify-center"></div>

                <!-- Ссылка на бота для авторизации -->
                <a
                    v-if="botUsername"
                    :href="`https://t.me/${botUsername}?start`"
                    target="_blank"
                    class="block w-full rounded bg-blue-500 px-4 py-3 text-center font-medium text-white hover:bg-blue-600 transition-colors"
                >
                    Войти через Telegram бота
                </a>

                <p class="text-center text-xs text-gray-500 dark:text-gray-500">
                    Напишите боту /start и перейдите по ссылке
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const error = ref('');
const loading = ref(false);
const botUsername = import.meta.env.VITE_TELEGRAM_BOT_USERNAME || '';

onMounted(async () => {
    if (authStore.isAuthenticated) {
        router.replace('/');
        return;
    }

    // Обработка ошибок из query-параметров (например, после неудачного редиректа)
    if (route.query.error) {
        error.value = route.query.error;
    }
});
</script>
