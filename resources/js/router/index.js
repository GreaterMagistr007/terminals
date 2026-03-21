import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import Home from '@/pages/Home.vue';
import Service from '@/pages/Service.vue';
import ServiceHistory from '@/pages/ServiceHistory.vue';
import Sales from '@/pages/Sales.vue';
import Login from '@/pages/Login.vue';

const routes = [
    {
        path: '/demo/a',
        name: 'demo-a',
        component: () => import('@/pages/demo/VariantA.vue'),
    },
    {
        path: '/demo/b',
        name: 'demo-b',
        component: () => import('@/pages/demo/VariantB.vue'),
    },
    {
        path: '/demo/c',
        name: 'demo-c',
        component: () => import('@/pages/demo/VariantC.vue'),
    },
    {
        path: '/demo/d',
        name: 'demo-d',
        component: () => import('@/pages/demo/VariantD.vue'),
    },
    {
        path: '/demo/e',
        name: 'demo-e',
        component: () => import('@/pages/demo/VariantE.vue'),
    },
    {
        path: '/demo/f',
        name: 'demo-f',
        component: () => import('@/pages/demo/VariantF.vue'),
    },
    {
        path: '/demo/g',
        name: 'demo-g',
        component: () => import('@/pages/demo/VariantG.vue'),
    },
    {
        path: '/demo/h',
        name: 'demo-h',
        component: () => import('@/pages/demo/VariantH.vue'),
    },
    {
        path: '/demo/i',
        name: 'demo-i',
        component: () => import('@/pages/demo/VariantI.vue'),
    },
    {
        path: '/demo/j',
        name: 'demo-j',
        component: () => import('@/pages/demo/VariantJ.vue'),
    },
    {
        path: '/demo/k',
        name: 'demo-k',
        component: () => import('@/pages/demo/VariantK.vue'),
    },
    {
        path: '/demo/l',
        name: 'demo-l',
        component: () => import('@/pages/demo/VariantL.vue'),
    },
    {
        path: '/demo/m',
        name: 'demo-m',
        component: () => import('@/pages/demo/VariantM.vue'),
    },
    {
        path: '/demo/n',
        name: 'demo-n',
        component: () => import('@/pages/demo/VariantN.vue'),
    },
    {
        path: '/demo/o',
        name: 'demo-o',
        component: () => import('@/pages/demo/VariantO.vue'),
    },
    {
        path: '/demo/p',
        name: 'demo-p',
        component: () => import('@/pages/demo/VariantP.vue'),
    },
    {
        path: '/demo/q',
        name: 'demo-q',
        component: () => import('@/pages/demo/VariantQ.vue'),
    },
    {
        path: '/demo/r',
        name: 'demo-r',
        component: () => import('@/pages/demo/VariantR.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/auth/telegram/:token',
        name: 'telegram-callback',
        component: () => import('@/pages/TelegramCallback.vue'),
        meta: { guest: true },
    },
    {
        path: '/service/:id',
        name: 'service',
        component: Service,
        meta: { auth: true },
    },
    {
        path: '/',
        component: AppLayout,
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'home',
                component: Home,
            },
            {
                path: 'history/:id',
                name: 'history',
                component: ServiceHistory,
            },
            {
                path: 'sales',
                name: 'sales',
                component: Sales,
            },
        ],
    },
    {
        path: '/admin',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { auth: true, admin: true },
        children: [
            {
                path: 'points',
                name: 'admin-points',
                component: () => import('@/pages/admin/Points.vue'),
            },
            {
                path: 'points/:id',
                name: 'admin-point-settings',
                component: () => import('@/pages/admin/PointSettings.vue'),
            },
            {
                path: 'ingredients',
                name: 'admin-ingredients',
                component: () => import('@/pages/admin/Ingredients.vue'),
            },
            {
                path: 'ingredients/:id/history',
                name: 'admin-ingredient-history',
                component: () => import('@/pages/admin/IngredientHistory.vue'),
            },
            {
                path: 'warehouses',
                name: 'admin-warehouses',
                component: () => import('@/pages/admin/Warehouses.vue'),
            },
            {
                path: 'warehouses/:id/stocks',
                name: 'admin-warehouse-stocks',
                component: () => import('@/pages/admin/WarehouseStocks.vue'),
            },
            {
                path: 'terminals',
                name: 'admin-terminals',
                component: () => import('@/pages/admin/Terminals.vue'),
            },
            {
                path: 'users',
                name: 'admin-users',
                component: () => import('@/pages/admin/Users.vue'),
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

// Ошибка загрузки lazy-чанка (офлайн, админка) — не ломать приложение
router.onError((error, to) => {
    if (error.message?.includes('Failed to fetch dynamically imported module') ||
        error.message?.includes('Importing a module script failed')) {
        router.push({ name: 'home' });
    }
});

export default router;
