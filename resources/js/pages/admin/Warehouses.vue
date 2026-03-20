<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Склады</h1>
                <button
                    @click="showCreateForm = true"
                    class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 transition-colors"
                >
                    Добавить
                </button>
            </div>

            <!-- Форма создания -->
            <div v-if="showCreateForm" class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Новый склад</h2>
                <form @submit.prevent="createWarehouse" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Название</label>
                        <input
                            v-model="newWarehouse.name"
                            type="text"
                            required
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

            <!-- Список складов -->
            <div class="space-y-2">
                <div
                    v-for="warehouse in warehouses"
                    :key="warehouse.id"
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <!-- Режим редактирования -->
                    <div v-if="editingId === warehouse.id">
                        <form @submit.prevent="saveEdit(warehouse)" class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Название</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
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
                        <div
                            class="flex-1 cursor-pointer"
                            @click="goToStocks(warehouse)"
                        >
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900 dark:text-white">{{ warehouse.name }}</p>
                                <span
                                    v-if="warehouse.is_default"
                                    class="rounded bg-blue-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                                >
                                    По умолчанию
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Редактирование -->
                            <button
                                @click="startEdit(warehouse)"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-blue-500 active:bg-gray-200 dark:hover:bg-gray-700 dark:hover:text-blue-400 transition-colors"
                                title="Редактировать"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                </svg>
                            </button>

                            <!-- Удаление -->
                            <button
                                v-if="!warehouse.is_default"
                                @click="confirmDelete(warehouse)"
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

            <p v-if="!warehouses.length && !loading" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Складов пока нет
            </p>

            <!-- Модалка подтверждения удаления -->
            <div v-if="deletingWarehouse" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="deletingWarehouse = null">
                <div class="mx-4 w-full max-w-sm rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Удалить склад?</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Склад "{{ deletingWarehouse.name }}" и все его остатки будут удалены. Это действие необратимо.
                    </p>
                    <div class="mt-4 flex gap-2 justify-end">
                        <button
                            @click="deletingWarehouse = null"
                            class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors"
                        >
                            Отмена
                        </button>
                        <button
                            @click="deleteWarehouse"
                            :disabled="deleting"
                            class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600 disabled:opacity-50 transition-colors"
                        >
                            {{ deleting ? 'Удаление...' : 'Удалить' }}
                        </button>
                    </div>
                    <div v-if="deleteError" class="mt-3 rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        {{ deleteError }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/client';

const router = useRouter();

const warehouses = ref([]);
const loading = ref(true);
const showCreateForm = ref(false);
const creating = ref(false);
const createError = ref('');
const saving = ref(false);
const editingId = ref(null);
const editForm = ref({ name: '' });
const editError = ref('');
const deletingWarehouse = ref(null);
const deleting = ref(false);
const deleteError = ref('');

const newWarehouse = ref({ name: '' });

/** Загрузка списка складов */
async function fetchWarehouses() {
    try {
        const { data } = await apiClient.get('/admin/warehouses');
        warehouses.value = data.warehouses;
    } finally {
        loading.value = false;
    }
}

/** Создание склада */
async function createWarehouse() {
    creating.value = true;
    createError.value = '';
    try {
        const { data } = await apiClient.post('/admin/warehouses', newWarehouse.value);
        warehouses.value.push(data.warehouse);
        warehouses.value.sort((a, b) => a.name.localeCompare(b.name));
        newWarehouse.value = { name: '' };
        showCreateForm.value = false;
    } catch (error) {
        createError.value = error.response?.data?.message || 'Не удалось создать склад';
    } finally {
        creating.value = false;
    }
}

/** Отмена создания */
function cancelCreate() {
    showCreateForm.value = false;
    createError.value = '';
    newWarehouse.value = { name: '' };
}

/** Начало редактирования */
function startEdit(warehouse) {
    editingId.value = warehouse.id;
    editForm.value = { name: warehouse.name };
    editError.value = '';
}

/** Отмена редактирования */
function cancelEdit() {
    editingId.value = null;
    editError.value = '';
}

/** Сохранение редактирования */
async function saveEdit(warehouse) {
    saving.value = true;
    editError.value = '';
    try {
        const { data } = await apiClient.put(`/admin/warehouses/${warehouse.id}`, editForm.value);
        Object.assign(warehouse, data.warehouse);
        warehouses.value.sort((a, b) => a.name.localeCompare(b.name));
        editingId.value = null;
    } catch (error) {
        editError.value = error.response?.data?.message || 'Не удалось сохранить изменения';
    } finally {
        saving.value = false;
    }
}

/** Подтверждение удаления */
function confirmDelete(warehouse) {
    deletingWarehouse.value = warehouse;
    deleteError.value = '';
}

/** Удаление склада */
async function deleteWarehouse() {
    deleting.value = true;
    deleteError.value = '';
    try {
        await apiClient.delete(`/admin/warehouses/${deletingWarehouse.value.id}`);
        warehouses.value = warehouses.value.filter(w => w.id !== deletingWarehouse.value.id);
        deletingWarehouse.value = null;
    } catch (error) {
        deleteError.value = error.response?.data?.message || 'Не удалось удалить склад';
    } finally {
        deleting.value = false;
    }
}

/** Переход к остаткам склада */
function goToStocks(warehouse) {
    router.push({ name: 'admin-warehouse-stocks', params: { id: warehouse.id } });
}

onMounted(fetchWarehouses);
</script>
