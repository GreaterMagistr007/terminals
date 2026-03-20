<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <!-- Хедер -->
            <div class="mb-6 flex items-center gap-3">
                <button
                    @click="router.push({ name: 'admin-ingredients' })"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-white hover:text-gray-700 active:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </button>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    История: {{ ingredientName }}
                </h1>
            </div>

            <!-- Загрузка -->
            <div v-if="loading" class="py-12 text-center text-gray-400 dark:text-gray-500">
                Загрузка...
            </div>

            <!-- Пустое состояние -->
            <p v-else-if="!movements.length" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Движений пока нет
            </p>

            <!-- Список движений -->
            <div v-else class="space-y-3">
                <div
                    v-for="movement in movements"
                    :key="movement.id"
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <!-- Заголовок: дата + бейдж типа -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ formatDate(movement.created_at) }}
                        </span>
                        <span
                            class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="typeBadgeClass(movement.type)"
                        >
                            {{ typeLabel(movement.type) }}
                        </span>
                    </div>

                    <!-- Детали -->
                    <div class="mt-2 space-y-1">
                        <!-- Количество -->
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ movement.quantity }} {{ ingredientUnit }}
                        </p>

                        <!-- Покупка -->
                        <template v-if="movement.type === 'purchase'">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                &rarr; {{ movement.to_warehouse?.name || 'Удалённый склад' }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Цена: {{ formatCost(movement.cost_per_unit) }}/ед.
                                <span class="ml-2 text-xs text-gray-400 dark:text-gray-500">
                                    {{ movement.source === 'box' ? 'коробками' : 'поштучно' }}
                                </span>
                            </p>
                        </template>

                        <!-- Перемещение -->
                        <template v-if="movement.type === 'transfer'">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ movement.from_warehouse?.name || 'Удалённый склад' }}
                                &rarr;
                                {{ movement.to_warehouse?.name || 'Удалённый склад' }}
                            </p>
                        </template>

                        <!-- Списание -->
                        <template v-if="movement.type === 'write_off'">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                &larr; {{ movement.from_warehouse?.name || 'Удалённый склад' }}
                            </p>
                            <p v-if="movement.reason" class="text-sm text-gray-500 dark:text-gray-400">
                                Причина: {{ movement.reason }}
                            </p>
                        </template>

                        <!-- Комментарий -->
                        <p v-if="movement.note" class="text-xs text-gray-400 dark:text-gray-500 italic">
                            {{ movement.note }}
                        </p>

                        <!-- Пользователь -->
                        <p v-if="movement.user" class="text-xs text-gray-400 dark:text-gray-500">
                            {{ movement.user.name }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import apiClient from '@/api/client';

const route = useRoute();
const router = useRouter();

const ingredientId = route.params.id;
const ingredientName = ref('...');
const ingredientUnit = ref('');
const movements = ref([]);
const loading = ref(true);

/** Форматирование даты в читаемый вид */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const months = [
        'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря',
    ];
    const day = date.getDate();
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year}, ${hours}:${minutes}`;
}

/** Форматирование стоимости */
function formatCost(value) {
    return parseFloat(value || 0).toFixed(2) + ' \u20BD';
}

/** Метка типа движения */
function typeLabel(type) {
    const labels = {
        purchase: 'Покупка',
        transfer: 'Перемещение',
        write_off: 'Списание',
    };
    return labels[type] || type;
}

/** CSS-классы бейджа типа */
function typeBadgeClass(type) {
    const classes = {
        purchase: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400',
        transfer: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
        write_off: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
    };
    return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
}

/** Загрузка данных */
async function fetchData() {
    try {
        // Параллельная загрузка ингредиентов и истории
        const [ingredientsRes, historyRes] = await Promise.all([
            apiClient.get('/admin/ingredients'),
            apiClient.get(`/admin/ingredients/${ingredientId}/history`),
        ]);

        // Поиск ингредиента по id
        const ingredient = ingredientsRes.data.ingredients.find(
            i => i.id === Number(ingredientId)
        );
        if (ingredient) {
            ingredientName.value = ingredient.name;
            ingredientUnit.value = ingredient.unit;
        }

        movements.value = historyRes.data.movements;
    } finally {
        loading.value = false;
    }
}

onMounted(fetchData);
</script>
