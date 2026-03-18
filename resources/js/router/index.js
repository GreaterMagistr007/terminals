import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/auth/telegram/:token',
        name: 'telegram-callback',
        component: () => import('@/pages/TelegramCallback.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: AppLayout,
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'home',
                component: () => import('@/pages/Home.vue'),
            },
            {
                path: 'admin/users',
                name: 'admin-users',
                component: () => import('@/pages/admin/Users.vue'),
                meta: { admin: true },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const authStore = useAuthStore();

    // Загрузка пользователя при первом заходе
    if (!authStore.loaded) {
        await authStore.fetchUser();
    }

    // Гостевые страницы: если авторизован — редирект на главную
    if (to.meta.guest && authStore.isAuthenticated) {
        return { name: 'home' };
    }

    // Защищённые страницы: если не авторизован — редирект на логин
    if (to.meta.auth && !authStore.isAuthenticated) {
        return { name: 'login' };
    }

    // Админские страницы: если не админ — редирект на главную
    if (to.meta.admin && !authStore.isAdmin) {
        return { name: 'home' };
    }
});

export default router;
