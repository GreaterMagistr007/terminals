<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 p-4">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Пользователи</h1>
                <button
                    @click="showCreateForm = true"
                    class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 transition-colors"
                >
                    Добавить
                </button>
            </div>

            <!-- Форма создания -->
            <div v-if="showCreateForm" class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Новый пользователь</h2>
                <form @submit.prevent="createUser" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Имя</label>
                        <input
                            v-model="newUser.name"
                            type="text"
                            required
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Роль</label>
                        <select
                            v-model="newUser.role"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="operator">Оператор</option>
                            <option value="admin">Администратор</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" :disabled="creating" class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50 transition-colors">
                            {{ creating ? 'Создание...' : 'Создать' }}
                        </button>
                        <button type="button" @click="showCreateForm = false" class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors">
                            Отмена
                        </button>
                    </div>
                </form>
                <div v-if="inviteUrl" class="mt-4 rounded bg-green-100 p-3 dark:bg-green-900">
                    <p class="text-sm text-green-800 dark:text-green-200">Ссылка-приглашение:</p>
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            :value="inviteUrl"
                            readonly
                            class="flex-1 rounded border bg-white px-2 py-1 text-sm dark:bg-gray-700 dark:text-white"
                        />
                        <button
                            @click="copyInviteUrl"
                            class="rounded bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600 transition-colors"
                        >
                            {{ copied ? 'Скопировано' : 'Копировать' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Список пользователей -->
            <div class="space-y-2">
                <div
                    v-for="user in users"
                    :key="user.id"
                    class="flex items-center justify-between rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ user.name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ roleLabel(user.role) }}
                            <span v-if="user.telegram_id" class="ml-2">TG: {{ user.telegram_id }}</span>
                            <span v-else class="ml-2 text-yellow-600 dark:text-yellow-400">Telegram не привязан</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Инвайт-ссылка -->
                        <button
                            v-if="!user.telegram_id"
                            @click="regenerateInvite(user)"
                            class="rounded bg-yellow-500 px-3 py-1 text-sm text-white hover:bg-yellow-600 transition-colors"
                        >
                            Инвайт
                        </button>
                        <!-- Смена роли -->
                        <select
                            :value="user.role"
                            @change="updateRole(user, $event.target.value)"
                            class="rounded border border-gray-300 px-2 py-1 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="operator">Оператор</option>
                            <option value="admin">Администратор</option>
                        </select>
                        <!-- Вкл/выкл -->
                        <button
                            @click="toggleActive(user)"
                            :class="user.is_active ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600'"
                            class="rounded px-3 py-1 text-sm text-white transition-colors"
                        >
                            {{ user.is_active ? 'Активен' : 'Отключён' }}
                        </button>
                    </div>
                </div>
            </div>

            <p v-if="!users.length && !loadingUsers" class="mt-8 text-center text-gray-500 dark:text-gray-400">
                Пользователей пока нет.
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import apiClient from '@/api/client';

const users = ref([]);
const loadingUsers = ref(true);
const showCreateForm = ref(false);
const creating = ref(false);
const inviteUrl = ref('');
const copied = ref(false);
const newUser = ref({ name: '', role: 'operator' });

const roleLabel = (role) => role === 'admin' ? 'Администратор' : 'Оператор';

async function fetchUsers() {
    try {
        const { data } = await apiClient.get('/admin/users');
        users.value = data.users;
    } finally {
        loadingUsers.value = false;
    }
}

async function createUser() {
    creating.value = true;
    inviteUrl.value = '';
    try {
        const { data } = await apiClient.post('/admin/users', newUser.value);
        users.value.push(data.user);
        inviteUrl.value = data.invite_url;
        newUser.value = { name: '', role: 'operator' };
    } finally {
        creating.value = false;
    }
}

async function updateRole(user, role) {
    const { data } = await apiClient.put(`/admin/users/${user.id}`, { role });
    Object.assign(user, data.user);
}

async function toggleActive(user) {
    const { data } = await apiClient.put(`/admin/users/${user.id}`, { is_active: !user.is_active });
    Object.assign(user, data.user);
}

async function regenerateInvite(user) {
    const { data } = await apiClient.post(`/admin/users/${user.id}/invite`);
    inviteUrl.value = data.invite_url;
    showCreateForm.value = true;
}

function copyInviteUrl() {
    navigator.clipboard.writeText(inviteUrl.value);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
}

onMounted(fetchUsers);
</script>
