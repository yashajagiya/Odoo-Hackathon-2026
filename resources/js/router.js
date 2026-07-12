import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './store';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('./views/Login.vue'),
        meta: { guestOnly: true }
    },
    {
        path: '/',
        component: () => import('./views/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: () => import('./views/Dashboard.vue'),
                meta: { permission: 'dashboard.view' }
            },
            {
                path: 'vehicles',
                name: 'Vehicles',
                component: () => import('./views/Vehicles.vue'),
                meta: { permission: 'vehicles.index' }
            },
            {
                path: 'drivers',
                name: 'Drivers',
                component: () => import('./views/Drivers.vue'),
                meta: { permission: 'drivers.index' }
            },
            {
                path: 'trips',
                name: 'Trips',
                component: () => import('./views/Trips.vue'),
                meta: { permission: 'trips.index' }
            },
            {
                path: 'maintenance',
                name: 'Maintenance',
                component: () => import('./views/Maintenance.vue'),
                meta: { permission: 'maintenance.index' }
            },
            {
                path: 'fuel-logs',
                name: 'FuelLogs',
                component: () => import('./views/FuelLogs.vue'),
                meta: { permission: 'fuel-logs.index' }
            },
            {
                path: 'expenses',
                name: 'Expenses',
                component: () => import('./views/Expenses.vue'),
                meta: { permission: 'expenses.index' }
            },
            {
                path: 'documents',
                name: 'Documents',
                component: () => import('./views/Documents.vue'),
                meta: { permission: 'documents.index' }
            }
        ]
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next({ name: 'Login' });
    }
    
    if (to.meta.guestOnly && authStore.isAuthenticated) {
        return next({ name: 'Dashboard' });
    }
    
    if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
        return next({ name: 'Dashboard' }); // Fallback to dashboard if unauthorized
    }
    
    next();
});

export default router;
