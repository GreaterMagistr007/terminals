<template>
    <div class="flex min-h-screen flex-col bg-gray-50 dark:bg-gray-950">
        <!-- Тост-уведомление -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-[-100%] opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-[-100%] opacity-0"
        >
            <div v-if="toast.visible" class="fixed inset-x-0 top-0 z-50 px-4 pt-4">
                <div
                    class="rounded-xl px-4 py-3 text-sm font-medium shadow-lg"
                    :class="toast.type === 'success'
                        ? 'bg-green-500 text-white'
                        : 'bg-red-500 text-white'"
                >
                    {{ toast.message }}
                </div>
            </div>
        </Transition>

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
            <!-- Блок "Нужно принести" с прошлого визита -->
            <div v-if="neededItems.length && !neededDismissed"
                class="mb-4 flex items-start gap-2 rounded-xl bg-amber-50 p-3 dark:bg-amber-900/20"
            >
                <svg class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-amber-700 dark:text-amber-400">Нужно принести:</p>
                    <p class="mt-0.5 text-xs text-amber-600 dark:text-amber-300">{{ neededItemsText }}</p>
                </div>
                <button @click="neededDismissed = true" class="shrink-0 p-0.5 text-amber-400 active:text-amber-600 dark:text-amber-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Дата и время обслуживания (шаг 1 и шаг 4) -->
            <div v-if="currentStep === 1 || currentStep === totalSteps" class="mb-5 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900">
                <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Дата и время обслуживания</label>
                <input
                    type="datetime-local"
                    v-model="visitedAt"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-400 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-blue-500"
                />
            </div>

            <!-- Шаг 1: Вода -->
            <div v-if="currentStep === 1">
                <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Уровень воды</h2>
                <p class="mb-4 text-sm text-gray-400 dark:text-gray-500">Укажите наполненность бутылей</p>

                <!-- Кнопки быстрых действий -->
                <div class="mb-4 flex gap-2">
                    <button
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-600 active:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:active:bg-gray-800 transition-colors"
                        @click="swapWater"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                        Поменять
                    </button>
                    <button
                        class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5 text-sm font-medium text-blue-600 active:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 dark:active:bg-blue-900/40 transition-colors"
                        @click="fillAllWater"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Заполнить все
                    </button>
                </div>

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

                <p v-if="!ingredients.length" class="text-sm text-gray-400 dark:text-gray-500">
                    Для этой точки ингредиенты не назначены. Настройте их в админке.
                </p>
                <div v-else class="space-y-3">
                    <div v-for="ing in ingredients" :key="ing.id" class="rounded-2xl bg-white shadow-sm dark:bg-gray-900 overflow-hidden">
                        <!-- Заголовок карточки (всегда виден) -->
                        <button
                            class="flex w-full items-center gap-3 p-4 text-left active:bg-gray-50 dark:active:bg-gray-800 transition-colors"
                            @click="ing.expanded = !ing.expanded"
                        >
                            <span class="flex-1 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ ing.name }}</span>
                            <!-- Бейджи со значениями, если есть (видны в свёрнутом состоянии) -->
                            <span v-if="!ing.expanded && (ing.brought || ing.needed)" class="flex items-center gap-1.5 text-xs">
                                <span v-if="ing.brought" class="rounded-md bg-green-100 px-1.5 py-0.5 font-medium text-green-700 dark:bg-green-900/40 dark:text-green-400">+{{ ing.brought }}</span>
                                <span v-if="ing.needed" class="rounded-md bg-orange-100 px-1.5 py-0.5 font-medium text-orange-700 dark:bg-orange-900/40 dark:text-orange-400">{{ ing.needed }}</span>
                            </span>
                            <svg
                                class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                                :class="{ 'rotate-180': ing.expanded }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                        <!-- Раскрывающееся содержимое -->
                        <div v-if="ing.expanded" class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-gray-800">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex-1">
                                    <p class="mb-2 text-center text-xs text-gray-400 dark:text-gray-500">Принёс</p>
                                    <div class="flex items-center justify-center gap-2">
                                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300" @click.stop="ing.brought = Math.max(0, ing.brought - 1)">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                        </button>
                                        <span class="w-8 text-center text-lg font-bold text-gray-900 dark:text-white">{{ ing.brought }}</span>
                                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 text-green-700 active:bg-green-200 dark:bg-green-900/40 dark:text-green-400" @click.stop="ing.brought++">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="h-12 w-px bg-gray-100 dark:bg-gray-800"></div>
                                <div class="flex-1">
                                    <p class="mb-2 text-center text-xs text-gray-400 dark:text-gray-500">Нужно</p>
                                    <div class="flex items-center justify-center gap-2">
                                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-gray-600 active:bg-gray-200 dark:bg-gray-800 dark:text-gray-300" @click.stop="ing.needed = Math.max(0, ing.needed - 1)">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                                        </button>
                                        <span class="w-8 text-center text-lg font-bold text-gray-900 dark:text-white">{{ ing.needed }}</span>
                                        <button class="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-700 active:bg-orange-200 dark:bg-orange-900/40 dark:text-orange-400" @click.stop="ing.needed++">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        </button>
                                    </div>
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

                <!-- Прикреплённые фото к комментарию -->
                <div class="mt-6">
                    <p class="mb-3 text-xs font-semibold text-gray-400 uppercase tracking-wider dark:text-gray-500">Прикрепить фото</p>
                    <div v-if="commentPhotoPreviews.length" class="mb-3 grid grid-cols-2 gap-2">
                        <div v-for="(preview, idx) in commentPhotoPreviews" :key="idx" class="group relative">
                            <img :src="preview" class="h-32 w-full rounded-xl object-cover" alt="Фото" />
                            <button
                                @click="removeCommentPhoto(idx)"
                                class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/50 text-white active:bg-black/70"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button
                        @click="commentPhotoInput.click()"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-gray-200 bg-white py-3 text-sm font-medium text-gray-500 active:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:active:bg-gray-800"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Добавить фото
                    </button>
                    <input
                        ref="commentPhotoInput"
                        type="file"
                        accept="image/*"
                        multiple
                        class="hidden"
                        @change="onCommentPhotosSelected"
                    />
                </div>
            </div>

            <!-- Шаг 4: Фото -->
            <div v-if="currentStep === 4">
                <h2 class="mb-1 text-lg font-bold text-gray-900 dark:text-white">Фотоотчёт</h2>
                <p class="mb-5 text-sm text-gray-400 dark:text-gray-500">Сделайте 2 фото аппарата</p>

                <div class="space-y-4">
                    <!-- Фото внутри -->
                    <div
                        class="group relative flex h-52 flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-white transition-colors dark:bg-gray-900 overflow-hidden"
                        :class="photos.inside ? 'border-green-300 dark:border-green-700' : 'border-gray-200 dark:border-gray-700'"
                    >
                        <template v-if="!photos.inside">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-3" @click="photoInsideInput.click()">
                                <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400" @click="photoInsideInput.click()">Фото внутри</span>
                            <span class="mt-1 text-xs text-gray-400 dark:text-gray-500" @click="photoInsideInput.click()">Нажмите для съёмки</span>
                        </template>
                        <template v-else>
                            <img :src="photoInsidePreview" class="h-full w-full object-cover" alt="Фото внутри" />
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/60 to-transparent px-3 pb-2 pt-6">
                                <span class="text-xs font-medium text-white">Фото внутри</span>
                                <button
                                    @click="retakePhoto('inside')"
                                    class="rounded-lg bg-white/20 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-sm active:bg-white/30"
                                >Переснять</button>
                            </div>
                        </template>
                        <input
                            ref="photoInsideInput"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            class="hidden"
                            @change="onPhotoSelected($event, 'inside')"
                        />
                    </div>

                    <!-- Фото снаружи -->
                    <div
                        class="group relative flex h-52 flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-white transition-colors dark:bg-gray-900 overflow-hidden"
                        :class="photos.outside ? 'border-green-300 dark:border-green-700' : 'border-gray-200 dark:border-gray-700'"
                    >
                        <template v-if="!photos.outside">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-3" @click="photoOutsideInput.click()">
                                <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400" @click="photoOutsideInput.click()">Фото снаружи</span>
                            <span class="mt-1 text-xs text-gray-400 dark:text-gray-500" @click="photoOutsideInput.click()">Нажмите для съёмки</span>
                        </template>
                        <template v-else>
                            <img :src="photoOutsidePreview" class="h-full w-full object-cover" alt="Фото снаружи" />
                            <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-black/60 to-transparent px-3 pb-2 pt-6">
                                <span class="text-xs font-medium text-white">Фото снаружи</span>
                                <button
                                    @click="retakePhoto('outside')"
                                    class="rounded-lg bg-white/20 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-sm active:bg-white/30"
                                >Переснять</button>
                            </div>
                        </template>
                        <input
                            ref="photoOutsideInput"
                            type="file"
                            accept="image/*"
                            capture="environment"
                            class="hidden"
                            @change="onPhotoSelected($event, 'outside')"
                        />
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
                class="w-full rounded-xl py-3.5 text-sm font-bold text-white shadow-lg transition-all active:scale-[0.98] disabled:opacity-60 disabled:active:scale-100"
                :class="currentStep === totalSteps ? 'bg-green-500 active:bg-green-600 shadow-green-500/25' : 'bg-blue-500 active:bg-blue-600 shadow-blue-500/25'"
                :disabled="saving"
                @click="nextStep"
            >
                <template v-if="saving">
                    <svg class="inline-block h-4 w-4 animate-spin mr-1.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Сохранение...
                </template>
                <template v-else>
                    {{ currentStep === totalSteps ? 'Сохранить' : 'Далее' }}
                </template>
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
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import apiClient from '@/api/client';

const router = useRouter();
const route = useRoute();

const terminal = ref(null);
const currentStep = ref(1);
const totalSteps = 4;

/** Текущее время по Иркутску в формате datetime-local (YYYY-MM-DDTHH:MM) */
function nowIrkutsk() {
    const now = new Date();
    const parts = new Intl.DateTimeFormat('sv-SE', {
        timeZone: 'Asia/Irkutsk',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit',
    }).formatToParts(now);
    const get = (type) => parts.find(p => p.type === type)?.value || '';
    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}`;
}

const visitedAt = ref(nowIrkutsk());

const water = reactive({ main: 0.5, spare: 0.0 });

const ingredients = ref([]);

const comment = ref('');
const isRecording = ref(false);
const templates = ['Всё в норме', 'Требуется ремонт', 'Протечка воды', 'Нужна чистка', 'Аппарат отключён'];
const showExitModal = ref(false);
const neededDismissed = ref(false);
const saving = ref(false);

// Геолокация
const coords = ref({ latitude: null, longitude: null });

// Фото аппарата (inside/outside)
const photos = reactive({ inside: null, outside: null });
const photoInsidePreview = ref(null);
const photoOutsidePreview = ref(null);

// Template refs для file inputs
const photoInsideInput = ref(null);
const photoOutsideInput = ref(null);
const commentPhotoInput = ref(null);

// Фото к комментарию
const commentPhotos = ref([]);
const commentPhotoPreviews = ref([]);

// Тост
const toast = reactive({ visible: false, type: 'success', message: '' });
let toastTimer = null;

function showToast(type, message) {
    toast.visible = true;
    toast.type = type;
    toast.message = message;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toast.visible = false;
    }, 3000);
}

// Нужные ингредиенты из последнего визита
const neededItems = ref([]);

const neededItemsText = computed(() => {
    return neededItems.value
        .map(i => i.qty > 1 ? `${i.name.toLowerCase()} ${i.qty}` : i.name.toLowerCase())
        .join(', ');
});

async function fetchTerminal() {
    try {
        const { data } = await apiClient.get(`/terminals/${route.params.id}`);
        terminal.value = data.terminal;

        // Инициализация ингредиентов из привязанных к точке (порядок из sort_order)
        if (data.terminal.ingredients?.length) {
            ingredients.value = data.terminal.ingredients.map((ing) => ({
                id: ing.id,
                name: ing.short_name || ing.name,
                brought: 0,
                needed: 0,
                expanded: false,
            }));
        }

        // Нужные ингредиенты из последнего визита
        if (data.terminal.latest_visit?.ingredients?.length) {
            neededItems.value = data.terminal.latest_visit.ingredients
                .filter(i => i.needed > 0)
                .map(i => ({
                    name: i.ingredient?.short_name || i.ingredient?.name || 'Ингредиент',
                    qty: i.needed,
                }));
        }
    } catch {
        router.replace('/');
    }
}

function swapWater() {
    const temp = water.main;
    water.main = water.spare;
    water.spare = temp;
}

function fillAllWater() {
    water.main = 1;
    water.spare = 1;
}

function goBack() {
    if (currentStep.value > 1) {
        currentStep.value--;
    } else {
        showExitModal.value = true;
    }
}

// Фото: выбор файла
function onPhotoSelected(event, type) {
    const file = event.target.files?.[0];
    if (!file) return;
    photos[type] = file;
    const url = URL.createObjectURL(file);
    if (type === 'inside') {
        // Освобождаем предыдущий URL
        if (photoInsidePreview.value) URL.revokeObjectURL(photoInsidePreview.value);
        photoInsidePreview.value = url;
    } else {
        if (photoOutsidePreview.value) URL.revokeObjectURL(photoOutsidePreview.value);
        photoOutsidePreview.value = url;
    }
}

// Фото: переснять
function retakePhoto(type) {
    photos[type] = null;
    if (type === 'inside') {
        if (photoInsidePreview.value) URL.revokeObjectURL(photoInsidePreview.value);
        photoInsidePreview.value = null;
        if (photoInsideInput.value) photoInsideInput.value.value = '';
    } else {
        if (photoOutsidePreview.value) URL.revokeObjectURL(photoOutsidePreview.value);
        photoOutsidePreview.value = null;
        if (photoOutsideInput.value) photoOutsideInput.value.value = '';
    }
}

// Фото к комментарию: выбор
function onCommentPhotosSelected(event) {
    const files = event.target.files;
    if (!files?.length) return;
    for (const file of files) {
        commentPhotos.value.push(file);
        commentPhotoPreviews.value.push(URL.createObjectURL(file));
    }
    // Сброс input для повторного выбора
    event.target.value = '';
}

// Фото к комментарию: удаление
function removeCommentPhoto(idx) {
    URL.revokeObjectURL(commentPhotoPreviews.value[idx]);
    commentPhotos.value.splice(idx, 1);
    commentPhotoPreviews.value.splice(idx, 1);
}

// Запрос геолокации
function requestGeolocation() {
    if (!navigator.geolocation) return;
    navigator.geolocation.getCurrentPosition(
        (position) => {
            coords.value.latitude = position.coords.latitude;
            coords.value.longitude = position.coords.longitude;
        },
        () => {
            // Геолокация недоступна или запрещена -- не блокируем работу
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

// Сохранение визита
async function saveVisit() {
    saving.value = true;
    try {
        const formData = new FormData();
        formData.append('terminal_id', route.params.id);
        formData.append('visited_at', visitedAt.value);
        formData.append('water_main', water.main);
        formData.append('water_spare', water.spare);
        formData.append('comment', comment.value);

        if (coords.value.latitude !== null) {
            formData.append('latitude', coords.value.latitude);
        }
        if (coords.value.longitude !== null) {
            formData.append('longitude', coords.value.longitude);
        }

        if (photos.inside) {
            formData.append('photo_inside', photos.inside);
        }
        if (photos.outside) {
            formData.append('photo_outside', photos.outside);
        }

        for (const file of commentPhotos.value) {
            formData.append('comment_photos[]', file);
        }

        // Ингредиенты: только где brought > 0 или needed > 0
        const ingredientsData = ingredients.value
            .filter(ing => ing.brought > 0 || ing.needed > 0)
            .map(ing => ({
                ingredient_id: ing.id,
                brought: ing.brought,
                needed: ing.needed,
            }));
        formData.append('ingredients', JSON.stringify(ingredientsData));

        await apiClient.post('/service-visits', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        showToast('success', 'Визит сохранён');
        setTimeout(() => {
            router.push('/');
        }, 3000);
    } catch (error) {
        const message = error.response?.data?.message
            || error.response?.data?.errors && Object.values(error.response.data.errors).flat().join(', ')
            || 'Ошибка при сохранении';
        showToast('error', message);
    } finally {
        saving.value = false;
    }
}

function nextStep() {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    } else {
        saveVisit();
    }
}

// Освобождение Object URL при размонтировании
onBeforeUnmount(() => {
    if (photoInsidePreview.value) URL.revokeObjectURL(photoInsidePreview.value);
    if (photoOutsidePreview.value) URL.revokeObjectURL(photoOutsidePreview.value);
    commentPhotoPreviews.value.forEach(url => URL.revokeObjectURL(url));
    clearTimeout(toastTimer);
});

onMounted(() => {
    fetchTerminal();
    requestGeolocation();
});
</script>
