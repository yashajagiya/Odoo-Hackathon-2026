<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Fuel Registry</h1>
        <p class="text-slate-400 text-sm">Log liters, costs, odometer reading, and analyze fuel efficiency trends.</p>
      </div>

      <button 
        v-if="hasPermission('fuel-logs.create')"
        @click="openModal"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Log Fuel Entry
      </button>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6">Vehicle</th>
              <th class="py-4 px-6">Odometer at Refuel</th>
              <th class="py-4 px-6">Liters</th>
              <th class="py-4 px-6">Cost / Liter</th>
              <th class="py-4 px-6">Total Cost</th>
              <th class="py-4 px-6">Refuel Date</th>
              <th class="py-4 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="logs.length === 0" class="h-32 text-center">
              <td colspan="7" class="text-slate-500">No fuel entries logged yet.</td>
            </tr>
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-mono text-xs">
                <div class="font-bold text-white">{{ log.vehicle?.name }}</div>
                <div class="text-slate-500 text-[10px] mt-0.5 font-sans">{{ log.vehicle?.registration_number }}</div>
              </td>
              <td class="py-4 px-6 font-semibold">{{ log.odometer_km?.toLocaleString() || '-' }} km</td>
              <td class="py-4 px-6 font-semibold text-slate-200">{{ log.liters }} L</td>
              <td class="py-4 px-6 text-slate-400">₹{{ log.cost_per_liter }}</td>
              <td class="py-4 px-6 font-semibold text-white">₹{{ log.total_cost?.toLocaleString() }}</td>
              <td class="py-4 px-6 text-slate-400">{{ log.date }}</td>
              <td class="py-4 px-6 text-right">
                <button 
                  v-if="hasPermission('fuel-logs.delete')"
                  @click="deleteLog(log.id)" 
                  class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
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

    <!-- Create Fuel Log Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">Log Fuel Entry</h2>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Select Vehicle*</label>
            <select 
              v-model="form.vehicle_id" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
            >
              <option value="">Select a vehicle</option>
              <option v-for="v in vehicles" :key="v.id" :value="v.id">
                {{ v.name }} ({{ v.registration_number }}, Odo: {{ v.odometer_km }} km)
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Liters (L)*</label>
              <input 
                v-model.number="form.liters" 
                type="number" 
                step="0.01"
                required
                min="0.1"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Cost per Liter (₹)*</label>
              <input 
                v-model.number="form.cost_per_liter" 
                type="number" 
                step="0.01"
                required
                min="1"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="space-y-1.5 text-left">
            <div class="flex justify-between items-center">
              <label class="text-xs font-semibold text-slate-400">Total Fuel Cost (₹)</label>
              <span class="text-[10px] text-slate-500">Auto-calculated: ₹{{ (form.liters * form.cost_per_liter).toFixed(2) }}</span>
            </div>
            <input 
              v-model.number="form.total_cost" 
              type="number" 
              step="0.01"
              placeholder="Leave empty for auto-calculation"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Odometer at Refuel (km)</label>
              <input 
                v-model.number="form.odometer_km" 
                type="number" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Refuel Date*</label>
              <input 
                v-model="form.date" 
                type="date" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
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
              class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md"
            >
              Log Entry
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
  name: 'FuelLogs',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const logs = ref([]);
    const vehicles = ref([]);
    const pagination = ref({ current_page: 1, last_page: 1 });
    const modalOpen = ref(false);

    const form = ref({
      vehicle_id: '',
      liters: 0,
      cost_per_liter: 0,
      total_cost: null,
      odometer_km: 0,
      date: new Date().toISOString().split('T')[0]
    });

    const fetchLogs = async () => {
      try {
        const response = await axios.get('/fuel-logs', {
          params: { page: pagination.value.current_page }
        });
        logs.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching fuel logs:', err);
      }
    };

    const fetchVehicles = async () => {
      try {
        const response = await axios.get('/vehicles', { params: { per_page: 100 } });
        vehicles.value = response.data.data;
      } catch (err) {
        console.error('Error loading vehicles:', err);
      }
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchLogs();
    };

    const openModal = () => {
      fetchVehicles();
      form.value = {
        vehicle_id: '',
        liters: 0,
        cost_per_liter: 0,
        total_cost: null,
        odometer_km: 0,
        date: new Date().toISOString().split('T')[0]
      };
      modalOpen.value = true;
    };

    const submitForm = async () => {
      try {
        await axios.post('/fuel-logs', form.value);
        modalOpen.value = false;
        fetchLogs();
      } catch (err) {
        alert('Failed to log fuel entry.');
      }
    };

    const deleteLog = async (id) => {
      if (!confirm('Are you sure you want to delete this fuel entry?')) return;
      try {
        await axios.delete(`/fuel-logs/${id}`);
        fetchLogs();
      } catch (err) {
        alert('Failed to delete fuel log.');
      }
    };

    onMounted(() => {
      fetchLogs();
    });

    return {
      logs,
      vehicles,
      pagination,
      modalOpen,
      form,
      hasPermission,
      setPage,
      openModal,
      submitForm,
      deleteLog
    };
  }
}
</script>
