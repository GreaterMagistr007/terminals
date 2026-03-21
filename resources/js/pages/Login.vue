<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-md dark:bg-gray-800">
            <h1 class="mb-6 text-center text-2xl font-bold text-gray-900 dark:text-white">
                Terminals
            </h1>

            <div v-if="error" class="mb-4 rounded bg-red-100 p-3 text-sm text-red-700 dark:bg-red-900 dark:text-red-300">
                {{ error }}
            </div>

            <div v-if="loading" class="text-center text-gray-500 dark:text-gray-400">
                Загрузка...
            </div>

            <div v-else class="space-y-5">
                <!-- Ссылка на бота -->
                <div class="text-center">
                    <p class="mb-3 text-gray-600 dark:text-gray-400">
                        Перейдите в Telegram и напишите боту
                    </p>
                    <a
                        :href="botLink"
                        target="_blank"
                        class="block w-full rounded bg-blue-500 px-4 py-3 text-center font-medium text-white hover:bg-blue-600 transition-colors"
                    >
                        Открыть Telegram
                    </a>
                </div>

                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Ввод кода -->
                <div>
                    <p class="mb-3 text-center text-sm text-gray-500 dark:text-gray-500">
                        Введите код, полученный от администратора
                    </p>
                    <input
                        ref="codeInput"
                        v-model="code"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        placeholder="000000"
                        class="mb-4 block w-full rounded border border-gray-300 px-4 py-3 text-center text-2xl font-mono tracking-widest focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        @keyup.enter="verifyCode"
                    />
                    <button
                        @click="verifyCode"
                        :disabled="code.length !== 6 || verifying"
                        class="block w-full rounded bg-green-600 px-4 py-3 font-medium text-white hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ verifying ? 'Проверка...' : 'Войти' }}
                    </button>
                </div>

                <!-- Обновить ссылку -->
                <p class="text-center">
                    <button
                        @click="refreshSession"
                        :disabled="refreshing"
                        class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        Получить новую ссылку
                    </button>
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import apiClient from '@/api/client';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const error = ref('');
const loading = ref(true);
const botLink = ref('');
const code = ref('');
const verifying = ref(false);
const refreshing = ref(false);
const codeInput = ref(null);

onMounted(async () => {
    if (authStore.isAuthenticated) {
        router.replace('/');
        return;
    }

    if (route.query.error) {
        error.value = route.query.error;
    }

    await initSession();
});

async function initSession() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await apiClient.post('/auth/login-session');
        botLink.value = data.bot_link;
    } catch {
        error.value = 'Не удалось инициализировать сессию.';
    } finally {
        loading.value = false;
    }
}

async function verifyCode() {
    if (code.value.length !== 6 || verifying.value) return;

    verifying.value = true;
    error.value = '';
    try {
        const { data } = await apiClient.post('/auth/verify-code', { code: code.value });
        authStore.user = data.user;
        router.replace('/');
    } catch (e) {
        error.value = e.response?.data?.message || 'Ошибка авторизации.';
        code.value = '';
        codeInput.value?.focus();
    } finally {
        verifying.value = false;
    }
}

async function refreshSession() {
    refreshing.value = true;
    await initSession();
    code.value = '';
    refreshing.value = false;
}
</script>
