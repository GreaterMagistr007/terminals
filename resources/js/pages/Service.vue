<template>
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Шапка -->
        <header class="fixed inset-x-0 top-0 z-10 flex items-center gap-3 bg-white px-4 py-3 shadow-sm dark:bg-gray-900">
            <button
                class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:active:bg-gray-700"
                @click="goBack"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </button>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-gray-900 truncate dark:text-white">{{ terminal?.comment || 'Загрузка...' }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Обслуживание</p>
            </div>
            <span class="shrink-0 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                Шаг {{ currentStep }} / {{ totalSteps }}
            </span>
        </header>

        <!-- Контент шагов -->
        <main class="flex-1 overflow-y-auto px-4 pt-16 pb-32">
            <!-- Шаг 1: Вода -->
            <div v-if="currentStep === 1">
                <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Уровень воды</h2>
                <p class="mb-5 text-sm text-gray-400 dark:text-gray-500">Укажите наполненность бутылей</p>

                <!-- Основная бутыль -->
                <div class="mb-6 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Основная бутыль</span>
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ water.main.toFixed(1) }}</span>
                    </div>
                    <div class="relative mx-auto mb-4 h-32 w-16">
                        <div class="absolute inset-x-0 bottom-0 h-28 rounded-b-xl rounded-t-md border-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                            <div class="absolute inset-x-0 bottom-0 rounded-b-lg bg-blue-400/30 transition-all duration-300 dark:bg-blue-500/30" :style="{ height: (water.main * 100) + '%' }">
                                <div class="absolute inset-x-0 top-0 h-1 bg-blue-400 dark:bg-blue-500"></div>
                            </div>
                        </div>
                        <div class="absolute inset-x-3 top-0 h-5 rounded-t-md border-2 border-b-0 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"></div>
                    </div>
                    <input type="range" min="0" max="1" step="0.1" v-model.number="water.main" class="w-full accent-blue-500" />
                    <div class="mt-1 flex justify-between text-xs text-gray-400 dark:text-gray-500">
                        <span>Пусто</span><span>Полная</span>
                    </div>
                </div>

                <!-- Запасная бутыль -->
                <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Запасная бутыль</span>
                        <span class="text-sm font-bold text-cyan-600 dark:text-cyan-400">{{ water.spare.toFixed(1) }}</span>
                    </div>
                    <div class="relative mx-auto mb-4 h-32 w-16">
                        <div class="absolute inset-x-0 bottom-0 h-28 rounded-b-xl rounded-t-md border-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                            <div class="absolute inset-x-0 bottom-0 rounded-b-lg bg-cyan-400/30 transition-all duration-300 dark:bg-cyan-500/30" :style="{ height: (water.spare * 100) + '%' }">
                                <div class="absolute inset-x-0 top-0 h-1 bg-cyan-400 dark:bg-cyan-500"></div>
                            </div>
                        </div>
                        <div class="absolute inset-x-3 top-0 h-5 rounded-t-md border-2 border-b-0 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800"></div>
                    </div>
                    <input type="range" min="0" max="1" step="0.1" v-model.number="water.spare" class="w-full accent-cyan-500" />
                    <div class="mt-1 flex justify-between text-xs text-gray-400 dark:text-gray-500">
                        <span>Пусто</span><span>Полная</span>
                    </div>
                </div>
            </div>

            <!-- Шаг 2: Ингредиенты -->
            <div v-if="currentStep === 2">
                <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Ингредиенты</h2>
                <p class="mb-5 text-sm text-gray-400 dark:text-gray-500">Укажите количество принесённых и нужных</p>

                <div class="space-y-3">
                    <div v-for="ing in ingredients" :key="ing.name" class="rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                        <div class="mb-3 flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl" :class="ing.bgClass">
                                <span class="text-base">{{ ing.icon }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ ing.name }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-1">
                                <p class="mb-2 text-center text-xs text-gray-400 dark:text-gray-500">Принёс</p>
                                <div class="flex items-center justify-center gap-2">
                                    <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300" @click="ing.brought = Math.max(0, ing.brought - 1)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                    </button>
                                    <span class="w-8 text-center text-lg font-bold text-gray-900 dark:text-white">{{ ing.brought }}</span>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-700 active:bg-green-200 dark:bg-green-900/40 dark:text-green-400" @click="ing.brought++">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="h-12 w-px bg-gray-100 dark:bg-gray-800"></div>
                            <div class="flex-1">
                                <p class="mb-2 text-center text-xs text-gray-400 dark:text-gray-500">Нужно</p>
                                <div class="flex items-center justify-center gap-2">
                                    <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300" @click="ing.needed = Math.max(0, ing.needed - 1)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                    </button>
                                    <span class="w-8 text-center text-lg font-bold text-gray-900 dark:text-white">{{ ing.needed }}</span>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-700 active:bg-orange-200 dark:bg-orange-900/40 dark:text-orange-400" @click="ing.needed++">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Шаг 3: Комментарий -->
            <div v-if="currentStep === 3">
                <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Комментарий</h2>
                <p class="mb-5 text-sm text-gray-400 dark:text-gray-500">Опишите состояние аппарата или проблемы</p>

                <div class="rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                    <div class="relative">
                        <textarea v-model="comment" rows="6" placeholder="Введите комментарий..."
                            class="w-full resize-none rounded-xl border border-gray-200 bg-gray-50 p-3 pr-12 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-400 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800"
                        ></textarea>
                        <button
                            class="absolute right-3 bottom-3 flex h-10 w-10 items-center justify-center rounded-full bg-blue-500 text-white shadow-md active:bg-blue-600 transition-colors"
                            :class="{ 'animate-pulse bg-red-500 active:bg-red-600': isRecording }"
                            @click="isRecording = !isRecording"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                            </svg>
                        </button>
                    </div>
                    <p v-if="isRecording" class="mt-2 text-xs text-red-500 font-medium animate-pulse">Запись голоса...</p>
                    <p v-else class="mt-2 text-xs text-gray-400 dark:text-gray-500">Нажмите на микрофон для голосового ввода</p>
                </div>

                <div class="mt-4">
                    <p class="mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wider dark:text-gray-500">Шаблоны</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="tmpl in templates" :key="tmpl"
                            class="rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-600 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:active:bg-gray-800 transition-colors"
                            @click="comment += (comment ? ' ' : '') + tmpl"
                        >{{ tmpl }}</button>
                    </div>
                </div>
            </div>

            <!-- Шаг 4: Фото -->
            <div v-if="currentStep === 4">
                <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Фотоотчёт</h2>
                <p class="mb-5 text-sm text-gray-400 dark:text-gray-500">Сделайте 2 фото аппарата</p>

                <div class="space-y-4">
                    <div
                        class="group relative flex h-52 flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-white transition-colors dark:bg-gray-900"
                        :class="photos.inside ? 'border-green-300 dark:border-green-700' : 'border-gray-200 dark:border-gray-700'"
                        @click="photos.inside = !photos.inside"
                    >
                        <template v-if="!photos.inside">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-3">
                                <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Фото внутри</span>
                            <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Нажмите для съёмки</span>
                        </template>
                        <template v-else>
                            <div class="flex h-full w-full items-center justify-center rounded-2xl bg-green-50 dark:bg-green-900/20">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">Фото внутри загружено</p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div
                        class="group relative flex h-52 flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-white transition-colors dark:bg-gray-900"
                        :class="photos.outside ? 'border-green-300 dark:border-green-700' : 'border-gray-200 dark:border-gray-700'"
                        @click="photos.outside = !photos.outside"
                    >
                        <template v-if="!photos.outside">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-3">
                                <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Фото снаружи</span>
                            <span class="mt-1 text-xs text-gray-400 dark:text-gray-500">Нажмите для съёмки</span>
                        </template>
                        <template v-else>
                            <div class="flex h-full w-full items-center justify-center rounded-2xl bg-green-50 dark:bg-green-900/20">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">Фото снаружи загружено</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>

        <!-- Нижняя панель -->
        <div class="fixed inset-x-0 bottom-0 border-t border-gray-200 bg-white px-4 pb-6 pt-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-center gap-2">
                <button v-for="step in totalSteps" :key="step"
                    class="h-2 rounded-full transition-all duration-300"
                    :class="step === currentStep ? 'w-8 bg-blue-500' : step < currentStep ? 'w-2 bg-blue-300 dark:bg-blue-700' : 'w-2 bg-gray-200 dark:bg-gray-700'"
                    @click="currentStep = step"
                ></button>
            </div>
            <button
                class="w-full rounded-xl py-3.5 text-sm font-bold text-white shadow-lg transition-all active:scale-[0.98]"
                :class="currentStep === totalSteps ? 'bg-green-500 active:bg-green-600 shadow-green-500/25' : 'bg-blue-500 active:bg-blue-600 shadow-blue-500/25'"
                @click="nextStep"
            >
                {{ currentStep === totalSteps ? 'Сохранить' : 'Далее' }}
            </button>
        </div>

        <!-- Модальное окно подтверждения выхода -->
        <div v-if="showExitModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showExitModal = false">
            <div class="mx-4 w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Прекратить обслуживание?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Данные не сохранятся</p>
                <div class="mt-5 flex gap-3">
                    <button
                        @click="showExitModal = false"
                        class="flex-1 rounded-xl bg-gray-100 py-2.5 text-sm font-semibold text-gray-700 active:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:active:bg-gray-600"
                    >Остаться</button>
                    <button
                        @click="router.push('/')"
                        class="flex-1 rounded-xl bg-red-500 py-2.5 text-sm font-semibold text-white active:bg-red-600"
                    >Выйти</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/client';

const router = useRouter();
const route = useRoute();

const terminal = ref(null);
const currentStep = ref(1);
const totalSteps = 4;

const water = reactive({ main: 0.5, spare: 0.0 });

// TODO: загружать из справочника ингредиентов (Этап 2)
const ingredients = reactive([
    { name: 'Кофе', icon: '\u2615', bgClass: 'bg-amber-100 dark:bg-amber-900/30', brought: 0, needed: 0 },
    { name: 'Молоко', icon: '\uD83E\uDD5B', bgClass: 'bg-blue-100 dark:bg-blue-900/30', brought: 0, needed: 0 },
    { name: 'Сахар', icon: '\uD83E\uDDC2', bgClass: 'bg-yellow-100 dark:bg-yellow-900/30', brought: 0, needed: 0 },
    { name: 'Шоколад', icon: '\uD83C\uDF6B', bgClass: 'bg-orange-100 dark:bg-orange-900/30', brought: 0, needed: 0 },
    { name: 'Стаканы', icon: '\uD83E\uDD64', bgClass: 'bg-gray-100 dark:bg-gray-800', brought: 0, needed: 0 },
    { name: 'Крышки', icon: '\u26AB', bgClass: 'bg-gray-100 dark:bg-gray-800', brought: 0, needed: 0 },
    { name: 'Палочки', icon: '\uD83E\uDD62', bgClass: 'bg-green-100 dark:bg-green-900/30', brought: 0, needed: 0 },
]);

const comment = ref('');
const isRecording = ref(false);
const templates = ['Всё в норме', 'Требуется ремонт', 'Протечка воды', 'Нужна чистка', 'Аппарат отключён'];
const photos = reactive({ inside: false, outside: false });
const showExitModal = ref(false);

async function fetchTerminal() {
    try {
        const { data } = await apiClient.get(`/terminals/${route.params.id}`);
        terminal.value = data.terminal;
    } catch {
        router.replace('/');
    }
}

function goBack() {
    if (currentStep.value > 1) {
        currentStep.value--;
    } else {
        showExitModal.value = true;
    }
}

function nextStep() {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    } else {
        // TODO: отправка данных на сервер (Этап 3)
        router.push('/');
    }
}

onMounted(fetchTerminal);
</script>
