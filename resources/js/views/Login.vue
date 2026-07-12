<template>
  <div class="min-h-screen flex items-center justify-center bg-[#090d16] relative overflow-hidden px-4">
    <!-- Background subtle gradient glow -->
    <div class="absolute w-[500px] h-[500px] rounded-full bg-indigo-900/20 blur-[120px] top-[-100px] left-[-100px] pointer-events-none"></div>
    <div class="absolute w-[500px] h-[500px] rounded-full bg-purple-900/10 blur-[120px] bottom-[-100px] right-[-100px] pointer-events-none"></div>

    <div class="w-full max-w-5xl grid md:grid-cols-12 gap-8 items-center relative z-10">
      
      <!-- Left side info: branding -->
      <div class="md:col-span-6 space-y-6 text-left pr-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-sm font-semibold tracking-wide">
          <span class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></span>
          TransitOps Management Platform
        </div>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight text-white leading-tight">
          Streamlining <span class="bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">Fleet Operations</span> in Real-Time
        </h1>
        <p class="text-slate-400 text-lg">
          A modular dashboard designed to orchestrate vehicles, drivers, dispatches, maintenance logs, and financial expenses on a single unified platform.
        </p>

        <!-- Fast access credentials -->
        <div class="space-y-3 pt-4">
          <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500">Quick Access Demo Accounts</h3>
          <div class="grid grid-cols-2 gap-2">
            <button 
              v-for="role in demoRoles" 
              :key="role.email"
              @click="autofill(role.email)"
              class="flex flex-col p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-900 transition-all text-left group"
            >
              <span class="text-white font-semibold text-xs group-hover:text-indigo-400 transition-colors">{{ role.name }}</span>
              <span class="text-slate-500 text-[10px] truncate">{{ role.email }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Right side: Login Form -->
      <div class="md:col-span-6">
        <div class="backdrop-blur-xl bg-slate-900/40 border border-slate-800/80 p-8 rounded-3xl shadow-2xl relative">
          <!-- Form Header -->
          <div class="space-y-2 mb-8">
            <h2 class="text-2xl font-bold text-white">Sign In</h2>
            <p class="text-slate-400 text-sm">Enter your credentials to access your workspace dashboard.</p>
          </div>

          <!-- Error Alert -->
          <div v-if="error" class="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ error }}</span>
          </div>

          <!-- Login Form -->
          <form @submit.prevent="handleLogin" class="space-y-6">
            <div class="space-y-2 text-left">
              <label for="email" class="text-sm font-medium text-slate-300">Email Address</label>
              <input 
                id="email" 
                v-model="email" 
                type="email" 
                required
                placeholder="name@transitops.com"
                class="w-full px-4 py-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-2 text-left">
              <div class="flex justify-between items-center">
                <label for="password" class="text-sm font-medium text-slate-300">Password</label>
              </div>
              <input 
                id="password" 
                v-model="password" 
                type="password" 
                required
                placeholder="••••••••"
                class="w-full px-4 py-3.5 rounded-xl bg-slate-950/80 border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <button 
              type="submit" 
              :disabled="loading"
              class="w-full py-4 px-6 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-lg shadow-indigo-600/20 active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none flex items-center justify-center gap-2"
            >
              <span v-if="loading" class="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              {{ loading ? 'Authenticating...' : 'Sign In to Dashboard' }}
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../store';

export default {
  name: 'Login',
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();

    const email = ref('');
    const password = ref('password'); // All seeded user passwords are "password"
    const loading = ref(false);
    const error = ref('');

    const demoRoles = [
      { name: 'Super Admin', email: 'admin@transitops.com' },
      { name: 'Fleet Manager', email: 'rahul@transitops.com' },
      { name: 'Dispatcher', email: 'amit@transitops.com' },
      { name: 'Accountant', email: 'vikram@transitops.com' },
      { name: 'Driver', email: 'ravi.kumar@transitops.com' }
    ];

    const autofill = (demoEmail) => {
      email.value = demoEmail;
      password.value = 'password';
    };

    const handleLogin = async () => {
      loading.value = true;
      error.value = '';
      try {
        await authStore.login(email.value, password.value);
        router.push({ name: 'Dashboard' });
      } catch (err) {
        error.value = err.response?.data?.message || 'Invalid credentials or connection error.';
      } finally {
        loading.value = false;
      }
    };

    return {
      email,
      password,
      loading,
      error,
      demoRoles,
      autofill,
      handleLogin
    };
  }
}
</script>
