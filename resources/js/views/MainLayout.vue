<template>
  <div class="min-h-screen flex bg-[#090d16] text-slate-100 font-sans">
    
    <!-- Sidebar -->
    <aside class="w-64 bg-[#0d1527] border-r border-slate-800 flex flex-col justify-between flex-shrink-0 z-30 transition-all duration-300 md:translate-x-0" :class="{'translate-x-0': sidebarOpen, '-translate-x-full md:translate-x-0': !sidebarOpen}">
      <div>
        <!-- Brand logo -->
        <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800">
          <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-extrabold text-lg shadow-md shadow-indigo-600/30">T</div>
          <span class="font-bold text-lg bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">TransitOps</span>
        </div>

        <!-- User role indicator -->
        <div class="px-6 py-4 border-b border-slate-800/50 bg-slate-900/30">
          <p class="text-xs text-slate-500 font-medium">Logged in as</p>
          <p class="text-sm font-semibold text-white truncate">{{ user?.name }}</p>
          <span class="inline-flex items-center gap-1.5 px-2 py-0.5 mt-1.5 rounded-full bg-indigo-500/10 text-indigo-400 text-[10px] font-bold uppercase tracking-wider border border-indigo-500/20">
            {{ user?.role?.name || 'User' }}
          </span>
        </div>

        <!-- Navigation Menu -->
        <nav class="p-4 space-y-1">
          <router-link 
            v-for="item in menuItems" 
            :key="item.name"
            :to="item.path"
            v-show="hasPermission(item.permission)"
            class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all duration-200 group text-slate-400 hover:text-white hover:bg-slate-800/50"
            active-class="bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-600/10 hover:text-indigo-400 font-semibold"
          >
            <span class="w-5 h-5 flex-shrink-0 group-hover:scale-110 transition-transform" v-html="item.icon"></span>
            <span class="text-sm">{{ item.name }}</span>
          </router-link>
        </nav>
      </div>

      <!-- Logout -->
      <div class="p-4 border-t border-slate-800">
        <button 
          @click="logout"
          class="w-full flex items-center gap-3 px-4 py-3 text-slate-400 hover:text-rose-400 hover:bg-rose-500/5 rounded-xl transition-all font-medium text-sm"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          Sign Out
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
      
      <!-- Top navbar -->
      <header class="h-16 border-b border-slate-800/80 bg-[#0d1527]/60 backdrop-blur-md flex items-center justify-between px-6 sticky top-0 z-20">
        <div class="flex items-center gap-4">
          <!-- Toggle sidebar on mobile -->
          <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          
          <h2 class="text-lg font-bold text-white hidden md:block">
            {{ currentRouteName }}
          </h2>
        </div>

        <div class="flex items-center gap-4">
          <!-- Quick Status Check -->
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 text-xs font-semibold border border-emerald-500/20">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
            Operational Connection: Online
          </span>

          <div class="h-8 w-px bg-slate-800"></div>

          <!-- User profile avatar (styled) -->
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-sm text-white uppercase">
              {{ user?.name ? user.name[0] : 'U' }}
            </div>
            <span class="text-sm font-semibold text-slate-200 hidden sm:block">{{ user?.name }}</span>
          </div>
        </div>
      </header>

      <!-- Main body routing -->
      <main class="p-6 md:p-8 flex-1">
        <router-view />
      </main>

    </div>

    <!-- Global Custom Confirmation Modal -->
    <div v-if="confirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-6 text-left transform scale-100 transition-all duration-300">
        <h3 class="text-lg font-bold text-white mb-2">{{ confirmModal.title }}</h3>
        <p class="text-sm text-slate-400 mb-6">{{ confirmModal.message }}</p>
        <div class="flex justify-end gap-3">
          <button 
            @click="cancelConfirm"
            class="py-2.5 px-4 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-colors"
          >
            Cancel
          </button>
          <button 
            @click="executeConfirm"
            class="py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-colors shadow-lg shadow-indigo-600/10"
          >
            Confirm Action
          </button>
        </div>
      </div>
    </div>

    <!-- Global Toast Notifications -->
    <div class="fixed top-4 right-4 z-[100] space-y-2 pointer-events-none w-full max-w-sm">
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        class="p-4 rounded-2xl border text-sm font-medium shadow-2xl transition-all duration-300 pointer-events-auto flex items-center gap-3 bg-[#0d1527]/90 backdrop-blur"
        :class="{
          'border-emerald-500/30 text-emerald-400': toast.type === 'success',
          'border-rose-500/30 text-rose-400': toast.type === 'error' || toast.type === 'warning',
          'border-indigo-500/30 text-indigo-400': toast.type === 'info',
        }"
      >
        <span class="w-2 h-2 rounded-full" :class="{
          'bg-emerald-500 animate-pulse': toast.type === 'success',
          'bg-rose-500 animate-pulse': toast.type === 'error' || toast.type === 'warning',
          'bg-indigo-500 animate-pulse': toast.type === 'info',
        }"></span>
        <span class="flex-1 text-slate-200 text-xs">{{ toast.message }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../store';

export default {
  name: 'MainLayout',
  setup() {
    const router = useRouter();
    const route = useRoute();
    const authStore = computed(() => useAuthStore());
    
    const sidebarOpen = ref(false);

    const user = computed(() => authStore.value.user);
    const hasPermission = (permission) => authStore.value.hasPermission(permission);

    const toasts = computed(() => authStore.value.toasts);
    const confirmModal = computed(() => authStore.value.confirmModal);

    const executeConfirm = () => {
      if (authStore.value.confirmModal?.onConfirm) {
        authStore.value.confirmModal.onConfirm();
      }
      authStore.value.clearConfirm();
    };

    const cancelConfirm = () => {
      if (authStore.value.confirmModal?.onCancel) {
        authStore.value.confirmModal.onCancel();
      }
      authStore.value.clearConfirm();
    };

    const logout = async () => {
      await authStore.value.logout();
      router.push({ name: 'Login' });
    };

    const currentRouteName = computed(() => {
      return route.name || 'Dashboard';
    });

    const menuItems = [
      {
        name: 'Dashboard',
        path: '/',
        permission: 'dashboard.view',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>'
      },
      {
        name: 'Vehicle Registry',
        path: '/vehicles',
        permission: 'vehicles.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>'
      },
      {
        name: 'Driver Profiles',
        path: '/drivers',
        permission: 'drivers.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>'
      },
      {
        name: 'Trip Orchestrator',
        path: '/trips',
        permission: 'trips.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>'
      },
      {
        name: 'Maintenance Logs',
        path: '/maintenance',
        permission: 'maintenance.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>'
      },
      {
        name: 'Fuel & Expenses',
        path: '/fuel-expenses',
        permission: 'expenses.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
      },
      {
        name: 'Document Vault',
        path: '/documents',
        permission: 'documents.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>'
      },
      {
        name: 'Settings & RBAC',
        path: '/settings',
        permission: 'roles.index',
        icon: '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>'
      }
    ];

    return {
      sidebarOpen,
      user,
      menuItems,
      hasPermission,
      logout,
      currentRouteName,
      toasts,
      confirmModal,
      executeConfirm,
      cancelConfirm
    };
  }
}
</script>
