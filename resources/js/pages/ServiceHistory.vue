<template>
    <div class="px-4 py-4">
        <!-- Шапка -->
        <div class="mb-4 flex items-center gap-3">
            <button
                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                @click="router.back()"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>
            <h1 class="flex-1 text-lg font-bold text-gray-900 truncate dark:text-white">
                История: {{ terminalName }}
            </h1>
        </div>

        <!-- Загрузка -->
        <p v-if="loading" class="text-center text-sm text-gray-400 dark:text-gray-500">Загрузка...</p>

        <!-- Пустое состояние -->
        <p v-else-if="!visits.length" class="text-center text-sm text-gray-400 dark:text-gray-500">
            Посещений пока нет
        </p>

        <!-- Список визитов -->
        <div v-else class="space-y-3">
            <div v-for="visit in visits" :key="visit.id"
                class="rounded-2xl bg-white shadow-sm dark:bg-gray-900 overflow-hidden"
            >
                <!-- Свёрнутая строка -->
                <button
                    @click="toggle(visit.id)"
                    class="flex w-full items-center gap-3 p-4 text-left active:bg-gray-50 dark:active:bg-gray-800 transition-colors"
                >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatShortDate(visit.visited_at) }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ visit.user?.name || 'Оператор' }}</p>
                    </div>
                    <span v-if="broughtCount(visit)" class="rounded-md bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-400">
                        {{ broughtCount(visit) }} ингр.
                    </span>
                    <svg
                        class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                        :class="{ 'rotate-180': expandedId === visit.id }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                <!-- Раскрытое содержимое -->
                <div v-if="expandedId === visit.id" class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800">
                    <!-- Дата и оператор -->
                    <div class="mb-3 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Дата и время</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ formatFullDate(visit.visited_at) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-2.5 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Оператор</p>
                            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ visit.user?.name || 'Неизвестен' }}</p>
                        </div>
                    </div>

                    <!-- Вода -->
                    <div v-if="visit.water_main !== null || visit.water_spare !== null" class="mb-3">
                        <p class="mb-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Вода</p>
                        <div class="flex gap-3">
                            <div v-if="visit.water_main !== null" class="flex items-center gap-1.5">
                                <div class="h-2.5 w-2.5 rounded-full bg-blue-400"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Основная: {{ Number(visit.water_main).toFixed(1) }}</span>
                            </div>
                            <div v-if="visit.water_spare !== null" class="flex items-center gap-1.5">
                                <div class="h-2.5 w-2.5 rounded-full bg-cyan-400"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300">Запасная: {{ Number(visit.water_spare).toFixed(1) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Ингредиенты "Принёс" -->
                    <div v-if="broughtIngredients(visit).length" class="mb-3">
                        <p class="mb-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Принёс</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="ing in broughtIngredients(visit)" :key="'b-' + ing.id"
                                class="rounded-md bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-400"
                            >
                                {{ ing.ingredient?.short_name || ing.ingredient?.name }} +{{ ing.brought }}
                            </span>
                        </div>
                    </div>

                    <!-- Ингредиенты "Нужно" -->
                    <div v-if="neededIngredients(visit).length" class="mb-3">
                        <p class="mb-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Нужно принести</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="ing in neededIngredients(visit)" :key="'n-' + ing.id"
                                class="rounded-md bg-orange-100 px-2 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/40 dark:text-orange-400"
                            >
                                {{ ing.ingredient?.short_name || ing.ingredient?.name }} {{ ing.needed }}
                            </span>
                        </div>
                    </div>

                    <!-- Комментарий -->
                    <div v-if="visit.comment" class="mb-3">
                        <p class="mb-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Комментарий</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ visit.comment }}</p>
                    </div>

                    <!-- Фото -->
                    <div v-if="visit.photos?.length" class="mb-1">
                        <p class="mb-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500">Фото</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="photo in visit.photos" :key="photo.id" class="relative">
                                <img
                                    :src="photo.url || '/storage/' + photo.path"
                                    class="h-32 w-full cursor-pointer rounded-xl object-cover active:opacity-80"
                                    :alt="photoTypeLabel(photo.type)"
                                    @click="openFullscreen(photo)"
                                />
                                <span class="absolute bottom-1.5 left-1.5 rounded-md bg-black/50 px-1.5 py-0.5 text-[10px] font-medium text-white">
                                    {{ photoTypeLabel(photo.type) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Полноэкранный просмотр фото -->
        <div v-if="fullscreenPhoto" class="fixed inset-0 z-50 flex items-center justify-center bg-black/90" @click="fullscreenPhoto = null">
            <button
                class="absolute top-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white active:bg-white/30"
                @click.stop="fullscreenPhoto = null"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img
                :src="fullscreenPhoto.url || '/storage/' + fullscreenPhoto.path"
                class="max-h-full max-w-full object-contain"
                :alt="photoTypeLabel(fullscreenPhoto.type)"
                @click.stop
            />
            <span class="absolute bottom-6 left-1/2 -translate-x-1/2 rounded-lg bg-black/50 px-3 py-1.5 text-sm font-medium text-white">
                {{ photoTypeLabel(fullscreenPhoto.type) }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/client';

const IRKUTSK_TZ = 'Asia/Irkutsk';

const router = useRouter();
const route = useRoute();

const visits = ref([]);
const terminalName = ref('Загрузка...');
const loading = ref(true);
const expandedId = ref(null);
const fullscreenPhoto = ref(null);

function toggle(id) {
    expandedId.value = expandedId.value === id ? null : id;
}

/** Краткая дата для свёрнутого состояния */
function formatShortDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();

    const irkNow = new Date(now.toLocaleString('en-US', { timeZone: IRKUTSK_TZ }));
    const irkDate = new Date(date.toLocaleString('en-US', { timeZone: IRKUTSK_TZ }));

    const todayStart = new Date(irkNow);
    todayStart.setHours(0, 0, 0, 0);

    const yesterdayStart = new Date(todayStart);
    yesterdayStart.setDate(yesterdayStart.getDate() - 1);

    const time = irkDate.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });

    if (irkDate >= todayStart) {
        return `Сегодня, ${time}`;
    }
    if (irkDate >= yesterdayStart) {
        return `Вчера, ${time}`;
    }

    return date.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        timeZone: IRKUTSK_TZ,
    }) + ', ' + time;
}

/** Полная дата и время для раскрытого состояния */
function formatFullDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('ru-RU', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: IRKUTSK_TZ,
    });
}

/** Количество принесённых ингредиентов (для краткой сводки) */
function broughtCount(visit) {
    if (!visit.ingredients?.length) return 0;
    return visit.ingredients.filter(i => i.brought > 0).length;
}

/** Ингредиенты с brought > 0 */
function broughtIngredients(visit) {
    if (!visit.ingredients?.length) return [];
    return visit.ingredients.filter(i => i.brought > 0);
}

/** Ингредиенты с needed > 0 */
function neededIngredients(visit) {
    if (!visit.ingredients?.length) return [];
    return visit.ingredients.filter(i => i.needed > 0);
}

/** Подпись типа фото */
function photoTypeLabel(type) {
    const labels = {
        inside: 'Внутри',
        outside: 'Снаружи',
        comment: 'Комментарий',
    };
    return labels[type] || type;
}

function openFullscreen(photo) {
    fullscreenPhoto.value = photo;
}

async function fetchData() {
    try {
        const terminalId = route.params.id;
        const [terminalRes, visitsRes] = await Promise.all([
            apiClient.get(`/terminals/${terminalId}`),
            apiClient.get('/service-visits', { params: { terminal_id: terminalId } }),
        ]);

        terminalName.value = terminalRes.data.terminal?.comment || 'Без описания';

        // Сортировка по visited_at, от новых к старым
        visits.value = (visitsRes.data.visits || []).sort((a, b) => {
            return new Date(b.visited_at) - new Date(a.visited_at);
        });
    } catch {
        terminalName.value = 'Ошибка загрузки';
    } finally {
        loading.value = false;
    }
}

onMounted(fetchData);
</script>
