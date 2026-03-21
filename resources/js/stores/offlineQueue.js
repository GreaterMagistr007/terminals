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
} from '@/services/offlineDb';

const MAX_SYNC_ATTEMPTS = 5;

export const useOfflineQueueStore = defineStore('offlineQueue', {
    state: () => ({
        pendingCount: 0,
        syncing: false,
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
                                continue;
                            } catch {
                                // Повторная попытка не удалась
                            }
                        }

                        // Сетевая ошибка (нет response) -- прекращаем синхронизацию
                        if (!error.response) {
                            await updateSyncStatus(visit.id, 'failed', 'Нет сети');
                            break;
                        }

                        // Серверная ошибка -- помечаем и продолжаем
                        const msg = error.response?.data?.message || 'Ошибка сервера';
                        await updateSyncStatus(visit.id, 'failed', msg);
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
 * Собрать FormData из сохранённого визита (аналогично Service.vue).
 * Фото хранятся как Blob в IndexedDB, создаём File из них.
 */
function buildFormData(visit) {
    const formData = new FormData();

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
