<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Хедер -->
        <div class="sticky top-0 z-10 flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <router-link
                :to="{ name: 'admin-points' }"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 active:bg-gray-200 dark:text-gray-400 dark:hover:bg-gray-800"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
            </router-link>
            <h1 class="truncate text-lg font-bold text-gray-900 dark:text-white">
                {{ terminal?.comment || 'Загрузка...' }}
            </h1>
        </div>

        <!-- Индикатор загрузки -->
        <div v-if="loading" class="mt-8 text-center text-gray-500 dark:text-gray-400">
            Загрузка...
        </div>

        <!-- Настройки -->
        <div v-else-if="terminal" class="mx-auto max-w-2xl space-y-4 p-4">
            <!-- Переключатель: скрыть из списка -->
            <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Скрыть из списка оператора</p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Терминал не будет отображаться у операторов</p>
                </div>
                <button
                    @click="toggleHidden"
                    :disabled="saving"
                    class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 focus:outline-none"
                    :class="settings.hidden ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'"
                    role="switch"
                    :aria-checked="settings.hidden"
                >
                    <span
                        class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                        :class="settings.hidden ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>

            <!-- Переключатель: использует воду -->
            <div class="flex items-center justify-between rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">Использует воду</p>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Включить учёт воды для этой точки</p>
                </div>
                <button
                    @click="toggleUsesWater"
                    :disabled="saving"
                    class="relative h-6 w-11 shrink-0 rounded-full transition-colors duration-200 focus:outline-none"
                    :class="settings.uses_water ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'"
                    role="switch"
                    :aria-checked="settings.uses_water"
                >
                    <span
                        class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                        :class="settings.uses_water ? 'translate-x-5' : 'translate-x-0'"
                    ></span>
                </button>
            </div>

            <!-- Блок: местоположение -->
            <button
                @click="openMap"
                class="w-full rounded-lg bg-white p-4 text-left shadow transition-colors hover:bg-gray-50 active:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-750 dark:active:bg-gray-700"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-500 dark:bg-blue-900/30 dark:text-blue-400">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 dark:text-white">Местоположение</p>
                            <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400">
                                {{ settings.address || 'Адрес не указан' }}
                            </p>
                            <p class="truncate text-sm text-gray-400 dark:text-gray-500">
                                <template v-if="settings.latitude && settings.longitude">
                                    {{ settings.latitude }}, {{ settings.longitude }}
                                </template>
                                <template v-else>
                                    Координаты не заданы
                                </template>
                            </p>
                        </div>
                    </div>
                    <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </div>
            </button>

            <!-- Сообщение об ошибке -->
            <div v-if="saveError" class="rounded-lg bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900/40 dark:text-red-300">
                {{ saveError }}
            </div>
        </div>

        <!-- Модальное окно карты -->
        <div v-if="mapOpen" class="fixed inset-0 z-50 flex h-dvh max-h-dvh flex-col overflow-hidden bg-white dark:bg-gray-900">
            <!-- Хедер карты -->
            <div class="shrink-0 flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ terminal?.comment || 'Местоположение' }}</h2>
                <button
                    @click="cancelMap"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Контейнер карты -->
            <div ref="mapContainer" class="min-h-0 flex-1"></div>

            <!-- Информация о выбранной точке -->
            <div v-if="mapAddress || mapCoords" class="shrink-0 border-t border-gray-200 px-4 py-2 dark:border-gray-800">
                <p v-if="mapAddress" class="text-sm text-gray-700 dark:text-gray-300">{{ mapAddress }}</p>
                <p v-if="mapCoords" class="text-xs text-gray-400 dark:text-gray-500">{{ mapCoords[0] }}, {{ mapCoords[1] }}</p>
            </div>

            <!-- Кнопки -->
            <div class="shrink-0 flex gap-3 border-t border-gray-200 p-4 dark:border-gray-800">
                <button
                    @click="cancelMap"
                    class="rounded-lg bg-gray-200 px-4 py-2.5 font-medium text-gray-700 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    :class="mapLocationChanged ? 'flex-1' : 'w-full'"
                >
                    {{ mapLocationChanged ? 'Отмена' : 'Закрыть' }}
                </button>
                <button
                    v-if="mapLocationChanged"
                    @click="saveMapLocation"
                    :disabled="saving || !mapCoords"
                    class="flex-1 rounded-lg bg-blue-500 px-4 py-2.5 font-medium text-white transition-colors hover:bg-blue-600 disabled:opacity-50"
                >
                    {{ saving ? 'Сохранение...' : 'Сохранить' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import apiClient from '@/api/client';

const route = useRoute();
const terminalId = route.params.id;

const terminal = ref(null);
const loading = ref(true);
const saving = ref(false);
const saveError = ref('');

// Настройки точки (локальное состояние)
const settings = ref({
    hidden: false,
    uses_water: true,
    address: null,
    latitude: null,
    longitude: null,
});

// Состояние карты
const mapOpen = ref(false);
const mapContainer = ref(null);
const mapAddress = ref('');
const mapCoords = ref(null);

// Исходные значения при открытии карты (для отслеживания изменений)
let originalMapAddress = '';
let originalMapCoords = null;

/** Проверка, изменились ли координаты или адрес на карте */
const mapLocationChanged = computed(() => {
    const coordsChanged = JSON.stringify(mapCoords.value) !== JSON.stringify(originalMapCoords);
    const addressChanged = (mapAddress.value || '') !== (originalMapAddress || '');
    return coordsChanged || addressChanged;
});

let ymapInstance = null;
let placemark = null;
let ymapsReady = null;

/** Загрузка данных терминала */
async function fetchTerminal() {
    try {
        const { data } = await apiClient.get(`/admin/points/${terminalId}`);
        terminal.value = data.terminal;

        if (data.terminal.settings) {
            settings.value = {
                hidden: data.terminal.settings.hidden,
                uses_water: data.terminal.settings.uses_water,
                address: data.terminal.settings.address,
                latitude: data.terminal.settings.latitude,
                longitude: data.terminal.settings.longitude,
            };
        }
    } finally {
        loading.value = false;
    }
}

/** Сохранение настроек на сервер */
async function saveSettings() {
    saving.value = true;
    saveError.value = '';

    try {
        const { data } = await apiClient.put(`/admin/points/${terminalId}`, {
            hidden: settings.value.hidden,
            uses_water: settings.value.uses_water,
            address: settings.value.address,
            latitude: settings.value.latitude,
            longitude: settings.value.longitude,
        });

        terminal.value = data.terminal;

        if (data.terminal.settings) {
            settings.value = {
                hidden: data.terminal.settings.hidden,
                uses_water: data.terminal.settings.uses_water,
                address: data.terminal.settings.address,
                latitude: data.terminal.settings.latitude,
                longitude: data.terminal.settings.longitude,
            };
        }
    } catch (error) {
        saveError.value = error.response?.data?.message || 'Не удалось сохранить настройки';
    } finally {
        saving.value = false;
    }
}

/** Переключатель «Скрыть из списка» */
function toggleHidden() {
    settings.value.hidden = !settings.value.hidden;
    saveSettings();
}

/** Переключатель «Использует воду» */
function toggleUsesWater() {
    settings.value.uses_water = !settings.value.uses_water;
    saveSettings();
}

/** Загрузка скрипта Яндекс.Карт */
function loadYmaps() {
    if (ymapsReady) return ymapsReady;

    // Если скрипт уже загружен глобально
    if (window.ymaps) {
        ymapsReady = new Promise(resolve => {
            window.ymaps.ready(resolve);
        });
        return ymapsReady;
    }

    const apiKey = document.querySelector('meta[name="yandex-maps-api-key"]')?.content || '';

    ymapsReady = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = `https://api-maps.yandex.ru/2.1/?apikey=${apiKey}&lang=ru_RU`;
        script.async = true;
        script.onload = () => {
            window.ymaps.ready(resolve);
        };
        script.onerror = () => {
            ymapsReady = null;
            reject(new Error('Не удалось загрузить Яндекс.Карты'));
        };
        document.head.appendChild(script);
    });

    return ymapsReady;
}

/** Открытие карты */
async function openMap() {
    mapOpen.value = true;

    // Инициализация координат для карты из текущих настроек
    if (settings.value.latitude && settings.value.longitude) {
        mapCoords.value = [
            parseFloat(settings.value.latitude),
            parseFloat(settings.value.longitude),
        ];
        mapAddress.value = settings.value.address || '';
    } else {
        mapCoords.value = null;
        mapAddress.value = '';
    }

    // Запоминаем исходные значения для отслеживания изменений
    originalMapCoords = mapCoords.value ? [...mapCoords.value] : null;
    originalMapAddress = mapAddress.value;

    await nextTick();

    try {
        await loadYmaps();
        initMap();
    } catch {
        saveError.value = 'Не удалось загрузить карту';
        mapOpen.value = false;
    }
}

/** Инициализация карты */
function initMap() {
    if (!mapContainer.value) return;

    // Центр карты: координаты точки или Иркутск
    const center = mapCoords.value
        ? [mapCoords.value[0], mapCoords.value[1]]
        : [52.2978, 104.2964];

    const zoom = mapCoords.value ? 16 : 12;

    ymapInstance = new window.ymaps.Map(mapContainer.value, {
        center,
        zoom,
        controls: ['zoomControl', 'geolocationControl'],
    });

    // Поиск по адресу (встроенный контрол Яндекс.Карт)
    const searchControl = new window.ymaps.control.SearchControl({
        options: {
            float: 'left',
            noPlacemark: true,
            placeholderContent: 'Поиск по адресу',
        },
    });
    ymapInstance.controls.add(searchControl);

    // При выборе результата поиска — ставим метку
    searchControl.events.add('resultselect', async () => {
        const result = searchControl.getResultsArray();
        const index = searchControl.getSelectedIndex();
        const geoObject = result[index];

        if (geoObject) {
            const coords = geoObject.geometry.getCoordinates();
            mapCoords.value = [
                parseFloat(coords[0].toFixed(7)),
                parseFloat(coords[1].toFixed(7)),
            ];
            mapAddress.value = geoObject.getAddressLine() || '';
            addPlacemark(mapCoords.value, mapAddress.value);
            ymapInstance.setCenter(coords, 16);
        }
    });

    // Если есть координаты — ставим метку
    if (mapCoords.value) {
        addPlacemark(mapCoords.value, mapAddress.value);
    }

    // Клик по карте — ставим/перемещаем метку + обратное геокодирование
    ymapInstance.events.add('click', async (e) => {
        const coords = e.get('coords');
        mapCoords.value = [
            parseFloat(coords[0].toFixed(7)),
            parseFloat(coords[1].toFixed(7)),
        ];

        addPlacemark(mapCoords.value, 'Определение адреса...');
        mapAddress.value = 'Определение адреса...';

        try {
            const address = await reverseGeocode(coords);

            mapAddress.value = address;
            if (placemark) {
                placemark.properties.set('balloonContentBody', formatBalloon(address, mapCoords.value));
            }
        } catch {
            mapAddress.value = '';
        }
    });
}

/** Добавление или перемещение метки */
function addPlacemark(coords, address) {
    if (placemark) {
        placemark.geometry.setCoordinates(coords);
        placemark.properties.set('balloonContentBody', formatBalloon(address, coords));
    } else {
        placemark = new window.ymaps.Placemark(coords, {
            balloonContentBody: formatBalloon(address, coords),
        }, {
            draggable: true,
            preset: 'islands#blueCircleDotIcon',
        });

        // Обратное геокодирование при перетаскивании метки
        placemark.events.add('dragend', async () => {
            const newCoords = placemark.geometry.getCoordinates();
            mapCoords.value = [
                parseFloat(newCoords[0].toFixed(7)),
                parseFloat(newCoords[1].toFixed(7)),
            ];

            mapAddress.value = 'Определение адреса...';

            try {
                const address = await reverseGeocode(newCoords);

                mapAddress.value = address;
                placemark.properties.set('balloonContentBody', formatBalloon(address, mapCoords.value));
            } catch {
                mapAddress.value = '';
            }
        });

        ymapInstance.geoObjects.add(placemark);
    }
}

/** Обратное геокодирование — получение адреса по координатам с точностью до дома */
async function reverseGeocode(coords) {
    // Сначала пробуем найти конкретный дом
    const houseResult = await window.ymaps.geocode(coords, { kind: 'house', results: 1 });
    const houseObject = houseResult.geoObjects.get(0);

    if (houseObject) {
        // Проверяем, что дом находится в разумной близости (не дальше ~100м)
        const houseCoords = houseObject.geometry.getCoordinates();
        const distance = window.ymaps.coordSystem.geo.getDistance(coords, houseCoords);

        if (distance < 100) {
            return houseObject.getAddressLine();
        }
    }

    // Если дом далеко — берём ближайший объект любого типа
    const fallbackResult = await window.ymaps.geocode(coords, { results: 1 });
    const fallbackObject = fallbackResult.geoObjects.get(0);
    return fallbackObject ? fallbackObject.getAddressLine() : '';
}

/** Форматирование содержимого балуна */
function formatBalloon(address, coords) {
    const parts = [];
    if (address) parts.push(`<strong>${address}</strong>`);
    if (coords) parts.push(`<span style="color:#999">${coords[0]}, ${coords[1]}</span>`);
    return parts.join('<br>');
}

/** Сохранение местоположения с карты */
async function saveMapLocation() {
    if (!mapCoords.value) return;

    settings.value.latitude = mapCoords.value[0];
    settings.value.longitude = mapCoords.value[1];
    settings.value.address = mapAddress.value || null;

    await saveSettings();

    if (!saveError.value) {
        destroyMap();
        mapOpen.value = false;
    }
}

/** Отмена — закрытие карты без сохранения */
function cancelMap() {
    destroyMap();
    mapOpen.value = false;
}

/** Уничтожение карты и очистка */
function destroyMap() {
    if (ymapInstance) {
        ymapInstance.destroy();
        ymapInstance = null;
    }
    placemark = null;
}

onMounted(fetchTerminal);
</script>
