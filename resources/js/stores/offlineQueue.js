/**
 * Pinia store для управления очередью offline-визитов.
 * Сохраняет визиты в IndexedDB и синхронизирует при появлении сети.
 */
import { defineStore } from 'pinia';
import apiClient from '@/api/client';
import {
    savePendingVisit,
    getPendingVisits,
    getPendingCount,
    updateSyncStatus,
    deletePendingVisit,
    resetAllSyncAttempts,
} from '@/services/offlineDb';

const MAX_SYNC_ATTEMPTS = 5;

export const useOfflineQueueStore = defineStore('offlineQueue', {
    state: () => ({
        pendingCount: 0,
        syncing: false,
        // Последняя ошибка синхронизации (для отображения под баннером).
        // { message, status, terminalId, visitId, at }
        lastSyncError: null,
    }),

    actions: {
        /** Загрузить количество ожидающих визитов из IndexedDB */
        async loadCount() {
            try {
                this.pendingCount = await getPendingCount();
            } catch {
                // IndexedDB недоступна -- игнорируем
            }
        },

        /** Добавить визит в очередь */
        async enqueue(visitData) {
            await savePendingVisit(visitData);
            this.pendingCount = await getPendingCount();
        },

        /**
         * Обнулить счётчик попыток у всех записей и попробовать синхронизировать заново.
         * Возвращает количество успешно отправленных.
         */
        async retryAll() {
            await resetAllSyncAttempts();
            this.lastSyncError = null;
            return this.syncAll();
        },

        /**
         * Отправить все ожидающие визиты на сервер.
         * Возвращает количество успешно отправленных.
         */
        async syncAll() {
            if (this.syncing) return 0;
            this.syncing = true;

            let sentCount = 0;

            try {
                const visits = await getPendingVisits();
                if (!visits.length) return 0;

                for (const visit of visits) {
                    // Пропускаем визиты с превышенным количеством попыток
                    if (visit.syncAttempts >= MAX_SYNC_ATTEMPTS) continue;

                    await updateSyncStatus(visit.id, 'syncing');

                    try {
                        const formData = buildFormData(visit);
                        await apiClient.post('/service-visits', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' },
                        });

                        await deletePendingVisit(visit.id);
                        sentCount++;
                        // При успехе сбрасываем последнюю ошибку
                        this.lastSyncError = null;
                    } catch (error) {
                        // Ошибка 419 (CSRF) -- обновить токен и повторить
                        if (error.response?.status === 419) {
                            try {
                                await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
                                const formData = buildFormData(visit);
                                await apiClient.post('/service-visits', formData, {
                                    headers: { 'Content-Type': 'multipart/form-data' },
                                });

                                await deletePendingVisit(visit.id);
                                sentCount++;
                                this.lastSyncError = null;
                                continue;
                            } catch (retryError) {
                                // Повторная попытка не удалась -- пишем подробности
                                recordError(this, visit, retryError, 'sync-retry-419');
                            }
                        }

                        // Сетевая ошибка (нет response) -- прекращаем синхронизацию
                        if (!error.response) {
                            await updateSyncStatus(visit.id, 'failed', 'Нет сети');
                            this.lastSyncError = {
                                message: 'Нет сети при отправке визита',
                                status: null,
                                terminalId: visit.terminalId,
                                visitId: visit.id,
                                at: new Date().toISOString(),
                            };
                            // Сетевые ошибки не логируем на сервер -- его всё равно не достать
                            break;
                        }

                        // Серверная ошибка -- помечаем и продолжаем
                        const msg = extractErrorMessage(error);
                        await updateSyncStatus(visit.id, 'failed', msg);
                        recordError(this, visit, error, 'offline-sync');
                    }
                }
            } finally {
                this.pendingCount = await getPendingCount();
                this.syncing = false;
            }

            return sentCount;
        },
    },
});

/**
 * Извлечь читаемое сообщение из axios-ошибки.
 */
function extractErrorMessage(error) {
    const status = error.response?.status;
    const data = error.response?.data;

    if (data?.message) return `[${status}] ${data.message}`;
    if (data?.errors) {
        const first = Object.values(data.errors)[0];
        const text = Array.isArray(first) ? first[0] : String(first);
        return `[${status}] ${text}`;
    }
    return `[${status ?? '?'}] ${error.message || 'Ошибка сервера'}`;
}

/**
 * Сохранить ошибку в state и отправить на сервер (fire-and-forget).
 * Серверный лог нужен, чтобы можно было отладить проблему оператора, не имея доступа к телефону.
 */
function recordError(store, visit, error, source) {
    const message = extractErrorMessage(error);
    store.lastSyncError = {
        message,
        status: error.response?.status ?? null,
        terminalId: visit.terminalId,
        visitId: visit.id,
        at: new Date().toISOString(),
    };

    const context = {
        visit_id: visit.id,
        terminal_id: visit.terminalId,
        terminal_name: visit.terminalName,
        sync_attempts: visit.syncAttempts,
        status: error.response?.status ?? null,
        response_data: safeTrim(error.response?.data, 2000),
        error_name: error.name,
        error_code: error.code,
        error_message: error.message,
    };

    // Не ждём ответа и не падаем при недоступности
    apiClient
        .post('/client-errors', {
            source,
            message,
            context,
            url: typeof window !== 'undefined' ? window.location.href : null,
        })
        .catch(() => {});
}

/**
 * Безопасно сериализовать произвольное значение в строку с ограничением длины.
 */
function safeTrim(value, maxLength) {
    if (value == null) return null;
    let str;
    try {
        str = typeof value === 'string' ? value : JSON.stringify(value);
    } catch {
        str = String(value);
    }
    if (str.length > maxLength) {
        return str.slice(0, maxLength) + '…';
    }
    return str;
}

/**
 * Собрать FormData из сохранённого визита (аналогично Service.vue).
 * Фото хранятся как Blob в IndexedDB, создаём File из них.
 */
function buildFormData(visit) {
    const formData = new FormData();

    // visit.id (UUID из IndexedDB) служит idempotency-ключом: при повторной отправке
    // того же визита бэк вернёт уже созданную запись, без второго уведомления в Telegram.
    formData.append('client_uuid', visit.id);
    formData.append('terminal_id', visit.terminalId);
    formData.append('visited_at', visit.visitedAt);

    if (visit.usesWater) {
        formData.append('water_main', visit.waterMain ?? 0);
        formData.append('water_spare', visit.waterSpare ?? 0);
    }

    formData.append('comment', visit.comment || '');

    if (visit.latitude !== null) {
        formData.append('latitude', visit.latitude);
    }
    if (visit.longitude !== null) {
        formData.append('longitude', visit.longitude);
    }

    // Фото внутри
    if (visit.photos.inside) {
        const name = visit.photoNames.inside || 'photo_inside.jpg';
        const file = new File([visit.photos.inside], name, {
            type: visit.photos.inside.type || 'image/jpeg',
        });
        formData.append('photo_inside', file);
    }

    // Фото снаружи
    if (visit.photos.outside) {
        const name = visit.photoNames.outside || 'photo_outside.jpg';
        const file = new File([visit.photos.outside], name, {
            type: visit.photos.outside.type || 'image/jpeg',
        });
        formData.append('photo_outside', file);
    }

    // Фото к комментарию
    if (visit.photos.comment?.length) {
        visit.photos.comment.forEach((blob, idx) => {
            const name = visit.photoNames.comment?.[idx] || `comment_photo_${idx}.jpg`;
            const file = new File([blob], name, {
                type: blob.type || 'image/jpeg',
            });
            formData.append('comment_photos[]', file);
        });
    }

    // Ингредиенты
    const ingredientsData = (visit.ingredients || [])
        .filter(ing => ing.brought > 0 || ing.needed > 0)
        .map(ing => ({
            ingredient_id: ing.ingredient_id,
            brought: ing.brought,
            needed: ing.needed,
        }));
    formData.append('ingredients', JSON.stringify(ingredientsData));

    return formData;
}
