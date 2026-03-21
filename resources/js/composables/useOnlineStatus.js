/**
 * Composable для отслеживания состояния сети.
 * Возвращает реактивный ref isOnline.
 */
import { ref, onScopeDispose } from 'vue';

export function useOnlineStatus() {
    const isOnline = ref(navigator.onLine);

    function handleOnline() {
        isOnline.value = true;
    }

    function handleOffline() {
        isOnline.value = false;
    }

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    onScopeDispose(() => {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
    });

    return { isOnline };
}
