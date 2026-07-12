<template>
  <div class="space-y-8 text-left">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">Settings & RBAC</h1>
      <p class="text-slate-400 text-sm">Configure role-based access control, user accounts, and system permissions.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Left Column: User Directory & Creation -->
      <div class="lg:col-span-7 space-y-6">
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800">
          <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-white">User Accounts Directory</h3>
            <button 
              v-if="hasPermission('users.create')"
              @click="openUserModal()"
              class="py-1.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs transition-all shadow-md shadow-indigo-600/10"
            >
              Add User
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-800 text-slate-400 text-xs font-bold uppercase tracking-wider bg-slate-950/20">
                  <th class="py-3 px-4">Name</th>
                  <th class="py-3 px-4">Email</th>
                  <th class="py-3 px-4">Role</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-xs text-slate-300">
                <tr v-for="u in users" :key="u.id" class="hover:bg-slate-900/20">
                  <td class="py-3.5 px-4 font-semibold text-white">{{ u.name }}</td>
                  <td class="py-3.5 px-4 text-slate-400 font-mono">{{ u.email }}</td>
                  <td class="py-3.5 px-4">
                    <span class="px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 font-bold uppercase tracking-wider text-[9px]">
                      {{ u.role?.name || 'No Role' }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <div class="inline-flex gap-2" v-if="u.email !== 'admin@transitops.com'">
                      <button 
                        v-if="hasPermission('users.update')"
                        @click="openUserModal(u)" 
                        class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                      </button>
                      <button 
                        v-if="hasPermission('users.delete')"
                        @click="deleteUser(u.id)" 
                        class="p-1 rounded bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Right Column: Roles & Permission Matrix -->
      <div class="lg:col-span-5 space-y-6">
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800">
          <h3 class="text-lg font-bold text-white mb-2">Role Permissions Matrix</h3>
          <p class="text-slate-400 text-xs mb-6">Visual matrix of system capabilities assigned to security roles.</p>
          
          <div class="space-y-4">
            <div 
              v-for="r in roles" 
              :key="r.id"
              class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80 text-xs"
            >
              <div class="flex justify-between items-center border-b border-slate-800 pb-2 mb-2.5">
                <span class="font-bold text-white uppercase tracking-wider text-[11px]">{{ r.name }}</span>
                <span class="text-slate-500 font-mono text-[10px]">{{ r.slug }}</span>
              </div>
              <p class="text-slate-400 text-[11px] mb-3">{{ r.description }}</p>
              
              <div class="flex flex-wrap gap-1.5">
                <span 
                  v-for="perm in r.permissions" 
                  :key="perm"
                  class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[9px]"
                >
                  {{ perm }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- User Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">{{ isEditing ? 'Edit User Details' : 'Create User Account' }}</h2>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Full Name*</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Email Address*</label>
            <input 
              v-model="form.email" 
              type="email" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">{{ isEditing ? 'Password (leave blank to keep current)' : 'Password*' }}</label>
            <input 
              v-model="form.password" 
              type="password" 
              :required="!isEditing"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Assign Role*</label>
            <select 
              v-model="form.role_id" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
            >
              <option value="">Choose role</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/60 mt-6">
            <button 
              type="button" 
              @click="modalOpen = false"
              class="py-2.5 px-5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl transition-all"
            >
              Cancel
            </button>
            <button 
              type="submit"
              class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md"
            >
              {{ isEditing ? 'Save Changes' : 'Create Account' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../store';

export default {
  name: 'Settings',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const users = ref([]);
    const roles = ref([]);
    const modalOpen = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);

    const form = ref({
      name: '',
      email: '',
      password: '',
      role_id: ''
    });

    const fetchData = async () => {
      try {
        const uRes = await axios.get('/users', { params: { per_page: 100 } });
        users.value = uRes.data.data;

        const rRes = await axios.get('/roles');
        roles.value = rRes.data.data;
      } catch (err) {
        console.error('Error fetching settings logs:', err);
      }
    };

    const openUserModal = (user = null) => {
      if (user) {
        isEditing.value = true;
        editingId.value = user.id;
        form.value = {
          name: user.name,
          email: user.email,
          password: '',
          role_id: user.role?.id || ''
        };
      } else {
        isEditing.value = false;
        editingId.value = null;
        form.value = {
          name: '',
          email: '',
          password: '',
          role_id: ''
        };
      }
      modalOpen.value = true;
    };

    const submitForm = async () => {
      try {
        if (isEditing.value) {
          await axios.put(`/users/${editingId.value}`, form.value);
          authStore.showToast('User account updated.', 'success');
        } else {
          await axios.post('/users', form.value);
          authStore.showToast('User account created.', 'success');
        }
        modalOpen.value = false;
        fetchData();
      } catch (err) {
        authStore.showToast('Failed to save user details.', 'error');
      }
    };

    const deleteUser = (id) => {
      authStore.showConfirm(
        'Delete User Account',
        'Are you sure you want to permanently delete this user account?',
        async () => {
          try {
            await axios.delete(`/users/${id}`);
            authStore.showToast('User account deleted.', 'success');
            fetchData();
          } catch (err) {
            authStore.showToast('Failed to delete user account.', 'error');
          }
        }
      );
    };

    onMounted(() => {
      fetchData();
    });

    return {
      users,
      roles,
      modalOpen,
      isEditing,
      form,
      hasPermission,
      openUserModal,
      submitForm,
      deleteUser
    };
  }
}
</script>
