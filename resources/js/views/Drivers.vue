<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Drivers & Safety Profiles</h1>
        <p class="text-slate-400 text-sm">Monitor driver compliance logs, licensing validity, safety scores, and status flags.</p>
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

    <!-- Main Content Layout: Split column for Safety Alerts & Driver Directory -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Left Column: Safety & Compliance Alert Center -->
      <div class="lg:col-span-3 space-y-6">
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-4">
          <div class="flex items-center justify-between pb-2 border-b border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
              Safety & Compliance
            </h3>
            <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-450 border border-rose-500/20 text-[9px] font-bold font-mono">
              {{ flaggedDrivers.length }} Flagged
            </span>
          </div>

          <div v-if="flaggedDrivers.length === 0" class="text-xs text-slate-500 py-6 text-center">
            All drivers are fully compliant. No safety score warnings or license expirations active.
          </div>

          <!-- Alert Cards -->
          <div v-else class="space-y-3 max-h-[50vh] overflow-y-auto pr-1">
            <div 
              v-for="d in flaggedDrivers" 
              :key="d.id"
              class="p-3.5 rounded-2xl bg-slate-950/45 border border-slate-800/80 space-y-2 text-xs"
            >
              <div class="flex justify-between items-start">
                <span class="font-bold text-white text-[13px]">{{ d.name }}</span>
                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-[10px] text-slate-400 font-mono">ID: {{ d.id }}</span>
              </div>

              <!-- Low Safety Score Flag -->
              <div v-if="d.safety_score < 75" class="p-2 rounded bg-rose-500/10 text-rose-400 border border-rose-500/10 text-[10px] space-y-1">
                <div class="font-bold uppercase tracking-wider text-[9px]">⚠️ CRITICAL SAFETY ALERT</div>
                <p class="leading-normal">Safety score dropped to <span class="font-bold text-white">{{ d.safety_score }}%</span>. Suspended status recommended.</p>
              </div>

              <!-- Expired License Flag -->
              <div v-if="d.is_license_expired" class="p-2 rounded bg-rose-500/10 text-rose-450 border border-rose-500/10 text-[10px] space-y-1">
                <div class="font-bold uppercase tracking-wider text-[9px]">🚫 LICENSE EXPIRED</div>
                <p class="leading-normal">License expired on {{ d.license_expiry_date }}. Assigning to active dispatches is blocked.</p>
              </div>

              <!-- License Expiring Soon Flag -->
              <div v-if="!d.is_license_expired && d.days_until_expiry <= 30 && d.days_until_expiry >= 0" class="p-2 rounded bg-amber-500/10 text-amber-400 border border-amber-500/10 text-[10px] space-y-1">
                <div class="font-bold uppercase tracking-wider text-[9px]">⏳ RENEWAL REQUIRED</div>
                <p class="leading-normal">License expires in <span class="font-bold text-white">{{ d.days_until_expiry }} days</span> ({{ d.license_expiry_date }}). Alert dispatched.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Drivers Grid Table -->
      <div class="lg:col-span-9 space-y-6">
        
        <!-- Filters Panel -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-slate-900/40 border border-slate-800">
          <input 
            v-model="search" 
            @input="debounceFetch"
            type="text" 
            placeholder="Search by driver name, license number..."
            class="px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-550 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
          />

          <select 
            v-model="filters.status" 
            @change="fetchDrivers"
            class="px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
          >
            <option value="">All Statuses</option>
            <option value="Available">Available</option>
            <option value="On Trip">On Trip</option>
            <option value="Off Duty">Off Duty</option>
            <option value="Suspended">Suspended</option>
          </select>

          <div class="flex items-center gap-2.5 px-4 py-2 rounded-xl bg-slate-950 border border-slate-800">
            <input 
              id="dispatchable" 
              v-model="filters.dispatchable" 
              @change="fetchDrivers"
              type="checkbox"
              class="rounded border-slate-800 text-indigo-600 focus:ring-indigo-500/20 bg-slate-900 w-4 h-4 cursor-pointer"
            />
            <label for="dispatchable" class="text-slate-350 text-xs font-semibold cursor-pointer">Dispatchable Only (Compliant)</label>
          </div>
        </div>

        <!-- Table -->
        <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                  <th class="py-3 px-5 cursor-pointer hover:text-white" @click="setSort('name')">Driver Name</th>
                  <th class="py-3 px-5">License Number</th>
                  <th class="py-3 px-5 cursor-pointer hover:text-white" @click="setSort('license_expiry_date')">License Expiry</th>
                  <th class="py-3 px-5">Contact Details</th>
                  <th class="py-3 px-5 cursor-pointer hover:text-white" @click="setSort('safety_score')">Safety Score</th>
                  <th class="py-3 px-5">Status</th>
                  <th class="py-3 px-5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800 text-xs text-slate-300">
                <tr v-if="drivers.length === 0" class="h-32 text-center">
                  <td colspan="7" class="text-slate-500">No driver profiles registered.</td>
                </tr>
                <tr v-for="driver in drivers" :key="driver.id" class="hover:bg-slate-900/40 transition-colors">
                  <td class="py-3 px-5 font-semibold text-white">{{ driver.name }}</td>
                  <td class="py-3 px-5 font-mono text-[10px]">
                    <div>{{ driver.license_number }}</div>
                    <div class="text-slate-500 text-[9px] uppercase mt-0.5">{{ driver.license_category || 'No Category' }}</div>
                  </td>
                  <td class="py-3 px-5 text-[10px]">
                    <div class="text-slate-200">{{ driver.license_expiry_date || '-' }}</div>
                    
                    <span 
                      v-if="driver.is_license_expired" 
                      class="inline-block mt-0.5 px-2 py-0.2 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[9px] font-bold"
                    >
                      Expired
                    </span>
                    <span 
                      v-else-if="driver.days_until_expiry <= 30 && driver.days_until_expiry >= 0" 
                      class="inline-block mt-0.5 px-2 py-0.2 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 text-[9px] font-bold animate-pulse"
                    >
                      {{ driver.days_until_expiry }} days left
                    </span>
                  </td>
                  <td class="py-3 px-5 text-slate-400 font-mono">{{ driver.contact_number || '-' }}</td>
                  <td class="py-3 px-5 font-semibold">
                    <span :class="safetyScoreClass(driver.safety_score)">
                      {{ driver.safety_score }} / 100
                    </span>
                  </td>
                  <td class="py-3 px-5">
                    <span 
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border"
                      :class="statusClass(driver.status)"
                    >
                      {{ driver.status }}
                    </span>
                  </td>
                  <td class="py-3 px-5 text-right">
                    <div class="inline-flex items-center gap-1.5 justify-end">
                      <button 
                        v-if="hasPermission('drivers.update')"
                        @click="openModal(driver)" 
                        class="p-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                      </button>
                      <button 
                        v-if="hasPermission('drivers.delete')"
                        @click="deleteDriver(driver.id)" 
                        class="p-1 rounded bg-rose-500/10 hover:bg-rose-500 text-rose-450 hover:text-white border border-rose-500/20 transition-colors"
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

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="p-3 border-t border-slate-800 bg-slate-900/10 flex justify-between items-center text-[10px]">
            <span class="text-slate-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <div class="flex gap-2">
              <button 
                @click="setPage(pagination.current_page - 1)" 
                :disabled="pagination.current_page === 1"
                class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 disabled:opacity-40"
              >
                Prev
              </button>
              <button 
                @click="setPage(pagination.current_page + 1)" 
                :disabled="pagination.current_page === pagination.last_page"
                class="px-2 py-1 rounded bg-slate-800 hover:bg-slate-700 text-slate-300 disabled:opacity-40"
              >
                Next
              </button>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- Onboard Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">{{ isEditing ? 'Edit Driver Profile' : 'Onboard Driver' }}</h2>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Driver Name*</label>
            <input 
              v-model="form.name" 
              type="text" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">License Number*</label>
              <input 
                v-model="form.license_number" 
                type="text" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">License Category*</label>
              <select 
                v-model="form.license_category" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="LMV">LMV (Light Motor Vehicle)</option>
                <option value="HMV">HMV (Heavy Motor Vehicle)</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">License Expiry Date*</label>
              <input 
                v-model="form.license_expiry_date" 
                type="date" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-350 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Contact Number*</label>
              <input 
                v-model="form.contact_number" 
                type="text" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Safety Compliance Score*</label>
              <input 
                v-model.number="form.safety_score" 
                type="number" 
                min="0"
                max="100"
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Linked User Account</label>
              <select 
                v-model="form.user_id" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option :value="null">None (unlinked)</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
              </select>
            </div>
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
import { ref, onMounted, computed } from 'vue';
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
        users.value = response.data.data.filter(u => u.role?.slug === 'driver');
      } catch (err) {
        console.error('Error loading users list:', err);
      }
    };

    const flaggedDrivers = computed(() => {
      return drivers.value.filter(d => 
        d.is_license_expired || 
        (d.days_until_expiry !== null && d.days_until_expiry <= 30) ||
        d.safety_score < 75
      );
    });

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
          license_category: 'LMV',
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
          authStore.showToast('Driver profile updated successfully.', 'success');
        } else {
          await axios.post('/drivers', form.value);
          authStore.showToast('Driver onboarded successfully.', 'success');
        }
        modalOpen.value = false;
        fetchDrivers();
      } catch (err) {
        if (err.response?.status === 422) {
          validationErrors.value = err.response.data.errors;
        } else {
          authStore.showToast('Failed to save driver.', 'error');
        }
      }
    };

    const deleteDriver = (id) => {
      authStore.showConfirm(
        'Offboard Driver',
        'Are you sure you want to suspend/delete this driver permanently?',
        async () => {
          try {
            await axios.delete(`/drivers/${id}`);
            authStore.showToast('Driver offboarded successfully.', 'success');
            fetchDrivers();
          } catch (err) {
            authStore.showToast('Failed to delete driver.', 'error');
          }
        }
      );
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
      flaggedDrivers,
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
