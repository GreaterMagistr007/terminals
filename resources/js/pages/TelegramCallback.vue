<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
        <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-md dark:bg-gray-800 text-center">
            <div v-if="loading">
                <p class="text-gray-600 dark:text-gray-400">Авторизация...</p>
            </div>
            <div v-else-if="error">
                <p class="mb-4 text-red-600 dark:text-red-400">{{ error }}</p>
                <router-link
                    to="/login"
                    class="text-blue-500 hover:text-blue-600"
                >
                    Вернуться на страницу входа
                </router-link>
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

const loading = ref(true);
const error = ref('');

onMounted(async () => {
    const token = route.params.token;

    if (!token) {
        error.value = 'Токен отсутствует.';
        loading.value = false;
        return;
    }

    try {
        await authStore.loginViaBotToken(token);
        router.replace('/');
    } catch (e) {
        const message = e.response?.data?.message || 'Ссылка недействительна или истекла.';
        error.value = message;
    } finally {
        loading.value = false;
    }
});
</script>
