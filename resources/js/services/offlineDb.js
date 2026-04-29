/**
 * IndexedDB обертка для хранения визитов и черновиков в режиме offline.
 * БД: terminals-offline, версия 2.
 * Object stores: pendingVisits (отложенные визиты), visitDrafts (черновики обслуживания).
 */

const DB_NAME = 'terminals-offline';
const DB_VERSION = 2;
const STORE_PENDING = 'pendingVisits';
const STORE_DRAFTS = 'visitDrafts';

let dbPromise = null;

/** Открыть/создать БД (синглтон). */
export function openDb() {
    if (dbPromise) return dbPromise;

    dbPromise = new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_PENDING)) {
                db.createObjectStore(STORE_PENDING, { keyPath: 'id' });
            }
            if (!db.objectStoreNames.contains(STORE_DRAFTS)) {
                db.createObjectStore(STORE_DRAFTS, { keyPath: 'terminalId' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });

    return dbPromise;
}

// ==================== Pending Visits ====================

/** Сохранить визит в очередь на отправку. */
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
        const tx = db.transaction(STORE_PENDING, 'readwrite');
        tx.objectStore(STORE_PENDING).put(record);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/** Получить все ожидающие визиты, отсортированные по createdAt ASC. */
export async function getPendingVisits() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_PENDING, 'readonly');
        const request = tx.objectStore(STORE_PENDING).getAll();
        request.onsuccess = () => {
            const visits = request.result || [];
            visits.sort((a, b) => a.createdAt.localeCompare(b.createdAt));
            resolve(visits);
        };
        request.onerror = () => reject(request.error);
    });
}

/** Количество ожидающих визитов. */
export async function getPendingCount() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_PENDING, 'readonly');
        const request = tx.objectStore(STORE_PENDING).count();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/** Обновить статус синхронизации визита. */
export async function updateSyncStatus(id, status, error = null) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_PENDING, 'readwrite');
        const store = tx.objectStore(STORE_PENDING);
        const getReq = store.get(id);
        getReq.onsuccess = () => {
            const record = getReq.result;
            if (!record) { resolve(); return; }
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
 * Сбросить счётчик попыток у всех ожидающих визитов.
 * Используется при ручной повторной отправке (клик по баннеру на Home).
 */
export async function resetAllSyncAttempts() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_PENDING, 'readwrite');
        const store = tx.objectStore(STORE_PENDING);
        const req = store.getAll();
        req.onsuccess = () => {
            const records = req.result || [];
            for (const r of records) {
                r.syncAttempts = 0;
                r.syncStatus = 'pending';
                r.syncError = null;
                store.put(r);
            }
        };
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/** Удалить визит после успешной синхронизации. */
export async function deletePendingVisit(id) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_PENDING, 'readwrite');
        tx.objectStore(STORE_PENDING).delete(id);
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

// ==================== Visit Drafts ====================

/** Сохранить/обновить черновик обслуживания (ключ: terminalId). */
export async function saveDraft(draft) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_DRAFTS, 'readwrite');
        tx.objectStore(STORE_DRAFTS).put({
            ...draft,
            updatedAt: new Date().toISOString(),
        });
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/** Получить черновик по terminalId. */
export async function getDraft(terminalId) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_DRAFTS, 'readonly');
        const request = tx.objectStore(STORE_DRAFTS).get(Number(terminalId));
        request.onsuccess = () => resolve(request.result || null);
        request.onerror = () => reject(request.error);
    });
}

/** Удалить черновик (после успешного сохранения визита). */
export async function deleteDraft(terminalId) {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_DRAFTS, 'readwrite');
        tx.objectStore(STORE_DRAFTS).delete(Number(terminalId));
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/** Получить все черновики (для отображения индикаторов на Home.vue). */
export async function getAllDrafts() {
    const db = await openDb();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_DRAFTS, 'readonly');
        const request = tx.objectStore(STORE_DRAFTS).getAll();
        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}
