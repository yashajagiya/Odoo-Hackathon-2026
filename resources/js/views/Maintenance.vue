<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Maintenance Workflow</h1>
        <p class="text-slate-400 text-sm">Issue work orders, manage vehicle downtime, and track repair expenses.</p>
      </div>

      <button 
        v-if="hasPermission('maintenance.create')"
        @click="openModal"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Open Work Order
      </button>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6">Vehicle</th>
              <th class="py-4 px-6">Description</th>
              <th class="py-4 px-6">Status</th>
              <th class="py-4 px-6">Start Date</th>
              <th class="py-4 px-6">Resolution Date</th>
              <th class="py-4 px-6">Maintenance Cost</th>
              <th class="py-4 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="logs.length === 0" class="h-32 text-center">
              <td colspan="7" class="text-slate-500">No maintenance work orders opened yet.</td>
            </tr>
            <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-mono text-xs">
                <div class="font-bold text-white">{{ log.vehicle?.name }}</div>
                <div class="text-slate-500 text-[10px] mt-0.5 font-sans">{{ log.vehicle?.registration_number }}</div>
              </td>
              <td class="py-4 px-6">
                <div class="font-medium text-slate-200">{{ log.description }}</div>
                <div class="text-slate-500 text-[11px] mt-0.5" v-if="log.notes">Notes: {{ log.notes }}</div>
              </td>
              <td class="py-4 px-6">
                <span 
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border"
                  :class="statusClass(log.status)"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(log.status)"></span>
                  {{ log.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-slate-400">{{ log.start_date }}</td>
              <td class="py-4 px-6 text-slate-400">{{ log.end_date || 'In Progress' }}</td>
              <td class="py-4 px-6 font-semibold text-white">₹{{ log.cost?.toLocaleString() }}</td>
              <td class="py-4 px-6 text-right">
                <div class="inline-flex items-center gap-2">
                  <button 
                    v-if="log.status === 'Open' && hasPermission('maintenance.update')"
                    @click="closeWorkOrder(log.id)" 
                    class="py-1 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition-colors shadow-md"
                  >
                    Close Work Order
                  </button>
                  <button 
                    v-if="hasPermission('maintenance.delete')"
                    @click="deleteLog(log.id)" 
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

    <!-- Create Work Order Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">Open Maintenance Work Order</h2>

        <!-- Error display -->
        <div v-if="error" class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-3">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>{{ error }}</span>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Select Vehicle*</label>
            <select 
              v-model="form.vehicle_id" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
            >
              <option value="">Select a vehicle (will transition to In Shop status)</option>
              <option v-for="v in dispatchableVehicles" :key="v.id" :value="v.id">
                {{ v.name }} ({{ v.registration_number }}, Status: {{ v.status }})
              </option>
            </select>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Description of issue/service*</label>
            <textarea 
              v-model="form.description" 
              required
              rows="3"
              placeholder="Detailed description of repairs required..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            ></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Estimated Cost (₹)*</label>
              <input 
                v-model.number="form.cost" 
                type="number" 
                required
                min="0"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Start Date*</label>
              <input 
                v-model="form.start_date" 
                type="date" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Notes / Spare Parts</label>
            <input 
              v-model="form.notes" 
              type="text" 
              placeholder="e.g. Engine oil, Air filter replaced..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
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
              Open Order
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
  name: 'Maintenance',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const logs = ref([]);
    const dispatchableVehicles = ref([]);
    const pagination = ref({ current_page: 1, last_page: 1 });
    const modalOpen = ref(false);
    const error = ref('');

    const form = ref({
      vehicle_id: '',
      description: '',
      cost: 0,
      start_date: new Date().toISOString().split('T')[0],
      notes: ''
    });

    const fetchLogs = async () => {
      try {
        const response = await axios.get('/maintenance-logs', {
          params: { page: pagination.value.current_page }
        });
        logs.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching logs:', err);
      }
    };

    const fetchVehicles = async () => {
      try {
        // Get vehicles that are Available/In Shop to potentially add/close work orders
        const response = await axios.get('/vehicles', { params: { per_page: 100 } });
        // Exclude retired and on-trip vehicles
        dispatchableVehicles.value = response.data.data.filter(v => v.status !== 'Retired' && v.status !== 'On Trip');
      } catch (err) {
        console.error('Error loading vehicles:', err);
      }
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchLogs();
    };

    const openModal = () => {
      error.value = '';
      fetchVehicles();
      form.value = {
        vehicle_id: '',
        description: '',
        cost: 0,
        start_date: new Date().toISOString().split('T')[0],
        notes: ''
      };
      modalOpen.value = true;
    };

    const submitForm = async () => {
      error.value = '';
      try {
        await axios.post('/maintenance-logs', form.value);
        modalOpen.value = false;
        fetchLogs();
      } catch (err) {
        if (err.response?.status === 422) {
          error.value = Object.values(err.response.data.errors).flat().join(' ');
        } else {
          error.value = err.response?.data?.message || 'Failed to open work order.';
        }
      }
    };

    const closeWorkOrder = async (id) => {
      if (!confirm('Are you sure you want to close this work order? Vehicle status will revert to Available.')) return;
      try {
        await axios.post(`/maintenance-logs/${id}/close`);
        fetchLogs();
      } catch (err) {
        alert('Failed to close work order.');
      }
    };

    const deleteLog = async (id) => {
      if (!confirm('Permanently delete this log? (Will not automatically revert vehicle status)')) return;
      try {
        await axios.delete(`/maintenance-logs/${id}`);
        fetchLogs();
      } catch (err) {
        alert('Failed to delete maintenance log.');
      }
    };

    const statusClass = (status) => {
      return status === 'Open' 
        ? 'bg-orange-500/10 text-orange-400 border-orange-500/20' 
        : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
    };

    const statusDotClass = (status) => {
      return status === 'Open' ? 'bg-orange-500' : 'bg-emerald-500';
    };

    onMounted(() => {
      fetchLogs();
    });

    return {
      logs,
      dispatchableVehicles,
      pagination,
      modalOpen,
      form,
      error,
      hasPermission,
      setPage,
      openModal,
      submitForm,
      closeWorkOrder,
      deleteLog,
      statusClass,
      statusDotClass
    };
  }
}
</script>
