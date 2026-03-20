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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Краткое название</label>
                        <input
                            v-model="newIngredient.short_name"
                            type="text"
                            maxlength="50"
                            placeholder="Для отображения при загрузке в аппарат"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Единица измерения</label>
                        <select
                            v-model="newIngredient.unit"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="u in unitOptions" :key="u" :value="u">{{ u }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Кол-во в таре</label>
                        <input
                            v-model.number="newIngredient.quantity_per_package"
                            type="number"
                            min="0.001"
                            step="any"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Сколько единиц в одной таре (упаковке), которую оператор носит на точку</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Кол-во в коробке</label>
                        <input
                            v-model.number="newIngredient.quantity_per_box"
                            type="number"
                            min="1"
                            step="1"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Сколько единиц в коробке при оптовой закупке. Пусто = не продаётся коробками</p>
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Цена за ед. при покупке коробкой</label>
                        <input
                            v-model.number="newIngredient.cost_per_unit_in_box"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Цена за 1 единицу при покупке коробкой (может быть дешевле поштучной)</p>
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
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Краткое название</label>
                                <input
                                    v-model="editForm.short_name"
                                    type="text"
                                    maxlength="50"
                                    placeholder="Для отображения при загрузке в аппарат"
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Единица измерения</label>
                                <select
                                    v-model="editForm.unit"
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option v-for="u in unitOptions" :key="u" :value="u">{{ u }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Кол-во в таре</label>
                                <input
                                    v-model.number="editForm.quantity_per_package"
                                    type="number"
                                    min="0.001"
                                    step="any"
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Кол-во в коробке</label>
                                <input
                                    v-model.number="editForm.quantity_per_box"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Пусто = не продаётся коробками</p>
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Цена за ед. при покупке коробкой</label>
                                <input
                                    v-model.number="editForm.cost_per_unit_in_box"
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
                    <div v-else>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ ingredient.name }}
                                    <span v-if="ingredient.short_name" class="ml-1 text-sm font-normal text-gray-400 dark:text-gray-500">({{ ingredient.short_name }})</span>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ ingredient.unit }}, {{ ingredient.quantity_per_package }} в таре
                                    <span class="ml-2">{{ formatCost(ingredient.cost_per_unit) }}</span>
                                </p>
                                <p v-if="ingredient.quantity_per_box" class="text-xs text-gray-400 dark:text-gray-500">
                                    Коробка: {{ ingredient.quantity_per_box }} шт
                                    <span v-if="ingredient.cost_per_unit_in_box" class="ml-1">/ {{ formatCost(ingredient.cost_per_unit_in_box) }} за ед.</span>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="startEdit(ingredient)"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-blue-500 active:bg-gray-200 dark:hover:bg-gray-700 dark:hover:text-blue-400 transition-colors"
                                    title="Редактировать"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                    </svg>
                                </button>
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

                        <!-- Остатки по складам -->
                        <div v-if="ingredient.warehouse_stocks?.length" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <span
                                v-for="(stock, idx) in ingredient.warehouse_stocks"
                                :key="stock.id"
                            >{{ stock.warehouse.name }}: <span class="font-medium text-gray-700 dark:text-gray-300">{{ stock.quantity }}</span><span v-if="idx < ingredient.warehouse_stocks.length - 1">, </span>
                            </span>
                            <span class="ml-2 font-medium text-gray-900 dark:text-white">| Всего: {{ totalStock(ingredient) }}</span>
                        </div>
                        <div v-else class="mt-2 text-sm text-gray-400 dark:text-gray-500">
                            Нет остатков
                        </div>

                        <!-- Кнопки действий -->
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                @click="openPurchase(ingredient)"
                                class="rounded border border-green-500 px-3 py-1 text-sm text-green-600 hover:bg-green-50 active:bg-green-100 dark:text-green-400 dark:hover:bg-green-900/30 dark:active:bg-green-900/50 transition-colors"
                            >
                                Оприходовать
                            </button>
                            <button
                                @click="openTransfer(ingredient)"
                                class="rounded border border-blue-500 px-3 py-1 text-sm text-blue-600 hover:bg-blue-50 active:bg-blue-100 dark:text-blue-400 dark:hover:bg-blue-900/30 dark:active:bg-blue-900/50 transition-colors"
                            >
                                Переместить
                            </button>
                            <button
                                @click="openWriteOff(ingredient)"
                                class="rounded border border-red-500 px-3 py-1 text-sm text-red-600 hover:bg-red-50 active:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 dark:active:bg-red-900/50 transition-colors"
                            >
                                Списать
                            </button>
                            <button
                                @click="goToHistory(ingredient)"
                                class="rounded border border-gray-300 px-3 py-1 text-sm text-gray-600 hover:bg-gray-50 active:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:active:bg-gray-600 transition-colors"
                            >
                                История
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

            <!-- Модалка покупки / оприходования -->
            <div v-if="purchaseIngredient" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closePurchase">
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Оприходование: {{ purchaseIngredient.name }}
                    </h3>

                    <form @submit.prevent="submitPurchase" class="mt-4 space-y-4">
                        <!-- Склад -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Склад</label>
                            <select
                                v-model.number="purchaseForm.warehouse_id"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>

                        <!-- Режим -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Режим</label>
                            <div class="mt-1 flex gap-4">
                                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input
                                        type="radio"
                                        v-model="purchaseForm.source"
                                        value="unit"
                                        class="text-blue-500"
                                    />
                                    Поштучно
                                </label>
                                <label
                                    class="flex items-center gap-2 text-sm"
                                    :class="purchaseIngredient.quantity_per_box
                                        ? 'text-gray-700 dark:text-gray-300'
                                        : 'text-gray-300 dark:text-gray-600'"
                                >
                                    <input
                                        type="radio"
                                        v-model="purchaseForm.source"
                                        value="box"
                                        :disabled="!purchaseIngredient.quantity_per_box"
                                        class="text-blue-500"
                                    />
                                    Коробками
                                </label>
                            </div>
                        </div>

                        <!-- Количество -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Количество, {{ purchaseForm.source === 'box' ? 'коробок' : purchaseIngredient.unit }}
                            </label>
                            <input
                                v-model.number="purchaseForm.quantity"
                                type="number"
                                min="1"
                                step="1"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Цена за единицу -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Цена за единицу</label>
                            <input
                                v-model.number="purchaseForm.cost_per_unit"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Итого -->
                        <div class="rounded bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-700/50 dark:text-gray-300">
                            <p v-if="purchaseForm.source === 'box' && purchaseIngredient.quantity_per_box">
                                {{ purchaseForm.quantity || 0 }} коробок
                                &times; {{ purchaseIngredient.quantity_per_box }} шт
                                = {{ purchaseTotalUnits }} шт,
                                сумма: {{ formatCost(purchaseTotalCost) }}
                            </p>
                            <p v-else>
                                {{ purchaseForm.quantity || 0 }} {{ purchaseIngredient.unit }},
                                сумма: {{ formatCost(purchaseTotalCost) }}
                            </p>
                        </div>

                        <!-- Комментарий -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Комментарий</label>
                            <textarea
                                v-model="purchaseForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button
                                type="button"
                                @click="closePurchase"
                                class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="rounded bg-green-500 px-4 py-2 text-white hover:bg-green-600 disabled:opacity-50 transition-colors"
                            >
                                {{ submitting ? 'Оприходование...' : 'Оприходовать' }}
                            </button>
                        </div>

                        <div v-if="modalError" class="rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                            {{ modalError }}
                        </div>
                    </form>
                </div>
            </div>

            <!-- Модалка перемещения -->
            <div v-if="transferIngredient" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeTransfer">
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Перемещение: {{ transferIngredient.name }}
                    </h3>

                    <form @submit.prevent="submitTransfer" class="mt-4 space-y-4">
                        <!-- Откуда -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Откуда</label>
                            <select
                                v-model.number="transferForm.from_warehouse_id"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>

                        <!-- Куда -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Куда</label>
                            <select
                                v-model.number="transferForm.to_warehouse_id"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option
                                    v-for="w in transferTargetWarehouses"
                                    :key="w.id"
                                    :value="w.id"
                                >{{ w.name }}</option>
                            </select>
                        </div>

                        <!-- Количество -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Количество, {{ transferIngredient.unit }}
                            </label>
                            <input
                                v-model.number="transferForm.quantity"
                                type="number"
                                min="1"
                                step="1"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Комментарий -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Комментарий</label>
                            <textarea
                                v-model="transferForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button
                                type="button"
                                @click="closeTransfer"
                                class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50 transition-colors"
                            >
                                {{ submitting ? 'Перемещение...' : 'Переместить' }}
                            </button>
                        </div>

                        <div v-if="modalError" class="rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                            {{ modalError }}
                        </div>
                    </form>
                </div>
            </div>

            <!-- Модалка списания -->
            <div v-if="writeOffIngredient" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="closeWriteOff">
                <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Списание: {{ writeOffIngredient.name }}
                    </h3>

                    <form @submit.prevent="submitWriteOff" class="mt-4 space-y-4">
                        <!-- Склад -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Склад</label>
                            <select
                                v-model.number="writeOffForm.warehouse_id"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                            </select>
                        </div>

                        <!-- Количество -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Количество, {{ writeOffIngredient.unit }}
                            </label>
                            <input
                                v-model.number="writeOffForm.quantity"
                                type="number"
                                min="1"
                                step="1"
                                required
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Причина -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Причина</label>
                            <input
                                v-model="writeOffForm.reason"
                                type="text"
                                required
                                maxlength="255"
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>

                        <!-- Комментарий -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Комментарий</label>
                            <textarea
                                v-model="writeOffForm.note"
                                rows="2"
                                maxlength="500"
                                class="mt-1 block w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            ></textarea>
                        </div>

                        <div class="flex gap-2 justify-end">
                            <button
                                type="button"
                                @click="closeWriteOff"
                                class="rounded bg-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-400 dark:bg-gray-600 dark:text-gray-200 transition-colors"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="rounded bg-red-500 px-4 py-2 text-white hover:bg-red-600 disabled:opacity-50 transition-colors"
                            >
                                {{ submitting ? 'Списание...' : 'Списать' }}
                            </button>
                        </div>

                        <div v-if="modalError" class="rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                            {{ modalError }}
                        </div>
                    </form>
                </div>
            </div>

            <!-- Уведомление об успешном действии -->
            <div
                v-if="successMessage"
                class="fixed bottom-4 left-1/2 z-50 -translate-x-1/2 rounded-lg bg-green-500 px-6 py-3 text-white shadow-lg"
            >
                {{ successMessage }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import apiClient from '@/api/client';

const router = useRouter();

const unitOptions = ['упаковка', 'штука', 'килограмм'];
const ingredients = ref([]);
const loading = ref(true);
const showCreateForm = ref(false);
const creating = ref(false);
const createError = ref('');
const saving = ref(false);
const editingId = ref(null);
const editForm = ref({ name: '', short_name: null, unit: '', quantity_per_package: 1, cost_per_unit: 0, quantity_per_box: null, cost_per_unit_in_box: null });
const editError = ref('');
const deletingIngredient = ref(null);
const deleting = ref(false);

const newIngredient = ref({
    name: '',
    short_name: null,
    unit: 'упаковка',
    quantity_per_package: 1,
    cost_per_unit: 0,
    quantity_per_box: null,
    cost_per_unit_in_box: null,
});

// Общие переменные для модалок действий
const warehouses = ref([]);
const submitting = ref(false);
const modalError = ref('');
const successMessage = ref('');
let successTimer = null;

// Покупка / оприходование
const purchaseIngredient = ref(null);
const purchaseForm = ref({
    warehouse_id: null,
    source: 'unit',
    quantity: 1,
    cost_per_unit: 0,
    note: '',
});

// Перемещение
const transferIngredient = ref(null);
const transferForm = ref({
    from_warehouse_id: null,
    to_warehouse_id: null,
    quantity: 1,
    note: '',
});

// Списание
const writeOffIngredient = ref(null);
const writeOffForm = ref({
    warehouse_id: null,
    quantity: 1,
    reason: '',
    note: '',
});

/** Количество единиц при покупке */
const purchaseTotalUnits = computed(() => {
    if (purchaseForm.value.source === 'box' && purchaseIngredient.value?.quantity_per_box) {
        return (purchaseForm.value.quantity || 0) * purchaseIngredient.value.quantity_per_box;
    }
    return purchaseForm.value.quantity || 0;
});

/** Общая стоимость покупки */
const purchaseTotalCost = computed(() => {
    return purchaseTotalUnits.value * (purchaseForm.value.cost_per_unit || 0);
});

/** Список складов-получателей (без склада-источника) для перемещения */
const transferTargetWarehouses = computed(() => {
    return warehouses.value.filter(w => w.id !== transferForm.value.from_warehouse_id);
});

/** Предзаполнение цены при смене режима покупки */
watch(() => purchaseForm.value.source, (source) => {
    if (!purchaseIngredient.value) return;
    if (source === 'box' && purchaseIngredient.value.cost_per_unit_in_box) {
        purchaseForm.value.cost_per_unit = purchaseIngredient.value.cost_per_unit_in_box;
    } else {
        purchaseForm.value.cost_per_unit = purchaseIngredient.value.cost_per_unit || 0;
    }
});

/** Сброс склада-получателя при смене склада-источника */
watch(() => transferForm.value.from_warehouse_id, () => {
    if (transferForm.value.to_warehouse_id === transferForm.value.from_warehouse_id) {
        transferForm.value.to_warehouse_id = null;
    }
});

/** Форматирование стоимости */
function formatCost(value) {
    return parseFloat(value || 0).toFixed(2) + ' \u20BD';
}

/** Ключ сортировки: короткое название, если есть, иначе полное */
function sortKey(ingredient) {
    return ingredient.short_name || ingredient.name;
}

/** Загрузка списка ингредиентов */
async function fetchIngredients() {
    try {
        const { data } = await apiClient.get('/admin/ingredients');
        ingredients.value = data.ingredients.sort((a, b) => sortKey(a).localeCompare(sortKey(b)));
    } finally {
        loading.value = false;
    }
}

/** Создание ингредиента */
async function createIngredient() {
    creating.value = true;
    createError.value = '';
    try {
        // Очищаем пустые значения полей коробки
        const payload = { ...newIngredient.value };
        if (!payload.quantity_per_box) payload.quantity_per_box = null;
        if (!payload.cost_per_unit_in_box) payload.cost_per_unit_in_box = null;

        const { data } = await apiClient.post('/admin/ingredients', payload);
        ingredients.value.push(data.ingredient);
        ingredients.value.sort((a, b) => sortKey(a).localeCompare(sortKey(b)));
        newIngredient.value = { name: '', short_name: null, unit: 'упаковка', cost_per_unit: 0, quantity_per_package: 1, quantity_per_box: null, cost_per_unit_in_box: null };
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
    newIngredient.value = { name: '', unit: 'упаковка', cost_per_unit: 0, quantity_per_package: 1, quantity_per_box: null, cost_per_unit_in_box: null };
}

/** Начало редактирования */
function startEdit(ingredient) {
    editingId.value = ingredient.id;
    editForm.value = {
        name: ingredient.name,
        short_name: ingredient.short_name,
        unit: ingredient.unit,
        quantity_per_package: ingredient.quantity_per_package,
        cost_per_unit: ingredient.cost_per_unit,
        quantity_per_box: ingredient.quantity_per_box,
        cost_per_unit_in_box: ingredient.cost_per_unit_in_box,
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
        // Очищаем пустые значения полей коробки
        const payload = { ...editForm.value };
        if (!payload.quantity_per_box) payload.quantity_per_box = null;
        if (!payload.cost_per_unit_in_box) payload.cost_per_unit_in_box = null;

        const { data } = await apiClient.put(`/admin/ingredients/${ingredient.id}`, payload);
        Object.assign(ingredient, data.ingredient);
        ingredients.value.sort((a, b) => sortKey(a).localeCompare(sortKey(b)));
        editingId.value = null;
    } catch (error) {
        editError.value = error.response?.data?.message || 'Не удалось сохранить изменения';
    } finally {
        saving.value = false;
    }
}

/** Подсчёт общего остатка */
function totalStock(ingredient) {
    return (ingredient.warehouse_stocks || []).reduce((sum, s) => sum + s.quantity, 0);
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

// --- Загрузка складов (общая для всех модалок) ---

/** Загрузка списка складов и установка склада по умолчанию */
async function loadWarehouses() {
    try {
        const { data } = await apiClient.get('/admin/warehouses');
        warehouses.value = data.warehouses;
    } catch {
        modalError.value = 'Не удалось загрузить список складов';
    }
}

/** Определение склада по умолчанию */
function getDefaultWarehouseId() {
    const defaultWarehouse = warehouses.value.find(w => w.is_default);
    if (defaultWarehouse) return defaultWarehouse.id;
    if (warehouses.value.length) return warehouses.value[0].id;
    return null;
}

// --- Покупка / оприходование ---

/** Открытие модалки покупки */
async function openPurchase(ingredient) {
    purchaseIngredient.value = ingredient;
    modalError.value = '';
    purchaseForm.value = {
        warehouse_id: null,
        source: 'unit',
        quantity: 1,
        cost_per_unit: ingredient.cost_per_unit || 0,
        note: '',
    };

    await loadWarehouses();
    purchaseForm.value.warehouse_id = getDefaultWarehouseId();
}

/** Закрытие модалки покупки */
function closePurchase() {
    purchaseIngredient.value = null;
    modalError.value = '';
}

/** Отправка покупки */
async function submitPurchase() {
    submitting.value = true;
    modalError.value = '';
    try {
        await apiClient.post(`/admin/ingredients/${purchaseIngredient.value.id}/purchase`, {
            warehouse_id: purchaseForm.value.warehouse_id,
            quantity: purchaseForm.value.quantity,
            cost_per_unit: purchaseForm.value.cost_per_unit,
            source: purchaseForm.value.source,
            note: purchaseForm.value.note || null,
        });

        closePurchase();
        await fetchIngredients();
        showSuccess('Ингредиент успешно оприходован');
    } catch (error) {
        modalError.value = error.response?.data?.message || 'Не удалось оприходовать ингредиент';
    } finally {
        submitting.value = false;
    }
}

// --- Перемещение ---

/** Открытие модалки перемещения */
async function openTransfer(ingredient) {
    transferIngredient.value = ingredient;
    modalError.value = '';
    transferForm.value = {
        from_warehouse_id: null,
        to_warehouse_id: null,
        quantity: 1,
        note: '',
    };

    await loadWarehouses();
    transferForm.value.from_warehouse_id = getDefaultWarehouseId();
}

/** Закрытие модалки перемещения */
function closeTransfer() {
    transferIngredient.value = null;
    modalError.value = '';
}

/** Отправка перемещения */
async function submitTransfer() {
    submitting.value = true;
    modalError.value = '';
    try {
        await apiClient.post(`/admin/ingredients/${transferIngredient.value.id}/transfer`, {
            from_warehouse_id: transferForm.value.from_warehouse_id,
            to_warehouse_id: transferForm.value.to_warehouse_id,
            quantity: transferForm.value.quantity,
            note: transferForm.value.note || null,
        });

        closeTransfer();
        await fetchIngredients();
        showSuccess('Ингредиент успешно перемещён');
    } catch (error) {
        modalError.value = error.response?.data?.message || 'Не удалось переместить ингредиент';
    } finally {
        submitting.value = false;
    }
}

// --- Списание ---

/** Открытие модалки списания */
async function openWriteOff(ingredient) {
    writeOffIngredient.value = ingredient;
    modalError.value = '';
    writeOffForm.value = {
        warehouse_id: null,
        quantity: 1,
        reason: '',
        note: '',
    };

    await loadWarehouses();
    writeOffForm.value.warehouse_id = getDefaultWarehouseId();
}

/** Закрытие модалки списания */
function closeWriteOff() {
    writeOffIngredient.value = null;
    modalError.value = '';
}

/** Отправка списания */
async function submitWriteOff() {
    submitting.value = true;
    modalError.value = '';
    try {
        await apiClient.post(`/admin/ingredients/${writeOffIngredient.value.id}/write-off`, {
            warehouse_id: writeOffForm.value.warehouse_id,
            quantity: writeOffForm.value.quantity,
            reason: writeOffForm.value.reason,
            note: writeOffForm.value.note || null,
        });

        closeWriteOff();
        await fetchIngredients();
        showSuccess('Ингредиент успешно списан');
    } catch (error) {
        modalError.value = error.response?.data?.message || 'Не удалось списать ингредиент';
    } finally {
        submitting.value = false;
    }
}

// --- История ---

/** Переход на страницу истории */
function goToHistory(ingredient) {
    router.push({ name: 'admin-ingredient-history', params: { id: ingredient.id } });
}

// --- Уведомления ---

/** Показать уведомление об успешном действии */
function showSuccess(message) {
    successMessage.value = message;
    if (successTimer) clearTimeout(successTimer);
    successTimer = setTimeout(() => {
        successMessage.value = '';
    }, 3000);
}

onMounted(() => {
    fetchIngredients();
});

onBeforeUnmount(() => {
    if (successTimer) clearTimeout(successTimer);
});
</script>
