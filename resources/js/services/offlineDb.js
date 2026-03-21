/**
 * IndexedDB обертка для хранения визитов в режиме offline.
 * БД: terminals-offline, версия 1.
 * Object store: pendingVisits (keyPath: id).
 */

const DB_NAME = 'terminals-offline';
const DB_VERSION = 1;
const STORE_NAME = 'pendingVisits';

let dbPromise = null;

/**
 * Открыть/создать БД (синглтон).
 * @returns {Promise<IDBDatabase>}
 */
export function openDb() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

/**
 * Сохранить визит в очередь на отправку.
 * @param {Object} visitData - данные визита
 * @returns {Promise<void>}
 */
export async function savePendingVisit(visitData) {
    const db = await openDb();
    const record = {
        id: crypto.randomUUID(),
        terminalId: visitData.terminalId,
        terminalName: visitData.terminalName,
        visitedAt: visitData.visitedAt,
        waterMain: visitData.waterMain ?? null,
        waterSpare: visitData.waterSpare ?? null,
        usesWater: visitData.usesWater,
        comment: visitData.comment,
        latitude: visitData.latitude ?? null,
        longitude: visitData.longitude ?? null,
        ingredients: visitData.ingredients || [],
        // Фото хранятся как Blob (File наследуется от Blob, IndexedDB сохраняет)
        photos: {
            inside: visitData.photos?.inside ?? null,
            outside: visitData.photos?.outside ?? null,
            comment: visitData.photos?.comment ?? [],
        },
        photoNames: {
            inside: visitData.photoNames?.inside ?? null,
            outside: visitData.photoNames?.outside ?? null,
            comment: visitData.photoNames?.comment ?? [],
        },
        createdAt: new Date().toISOString(),
        syncStatus: 'pending',
        syncError: null,
        syncAttempts: 0,
    };

    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).put(record);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * Получить все ожидающие визиты, отсортированные по createdAt ASC.
 * @returns {Promise<Array>}
 */
export async function getPendingVisits() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const request = tx.objectStore(STORE_NAME).getAll();
        request.onsuccess = () => {
            const visits = request.result || [];
            visits.sort((a, b) => a.createdAt.localeCompare(b.createdAt));
            resolve(visits);
        };
        request.onerror = () => reject(request.error);
    });
}

/**
 * Количество ожидающих визитов.
 * @returns {Promise<number>}
 */
export async function getPendingCount() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const request = tx.objectStore(STORE_NAME).count();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Обновить статус синхронизации визита.
 * @param {string} id
 * @param {'pending'|'syncing'|'failed'} status
 * @param {string|null} error
 * @returns {Promise<void>}
 */
export async function updateSyncStatus(id, status, error = null) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const getReq = store.get(id);
        getReq.onsuccess = () => {
            const record = getReq.result;
            if (!record) {
                resolve();
                return;
            }
            record.syncStatus = status;
            record.syncError = error;
            if (status === 'failed') {
                record.syncAttempts = (record.syncAttempts || 0) + 1;
            }
            store.put(record);
        };
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * Удалить визит после успешной синхронизации.
 * @param {string} id
 * @returns {Promise<void>}
 */
export async function deletePendingVisit(id) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        tx.objectStore(STORE_NAME).delete(id);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}
