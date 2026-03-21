<template>
    <div class="px-4 py-4">
        <!-- Заголовок с кнопками -->
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900 dark:text-white">Точки обслуживания</h1>
            <div class="flex items-center gap-1">
                <button
                    @click="refreshTerminals"
                    :disabled="refreshing"
                    class="rounded-lg p-1.5 text-gray-400 active:bg-gray-100 disabled:opacity-40 dark:text-gray-500 dark:active:bg-gray-800"
                >
                    <svg class="h-5 w-5" :class="{ 'animate-spin': refreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
                    </svg>
                </button>
                <button @click="showSettings = !showSettings"
                    class="rounded-lg p-1.5 text-gray-400 active:bg-gray-100 dark:text-gray-500 dark:active:bg-gray-800"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Карточка настроек -->
        <div v-if="showSettings" class="mb-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900">
            <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Настройки</p>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Сортировка</span>
                    <select
                        v-model="sortMode"
                        @change="saveSettings"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
                    >
                        <option value="service">По обслуживанию</option>
                        <option value="sales">По стаканам</option>
                        <option value="alphabet">По алфавиту</option>
                    </select>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Показать скрытые</span>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" v-model="showHidden" @change="saveSettings" class="peer sr-only" />
                        <div class="h-5 w-9 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all peer-checked:bg-blue-500 peer-checked:after:translate-x-full dark:bg-gray-700 dark:peer-checked:bg-blue-500"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Баннер ожидающих отправки визитов -->
        <div v-if="offlineQueueStore.pendingCount > 0" class="mb-4 flex items-center gap-2 rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-900/20">
            <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium text-amber-700 dark:text-amber-400">
                Ожидает отправки: {{ offlineQueueStore.pendingCount }} визит{{ pendingVisitsSuffix }}
            </span>
        </div>

        <!-- Загрузка -->
        <p v-if="loading" class="text-center text-sm text-gray-400 dark:text-gray-500">Загрузка...</p>

        <!-- Пустой список -->
        <p v-else-if="!sortedTerminals.length" class="text-center text-sm text-gray-400 dark:text-gray-500">
            Терминалов нет. Администратор может загрузить список через раздел «Терминалы».
        </p>

        <!-- Список терминалов -->
        <div v-else class="space-y-2">
            <div v-for="terminal in sortedTerminals" :key="terminal.id"
                class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-900"
            >
                <!-- Свёрнутая строка -->
                <button @click="toggle(terminal.id)"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left active:bg-gray-50 dark:active:bg-gray-800"
                >
                    <div class="h-10 w-1 shrink-0 rounded-full" :class="statusBarClass(terminal)"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 truncate dark:text-white">{{ terminal.comment || 'Без описания' }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">продаж с последнего обслуживания: {{ terminal.sales_since_last_visit ?? 0 }}</p>
                    </div>
                    <div class="shrink-0 flex items-center gap-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatVisitDate(terminal.service_visits_max_visited_at) }}</span>
                        <svg class="h-4 w-4 text-gray-300 transition-transform dark:text-gray-600"
                            :class="expandedId === terminal.id ? 'rotate-180' : ''"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                </button>

                <!-- Раскрытый контент -->
                <div v-if="expandedId === terminal.id"
                    class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800"
                >
                    <!-- Вода и последний визит -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Вода</p>
                            <template v-if="terminal.latest_visit && (terminal.latest_visit.water_main != null || terminal.latest_visit.water_spare != null)">
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-1.5 rounded-full transition-all"
                                            :class="estimatedWater(terminal).main > 0.2 ? 'bg-blue-400 dark:bg-blue-500' : 'bg-red-400 dark:bg-red-500'"
                                            :style="{ width: (estimatedWater(terminal).main * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-xs font-semibold" :class="estimatedWater(terminal).main > 0.2 ? 'text-gray-600 dark:text-gray-300' : 'text-red-500 dark:text-red-400'">{{ estimatedWater(terminal).main.toFixed(1) }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div class="h-1.5 rounded-full transition-all"
                                            :class="estimatedWater(terminal).spare > 0.2 ? 'bg-cyan-400 dark:bg-cyan-500' : 'bg-red-400 dark:bg-red-500'"
                                            :style="{ width: (estimatedWater(terminal).spare * 100) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-xs font-semibold" :class="estimatedWater(terminal).spare > 0.2 ? 'text-gray-600 dark:text-gray-300' : 'text-red-500 dark:text-red-400'">{{ estimatedWater(terminal).spare.toFixed(1) }}</span>
                                </div>
                            </template>
                            <p v-else class="mt-1 text-sm font-semibold text-gray-400">—</p>
                        </div>
                        <div
                            class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
                            :class="terminal.service_visits_max_visited_at ? 'cursor-pointer active:bg-gray-100 dark:active:bg-gray-700' : ''"
                            @click.stop="terminal.service_visits_max_visited_at && confirmEditVisit(terminal.id)"
                        >
                            <p class="text-xs text-gray-400 dark:text-gray-500">Последний визит</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ formatVisitDate(terminal.service_visits_max_visited_at) }}</p>
                        </div>
                    </div>

                    <!-- Ингредиенты -->
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Ингредиенты</p>
                        <template v-if="terminal.latest_visit?.ingredients?.length">
                            <div class="flex flex-wrap gap-1.5">
                                <template v-for="vi in terminal.latest_visit.ingredients" :key="vi.id">
                                    <span v-if="vi.brought" class="rounded-md bg-green-100 px-1.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-400">
                                        {{ vi.ingredient?.short_name || vi.ingredient?.name }} +{{ vi.brought }}
                                    </span>
                                    <span v-if="vi.needed" class="rounded-md bg-orange-100 px-1.5 py-0.5 text-xs font-medium text-orange-700 dark:bg-orange-900/40 dark:text-orange-400">
                                        {{ vi.ingredient?.short_name || vi.ingredient?.name }} {{ vi.needed }}
                                    </span>
                                </template>
                            </div>
                        </template>
                        <p v-else class="text-xs text-gray-300 dark:text-gray-600">Нет данных</p>
                    </div>

                    <!-- Кнопки действий -->
                    <div class="flex gap-2">
                        <router-link :to="{ name: 'service', params: { id: terminal.id } }" class="flex-1 flex items-center justify-center gap-1.5 rounded-lg bg-blue-500 py-2.5 text-sm font-medium text-white active:bg-blue-600">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
                            </svg>
                            Обслужить
                        </router-link>
                        <router-link :to="{ name: 'history', params: { id: terminal.id } }" class="flex items-center justify-center gap-1.5 rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-700 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:active:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            История
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модалка подтверждения редактирования -->
        <div v-if="editModal.visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="editModal.visible = false">
            <div class="mx-4 w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Изменить последний визит?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Данные визита будут загружены в форму для редактирования</p>
                <div class="mt-5 flex gap-3">
                    <button
                        @click="editModal.visible = false"
                        class="flex-1 rounded-xl bg-gray-100 py-2.5 text-sm font-semibold text-gray-700 active:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:active:bg-gray-600"
                    >Нет</button>
                    <button
                        @click="goToEditVisit"
                        class="flex-1 rounded-xl bg-blue-500 py-2.5 text-sm font-semibold text-white active:bg-blue-600"
                    >Да</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/client';
import { useOfflineQueueStore } from '@/stores/offlineQueue';
import { useTerminalsStore } from '@/stores/terminals';

const router = useRouter();
const offlineQueueStore = useOfflineQueueStore();
const terminalsStore = useTerminalsStore();

const IRKUTSK_TZ = 'Asia/Irkutsk';
const SETTINGS_KEY = 'terminals_home_settings';

// Константы расхода воды (будут заменены реальными рецептами)
const BOTTLE_VOLUME_ML = 18900; // объём полной бутылки, мл
const WATER_PER_CUP_ML = 340;  // расход воды на стакан, мл

/**
 * Расчёт оставшейся воды с учётом продаж.
 * Когда основная бутыль заканчивается, расход переходит на запасную.
 */
function estimatedWater(terminal) {
    if (!terminal.latest_visit) return { main: 0, spare: 0 };
    const mainMl = (terminal.latest_visit.water_main ?? 0) * BOTTLE_VOLUME_ML;
    const spareMl = (terminal.latest_visit.water_spare ?? 0) * BOTTLE_VOLUME_ML;
    const usedMl = (terminal.sales_since_last_visit ?? 0) * WATER_PER_CUP_ML;

    let remainingMain = mainMl - usedMl;
    let remainingSpare = spareMl;

    if (remainingMain < 0) {
        remainingSpare = Math.max(0, spareMl + remainingMain);
        remainingMain = 0;
    }

    return {
        main: Math.min(1, remainingMain / BOTTLE_VOLUME_ML),
        spare: Math.min(1, remainingSpare / BOTTLE_VOLUME_ML),
    };
}

const terminals = computed(() => terminalsStore.terminals);
const loading = ref(true);
const refreshing = ref(false);
const expandedId = ref(null);
const showSettings = ref(false);

// Загрузка настроек из localStorage (default: service, showHidden=false)
const saved = JSON.parse(localStorage.getItem(SETTINGS_KEY) || '{}');
const sortMode = ref(saved.sortMode || 'service');
const showHidden = ref(saved.showHidden ?? false);

function saveSettings() {
    localStorage.setItem(SETTINGS_KEY, JSON.stringify({
        sortMode: sortMode.value,
        showHidden: showHidden.value,
    }));
}

const sortedTerminals = computed(() => {
    let list = [...terminals.value];
    if (!showHidden.value) {
        list = list.filter(t => !t.settings?.hidden);
    }
    if (sortMode.value === 'alphabet') {
        return list.sort((a, b) => (a.comment || '').localeCompare(b.comment || '', 'ru'));
    }
    if (sortMode.value === 'sales') {
        // По стаканам с последнего обслуживания: от большего к меньшему
        return list.sort((a, b) => (b.sales_since_last_visit ?? 0) - (a.sales_since_last_visit ?? 0));
    }
    // По обслуживанию: от самого давнего (null/старая дата первым) к недавнему
    return list.sort((a, b) => {
        const dateA = a.service_visits_max_visited_at ? new Date(a.service_visits_max_visited_at).getTime() : 0;
        const dateB = b.service_visits_max_visited_at ? new Date(b.service_visits_max_visited_at).getTime() : 0;
        return dateA - dateB;
    });
});

const toggle = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

// Модалка редактирования последнего визита
const editModal = reactive({ visible: false, terminalId: null });

function confirmEditVisit(terminalId) {
    editModal.terminalId = terminalId;
    editModal.visible = true;
}

function goToEditVisit() {
    editModal.visible = false;
    router.push({ name: 'service', params: { id: editModal.terminalId }, query: { edit: 'last' } });
}

/**
 * Склонение слова "визит" для баннера ожидающих.
 */
const pendingVisitsSuffix = computed(() => {
    const n = offlineQueueStore.pendingCount;
    const abs = Math.abs(n) % 100;
    const last = abs % 10;
    if (abs > 10 && abs < 20) return 'ов';
    if (last === 1) return '';
    if (last >= 2 && last <= 4) return 'а';
    return 'ов';
});

async function fetchTerminals() {
    // Если в store уже есть данные (из кеша) — показать сразу
    if (terminalsStore.terminals.length) {
        loading.value = false;
    }
    await terminalsStore.fetch();
    loading.value = false;
}

async function refreshTerminals() {
    refreshing.value = true;
    try {
        await terminalsStore.refresh();
    } catch {
        // Ошибка сети — данные остаются из кеша
    } finally {
        refreshing.value = false;
    }
}

function statusBarClass(terminal) {
    // TODO: заменить на логику по данным обслуживания
    return {
        1: 'bg-green-500',
        2: 'bg-red-500',
        3: 'bg-gray-400',
    }[terminal.state] || 'bg-gray-300';
}

/**
 * Форматирование даты по иркутскому времени.
 * < 1 часа: "N минут"
 * Сегодня (по Иркутску): "N часов"
 * Вчера (по Иркутску): "Вчера"
 * 2-7 дней: "N дней"
 * > 7 дней: "28 января"
 */
function formatVisitDate(dateStr) {
    if (!dateStr) return 'Нет данных';

    const date = new Date(dateStr);
    const now = new Date();

    const irkNow = new Date(now.toLocaleString('en-US', { timeZone: IRKUTSK_TZ }));
    const irkDate = new Date(date.toLocaleString('en-US', { timeZone: IRKUTSK_TZ }));

    const todayStart = new Date(irkNow);
    todayStart.setHours(0, 0, 0, 0);

    const tomorrowStart = new Date(todayStart);
    tomorrowStart.setDate(tomorrowStart.getDate() + 1);

    // Форматирование времени по Иркутску (ЧЧ:ММ)
    const irkTime = date.toLocaleTimeString('ru-RU', {
        hour: '2-digit', minute: '2-digit',
        timeZone: IRKUTSK_TZ,
    });

    const diffMs = now - date;

    // Дата в будущем или сегодня: показываем "Сегодня, ЧЧ:ММ"
    if (diffMs < 0) {
        if (irkDate >= todayStart && irkDate < tomorrowStart) {
            return `Сегодня, ${irkTime}`;
        }
        return date.toLocaleDateString('ru-RU', {
            day: 'numeric', month: 'long',
            timeZone: IRKUTSK_TZ,
        }) + `, ${irkTime}`;
    }

    const diffMinutes = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);

    if (diffMinutes < 60) {
        if (diffMinutes < 1) return 'Только что';
        return `${diffMinutes} ${pluralize(diffMinutes, 'минута', 'минуты', 'минут')}`;
    }

    if (irkDate >= todayStart) {
        return `Сегодня, ${irkTime}`;
    }

    const yesterdayStart = new Date(todayStart);
    yesterdayStart.setDate(yesterdayStart.getDate() - 1);

    if (irkDate >= yesterdayStart) {
        return `Вчера, ${irkTime}`;
    }

    const diffDays = Math.floor((todayStart - irkDate) / 86400000) + 1;
    if (diffDays <= 7) {
        return `${diffDays} ${pluralize(diffDays, 'день', 'дня', 'дней')}`;
    }

    return date.toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'long',
        timeZone: IRKUTSK_TZ,
    });
}

function pluralize(n, one, few, many) {
    const abs = Math.abs(n) % 100;
    const lastDigit = abs % 10;
    if (abs > 10 && abs < 20) return many;
    if (lastDigit === 1) return one;
    if (lastDigit >= 2 && lastDigit <= 4) return few;
    return many;
}

onMounted(() => {
    fetchTerminals();
    document.addEventListener('vendista:updated', fetchTerminals);
});

onBeforeUnmount(() => {
    document.removeEventListener('vendista:updated', fetchTerminals);
});
</script>
