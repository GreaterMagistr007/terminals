<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <!-- Хедер -->
            <div class="mb-6 flex items-center gap-3">
                <button
                    @click="goBack"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-200 active:bg-gray-300 dark:text-gray-400 dark:hover:bg-gray-700 dark:active:bg-gray-600 transition-colors"
                    title="Назад"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ warehouseName }}
                </h1>
            </div>

            <!-- Список остатков -->
            <div class="space-y-2">
                <div
                    v-for="stock in stocks"
                    :key="stock.id"
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between">
                        <p class="font-medium text-gray-900 dark:text-white">{{ stock.ingredient.name }}</p>
                        <p
                            class="text-sm"
                            :class="stock.quantity > 0
                                ? 'text-gray-700 dark:text-gray-300'
                                : 'text-gray-400 dark:text-gray-500'"
                        >
                            {{ stock.quantity > 0 ? `${stock.quantity} ${stock.ingredient.unit}` : 'Нет на складе' }}
                        </p>
                    </div>
                </div>
            </div>

            <p v-if="!stocks.length && !loading" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                На складе нет ингредиентов
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '@/api/client';

const route = useRoute();
const router = useRouter();

const stocks = ref([]);
const warehouseName = ref('');
const loading = ref(true);

/** Загрузка остатков склада */
async function fetchStocks() {
    try {
        const { data } = await apiClient.get(`/admin/warehouses/${route.params.id}/stocks`);
        stocks.value = data.stocks;
        warehouseName.value = data.warehouse.name;
    } finally {
        loading.value = false;
    }
}

/** Возврат к списку складов */
function goBack() {
    router.push({ name: 'admin-warehouses' });
}

onMounted(fetchStocks);
</script>
