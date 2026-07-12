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
                path: 'fuel-expenses',
                name: 'FuelExpenses',
                component: () => import('./views/FuelExpenses.vue'),
                meta: { permission: 'expenses.index' }
            },
            {
                path: 'documents',
                name: 'Documents',
                component: () => import('./views/Documents.vue'),
                meta: { permission: 'documents.index' }
            },
            {
                path: 'settings',
                name: 'Settings',
                component: () => import('./views/Settings.vue'),
                meta: { permission: 'roles.index' }
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

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // If token exists but user profile is not loaded, fetch it first to prevent layout/permission redirect loops
    if (authStore.token && !authStore.user) {
        try {
            await authStore.fetchMe();
        } catch (err) {
            authStore.logout();
            if (to.name !== 'Login') {
                return next({ name: 'Login' });
            }
        }
    }
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        return next({ name: 'Login' });
    }
    
    if (authStore.isAuthenticated) {
        const isOnlyDriver = authStore.hasPermission('trips.view-own') && !authStore.hasPermission('dashboard.view');

        if (to.meta.guestOnly) {
            return next({ name: isOnlyDriver ? 'Trips' : 'Dashboard' });
        }
        
        if (to.name === 'Dashboard' && !authStore.hasPermission('dashboard.view')) {
            return next({ name: isOnlyDriver ? 'Trips' : 'Login' });
        }
        
        if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
            if (to.name === 'Trips' && authStore.hasPermission('trips.view-own')) {
                return next();
            }
            return next({ name: isOnlyDriver ? 'Trips' : 'Dashboard' });
        }
    }
    
    next();
});

export default router;
