<template>
  <div class="min-h-screen bg-[#0f172a] text-slate-100 font-sans antialiased selection:bg-indigo-500 selection:text-white">
    <router-view />
  </div>
</template>

<script>
import { onMounted } from 'vue';
import { useAuthStore } from './store';

export default {
  name: 'App',
  setup() {
    const authStore = useAuthStore();
    onMounted(() => {
      authStore.fetchMe();
    });
  }
}
</script>
