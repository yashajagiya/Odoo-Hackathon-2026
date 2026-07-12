<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Driver Management</h1>
        <p class="text-slate-400 text-sm">Monitor driver compliance, safety scores, licensing details, and availability.</p>
      </div>

      <button 
        v-if="hasPermission('drivers.create')"
        @click="openModal()"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Onboard Driver
      </button>
    </div>

    <!-- Filters Panel -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
      <input 
        v-model="search" 
        @input="debounceFetch"
        type="text" 
        placeholder="Search by driver name, license number..."
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
      />

      <select 
        v-model="filters.status" 
        @change="fetchDrivers"
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
      >
        <option value="">All Statuses</option>
        <option value="Available">Available</option>
        <option value="On Trip">On Trip</option>
        <option value="Off Duty">Off Duty</option>
        <option value="Suspended">Suspended</option>
      </select>

      <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800">
        <input 
          id="dispatchable" 
          v-model="filters.dispatchable" 
          @change="fetchDrivers"
          type="checkbox"
          class="rounded border-slate-800 text-indigo-600 focus:ring-indigo-500/20 bg-slate-900 w-4 h-4 cursor-pointer"
        />
        <label for="dispatchable" class="text-slate-300 text-sm font-medium cursor-pointer">Dispatchable Only (Compliant)</label>
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6 cursor-pointer hover:text-white" @click="setSort('name')">Name</th>
              <th class="py-4 px-6">License Details</th>
              <th class="py-4 px-6 cursor-pointer hover:text-white" @click="setSort('license_expiry_date')">License Expiry</th>
              <th class="py-4 px-6">Contact</th>
              <th class="py-4 px-6 cursor-pointer hover:text-white" @click="setSort('safety_score')">Safety Score</th>
              <th class="py-4 px-6">Status</th>
              <th class="py-4 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="drivers.length === 0" class="h-32 text-center">
              <td colspan="7" class="text-slate-500">No drivers onboarded yet.</td>
            </tr>
            <tr v-for="driver in drivers" :key="driver.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-semibold text-white">{{ driver.name }}</td>
              <td class="py-4 px-6 font-mono text-xs">
                <div>{{ driver.license_number }}</div>
                <div class="text-slate-500 text-[10px] uppercase mt-0.5">{{ driver.license_category || 'No Category' }}</div>
              </td>
              <td class="py-4 px-6">
                <div>{{ driver.license_expiry_date || '-' }}</div>
                <!-- Expiry warning pill -->
                <span 
                  v-if="driver.is_license_expired" 
                  class="inline-block mt-1 px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-bold"
                >
                  License Expired
                </span>
                <span 
                  v-else-if="driver.days_until_expiry <= 30 && driver.days_until_expiry >= 0" 
                  class="inline-block mt-1 px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[10px] font-bold animate-pulse"
                >
                  Expires in {{ driver.days_until_expiry }} days
                </span>
              </td>
              <td class="py-4 px-6">{{ driver.contact_number || '-' }}</td>
              <td class="py-4 px-6 font-semibold">
                <span :class="safetyScoreClass(driver.safety_score)">
                  {{ driver.safety_score }} / 100
                </span>
              </td>
              <td class="py-4 px-6">
                <span 
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border"
                  :class="statusClass(driver.status)"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(driver.status)"></span>
                  {{ driver.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-right">
                <div class="inline-flex items-center gap-2">
                  <button 
                    v-if="hasPermission('drivers.update')"
                    @click="openModal(driver)" 
                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button 
                    v-if="hasPermission('drivers.delete')"
                    @click="deleteDriver(driver.id)" 
                    class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="p-4 border-t border-slate-800 bg-slate-900/10 flex justify-between items-center text-xs">
        <span class="text-slate-500">Showing page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button 
            @click="setPage(pagination.current_page - 1)" 
            :disabled="pagination.current_page === 1"
            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 disabled:opacity-40 disabled:pointer-events-none transition-colors"
          >
            Prev
          </button>
          <button 
            @click="setPage(pagination.current_page + 1)" 
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 disabled:opacity-40 disabled:pointer-events-none transition-colors"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- onboard/Edit Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl p-8 relative max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-white mb-6">{{ isEditing ? 'Edit Driver Profile' : 'Onboard New Driver' }}</h2>

        <form @submit.prevent="submitForm" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Full Name*</label>
              <input 
                v-model="form.name" 
                type="text" 
                required 
                placeholder="Ravi Kumar"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Contact Number</label>
              <input 
                v-model="form.contact_number" 
                type="text" 
                placeholder="9876543210"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">License Number*</label>
              <input 
                v-model="form.license_number" 
                type="text" 
                required 
                placeholder="DL-2020-0012345"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
              <span v-if="validationErrors.license_number" class="text-rose-400 text-[10px]">{{ validationErrors.license_number[0] }}</span>
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">License Category*</label>
              <input 
                v-model="form.license_category" 
                type="text" 
                required 
                placeholder="HMV, LMV, etc."
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">License Expiry Date*</label>
              <input 
                v-model="form.license_expiry_date" 
                type="date" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Safety Score (0-100)*</label>
              <input 
                v-model.number="form.safety_score" 
                type="number" 
                min="0"
                max="100"
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Link User Account</label>
              <select 
                v-model="form.user_id" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option :value="null">Unlinked</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
              </select>
            </div>

            <div class="space-y-1.5 text-left" v-if="isEditing">
              <label class="text-xs font-semibold text-slate-400">Status</label>
              <select 
                v-model="form.status" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="Available">Available</option>
                <option value="On Trip">On Trip</option>
                <option value="Off Duty">Off Duty</option>
                <option value="Suspended">Suspended</option>
              </select>
            </div>
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
              class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10"
            >
              {{ isEditing ? 'Save Changes' : 'Onboard Driver' }}
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
  name: 'Drivers',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const drivers = ref([]);
    const users = ref([]);
    const search = ref('');
    const filters = ref({ status: '', dispatchable: false });
    const pagination = ref({ current_page: 1, last_page: 1 });

    const sort_by = ref('created_at');
    const sort_dir = ref('desc');

    const modalOpen = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const validationErrors = ref({});

    const form = ref({
      name: '',
      license_number: '',
      license_category: '',
      license_expiry_date: '',
      contact_number: '',
      safety_score: 100,
      status: 'Available',
      user_id: null
    });

    let debounceTimeout = null;

    const fetchDrivers = async () => {
      try {
        const response = await axios.get('/drivers', {
          params: {
            search: search.value,
            status: filters.value.status,
            dispatchable: filters.value.dispatchable ? 1 : 0,
            page: pagination.value.current_page,
            sort_by: sort_by.value,
            sort_dir: sort_dir.value
          }
        });
        drivers.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error loading drivers:', err);
      }
    };

    const fetchUsers = async () => {
      try {
        const response = await axios.get('/users', { params: { per_page: 100 } });
        // Only show users with role driver (or unlinked ones)
        users.value = response.data.data.filter(u => u.role?.slug === 'driver');
      } catch (err) {
        console.error('Error loading users list:', err);
      }
    };

    const debounceFetch = () => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchDrivers();
      }, 300);
    };

    const setSort = (column) => {
      if (sort_by.value === column) {
        sort_dir.value = sort_dir.value === 'asc' ? 'desc' : 'asc';
      } else {
        sort_by.value = column;
        sort_dir.value = 'asc';
      }
      fetchDrivers();
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchDrivers();
    };

    const openModal = (driver = null) => {
      validationErrors.value = {};
      fetchUsers();
      if (driver) {
        isEditing.value = true;
        editingId.value = driver.id;
        form.value = { ...driver };
      } else {
        isEditing.value = false;
        editingId.value = null;
        form.value = {
          name: '',
          license_number: '',
          license_category: '',
          license_expiry_date: '',
          contact_number: '',
          safety_score: 100,
          status: 'Available',
          user_id: null
        };
      }
      modalOpen.value = true;
    };

    const submitForm = async () => {
      validationErrors.value = {};
      try {
        if (isEditing.value) {
          await axios.put(`/drivers/${editingId.value}`, form.value);
        } else {
          await axios.post('/drivers', form.value);
        }
        modalOpen.value = false;
        fetchDrivers();
      } catch (err) {
        if (err.response?.status === 422) {
          validationErrors.value = err.response.data.errors;
        } else {
          alert('Failed to save driver.');
        }
      }
    };

    const deleteDriver = async (id) => {
      if (!confirm('Are you sure you want to suspend/delete this driver permanently?')) return;
      try {
        await axios.delete(`/drivers/${id}`);
        fetchDrivers();
      } catch (err) {
        alert('Failed to delete driver.');
      }
    };

    const safetyScoreClass = (score) => {
      if (score >= 90) return 'text-emerald-400';
      if (score >= 75) return 'text-amber-400';
      return 'text-rose-400';
    };

    const statusClass = (status) => {
      switch (status) {
        case 'Available': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'On Trip': return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        case 'Off Duty': return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        case 'Suspended': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
      }
    };

    const statusDotClass = (status) => {
      switch (status) {
        case 'Available': return 'bg-emerald-500';
        case 'On Trip': return 'bg-indigo-500';
        case 'Off Duty': return 'bg-slate-400';
        case 'Suspended': return 'bg-rose-500';
        default: return 'bg-slate-500';
      }
    };

    onMounted(() => {
      fetchDrivers();
    });

    return {
      drivers,
      users,
      search,
      filters,
      pagination,
      modalOpen,
      isEditing,
      form,
      validationErrors,
      hasPermission,
      fetchDrivers,
      debounceFetch,
      setSort,
      setPage,
      openModal,
      submitForm,
      deleteDriver,
      safetyScoreClass,
      statusClass,
      statusDotClass
    };
  }
}
</script>
