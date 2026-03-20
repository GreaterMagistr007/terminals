<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ингредиенты</h1>
                <button
                    @click="showCreateForm = true"
                    class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 transition-colors"
                >
                    Добавить
                </button>
            </div>

            <!-- Форма создания -->
            <div v-if="showCreateForm" class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Новый ингредиент</h2>
                <form @submit.prevent="createIngredient" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Название</label>
                        <input
                            v-model="newIngredient.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Единица измерения</label>
                        <input
                            v-model="newIngredient.unit"
                            type="text"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Стоимость за единицу</label>
                        <input
                            v-model.number="newIngredient.cost_per_unit"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="creating" class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50 transition-colors">
                            {{ creating ? 'Создание...' : 'Создать' }}
                        </button>
                        <button type="button" @click="cancelCreate" class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors">
                            Отмена
                        </button>
                    </div>
                    <div v-if="createError" class="rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        {{ createError }}
                    </div>
                </form>
            </div>

            <!-- Список ингредиентов -->
            <div class="space-y-2">
                <div
                    v-for="ingredient in ingredients"
                    :key="ingredient.id"
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <!-- Режим редактирования -->
                    <div v-if="editingId === ingredient.id">
                        <form @submit.prevent="saveEdit(ingredient)" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Название</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Единица измерения</label>
                                <input
                                    v-model="editForm.unit"
                                    type="text"
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Стоимость за единицу</label>
                                <input
                                    v-model.number="editForm.cost_per_unit"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" :disabled="saving" class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50 transition-colors">
                                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                                </button>
                                <button type="button" @click="cancelEdit" class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors">
                                    Отмена
                                </button>
                            </div>
                            <div v-if="editError" class="rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                {{ editError }}
                            </div>
                        </form>
                    </div>

                    <!-- Режим просмотра -->
                    <div v-else class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ ingredient.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ ingredient.unit }}
                                <span class="ml-2">{{ formatCost(ingredient.cost_per_unit) }}</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Редактирование -->
                            <button
                                @click="startEdit(ingredient)"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-blue-500 active:bg-gray-200 dark:hover:bg-gray-700 dark:hover:text-blue-400 transition-colors"
                                title="Редактировать"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>

                            <!-- Переключатель активности -->
                            <button
                                @click="toggleActive(ingredient)"
                                :disabled="saving"
                                class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 focus:outline-none"
                                :class="ingredient.is_active ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'"
                                role="switch"
                                :aria-checked="ingredient.is_active"
                                title="Активность"
                            >
                                <span
                                    class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                                    :class="ingredient.is_active ? 'translate-x-5' : 'translate-x-0'"
                                ></span>
                            </button>

                            <!-- Удаление -->
                            <button
                                @click="confirmDelete(ingredient)"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-red-500 active:bg-gray-200 dark:hover:bg-gray-700 dark:hover:text-red-400 transition-colors"
                                title="Удалить"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="!ingredients.length && !loading" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Ингредиентов пока нет
            </p>

            <!-- Модалка подтверждения удаления -->
            <div v-if="deletingIngredient" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="deletingIngredient = null">
                <div class="mx-4 w-full max-w-sm rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Удалить ингредиент?</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Ингредиент "{{ deletingIngredient.name }}" будет удалён. Это действие необратимо.
                    </p>
                    <div class="mt-4 flex gap-2 justify-end">
                        <button
                            @click="deletingIngredient = null"
                            class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            @click="deleteIngredient"
                            :disabled="deleting"
                            class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600 disabled:opacity-50 transition-colors"
                        >
                            {{ deleting ? 'Удаление...' : 'Удалить' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';

const ingredients = ref([]);
const loading = ref(true);
const showCreateForm = ref(false);
const creating = ref(false);
const createError = ref('');
const saving = ref(false);
const editingId = ref(null);
const editForm = ref({ name: '', unit: '', cost_per_unit: 0 });
const editError = ref('');
const deletingIngredient = ref(null);
const deleting = ref(false);

const newIngredient = ref({
    name: '',
    unit: 'упаковка',
    cost_per_unit: 0,
});

/** Форматирование стоимости */
function formatCost(value) {
    return parseFloat(value).toFixed(2) + ' \u20BD';
}

/** Загрузка списка ингредиентов */
async function fetchIngredients() {
    try {
        const { data } = await apiClient.get('/admin/ingredients');
        ingredients.value = data.ingredients;
    } finally {
        loading.value = false;
    }
}

/** Создание ингредиента */
async function createIngredient() {
    creating.value = true;
    createError.value = '';
    try {
        const { data } = await apiClient.post('/admin/ingredients', newIngredient.value);
        ingredients.value.push(data.ingredient);
        // Пересортировка по имени
        ingredients.value.sort((a, b) => a.name.localeCompare(b.name));
        newIngredient.value = { name: '', unit: 'упаковка', cost_per_unit: 0 };
        showCreateForm.value = false;
    } catch (error) {
        createError.value = error.response?.data?.message || 'Не удалось создать ингредиент';
    } finally {
        creating.value = false;
    }
}

/** Отмена создания */
function cancelCreate() {
    showCreateForm.value = false;
    createError.value = '';
    newIngredient.value = { name: '', unit: 'упаковка', cost_per_unit: 0 };
}

/** Начало редактирования */
function startEdit(ingredient) {
    editingId.value = ingredient.id;
    editForm.value = {
        name: ingredient.name,
        unit: ingredient.unit,
        cost_per_unit: ingredient.cost_per_unit,
    };
    editError.value = '';
}

/** Отмена редактирования */
function cancelEdit() {
    editingId.value = null;
    editError.value = '';
}

/** Сохранение редактирования */
async function saveEdit(ingredient) {
    saving.value = true;
    editError.value = '';
    try {
        const { data } = await apiClient.put(`/admin/ingredients/${ingredient.id}`, editForm.value);
        Object.assign(ingredient, data.ingredient);
        // Пересортировка по имени
        ingredients.value.sort((a, b) => a.name.localeCompare(b.name));
        editingId.value = null;
    } catch (error) {
        editError.value = error.response?.data?.message || 'Не удалось сохранить изменения';
    } finally {
        saving.value = false;
    }
}

/** Переключение активности */
async function toggleActive(ingredient) {
    saving.value = true;
    try {
        const { data } = await apiClient.put(`/admin/ingredients/${ingredient.id}`, {
            is_active: !ingredient.is_active,
        });
        Object.assign(ingredient, data.ingredient);
    } finally {
        saving.value = false;
    }
}

/** Подтверждение удаления */
function confirmDelete(ingredient) {
    deletingIngredient.value = ingredient;
}

/** Удаление ингредиента */
async function deleteIngredient() {
    deleting.value = true;
    try {
        await apiClient.delete(`/admin/ingredients/${deletingIngredient.value.id}`);
        ingredients.value = ingredients.value.filter(i => i.id !== deletingIngredient.value.id);
        deletingIngredient.value = null;
    } finally {
        deleting.value = false;
    }
}

onMounted(fetchIngredients);
</script>
