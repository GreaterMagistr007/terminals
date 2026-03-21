import axios from 'axios';

const apiClient = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

// Интерцептор: при 419 (CSRF mismatch) обновить токен и повторить запрос
let isRefreshingCsrf = false;
let csrfRefreshPromise = null;

apiClient.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;

        if (error.response?.status === 419 && !originalRequest._csrfRetried) {
            originalRequest._csrfRetried = true;

            // Один запрос на обновление CSRF за раз
            if (!isRefreshingCsrf) {
                isRefreshingCsrf = true;
                csrfRefreshPromise = fetch('/sanctum/csrf-cookie', { credentials: 'include' })
                    .finally(() => { isRefreshingCsrf = false; });
            }

            try {
                await csrfRefreshPromise;
                return apiClient(originalRequest);
            } catch {
                return Promise.reject(error);
            }
        }

        return Promise.reject(error);
    }
);

export default apiClient;
