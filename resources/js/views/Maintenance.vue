<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">Maintenance Workflow</h1>
      <p class="text-slate-400 text-sm">Issue work orders, manage vehicle downtime, and track repair expenses in real-time.</p>
    </div>

    <!-- Grid: Form Left, Table Right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Left Column: Open Work Order Form -->
      <div class="lg:col-span-4 space-y-6">
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-5">
          <div class="flex justify-between items-center pb-3 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white">Create Work Order</h2>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-orange-500/10 text-orange-400 border border-orange-500/20 text-[10px] font-bold uppercase tracking-wider">
              Downtime Trigger
            </span>
          </div>

          <!-- Error Block -->
          <div v-if="error" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="flex-1">{{ error }}</span>
          </div>

          <form @submit.prevent="submitForm" class="space-y-4">
            <div class="space-y-1 text-left">
              <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Select Vehicle*</label>
              <select 
                v-model="form.vehicle_id" 
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="">Choose vehicle (Transition to In Shop)</option>
                <option v-for="v in dispatchableVehicles" :key="v.id" :value="v.id">
                  {{ v.name }} ({{ v.registration_number }}, Status: {{ v.status }})
                </option>
              </select>
            </div>

            <div class="space-y-1 text-left">
              <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Issue Description*</label>
              <textarea 
                v-model="form.description" 
                required
                rows="3"
                placeholder="Describe the issue, e.g. scheduled engine oil change or broken indicator light..."
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-650 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Est. Cost (₹)*</label>
                <input 
                  v-model.number="form.cost" 
                  type="number" 
                  required
                  min="0"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>

              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Start Date*</label>
                <input 
                  v-model="form.start_date" 
                  type="date" 
                  required
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>
            </div>

            <div class="space-y-1 text-left">
              <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Notes / Spare Parts</label>
              <input 
                v-model="form.notes" 
                type="text" 
                placeholder="e.g. Air filter, new brake pads"
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-650 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <button 
              type="submit"
              class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
              </svg>
              Open Work Order
            </button>
          </form>
        </div>
      </div>

      <!-- Right Column: Table List -->
      <div class="lg:col-span-8 space-y-6">
        <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                  <th class="py-3 px-4">Vehicle Details</th>
                  <th class="py-3 px-4">Issue / Description</th>
                  <th class="py-3 px-4">Timeline</th>
                  <th class="py-3 px-4">Cost</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800 text-xs text-slate-300">
                <tr v-if="logs.length === 0" class="h-32 text-center">
                  <td colspan="6" class="text-slate-500 text-xs">No maintenance work orders opened yet.</td>
                </tr>
                <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-900/40 transition-colors">
                  <td class="py-3.5 px-4 font-mono text-[10px]">
                    <div class="font-bold text-white">{{ log.vehicle?.name }}</div>
                    <div class="text-slate-500 text-[9px] mt-0.5 font-sans">{{ log.vehicle?.registration_number }}</div>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="font-medium text-slate-200">{{ log.description }}</div>
                    <div class="text-slate-500 text-[10px] mt-0.5" v-if="log.notes">Notes: {{ log.notes }}</div>
                  </td>
                  <td class="py-3.5 px-4 text-[10px] text-slate-400">
                    <div>Start: {{ log.start_date }}</div>
                    <div class="mt-0.5">End: {{ log.end_date || 'In Progress' }}</div>
                  </td>
                  <td class="py-3.5 px-4 font-semibold text-white">₹{{ log.cost?.toLocaleString() }}</td>
                  <td class="py-3.5 px-4">
                    <span 
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border"
                      :class="statusClass(log.status)"
                    >
                      {{ log.status }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <div class="inline-flex items-center gap-2 justify-end">
                      <button 
                        v-if="log.status === 'Open' && hasPermission('maintenance.update')"
                        @click="closeWorkOrder(log.id)" 
                        class="py-1 px-2.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[10px] transition-colors"
                      >
                        Close Order
                      </button>
                      <button 
                        v-if="hasPermission('maintenance.delete')"
                        @click="deleteLog(log.id)" 
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
        const response = await axios.get('/vehicles', { params: { per_page: 100 } });
        dispatchableVehicles.value = response.data.data.filter(v => v.status !== 'Retired' && v.status !== 'On Trip');
      } catch (err) {
        console.error('Error loading vehicles:', err);
      }
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchLogs();
    };

    const submitForm = async () => {
      error.value = '';
      try {
        await axios.post('/maintenance-logs', form.value);
        authStore.showToast('Work order opened successfully. Vehicle status set to In Shop.', 'success');
        
        // Reset form
        form.value = {
          vehicle_id: '',
          description: '',
          cost: 0,
          start_date: new Date().toISOString().split('T')[0],
          notes: ''
        };

        fetchLogs();
        fetchVehicles();
      } catch (err) {
        if (err.response?.status === 422) {
          error.value = Object.values(err.response.data.errors).flat().join(' ');
        } else {
          error.value = err.response?.data?.message || 'Failed to open work order.';
          authStore.showToast(error.value, 'error');
        }
      }
    };

    const closeWorkOrder = (id) => {
      authStore.showConfirm(
        'Close Work Order',
        'Are you sure you want to close this work order? Vehicle status will revert to Available.',
        async () => {
          try {
            await axios.post(`/maintenance-logs/${id}/close`);
            authStore.showToast('Work order closed successfully. Vehicle status restored.', 'success');
            fetchLogs();
            fetchVehicles();
          } catch (err) {
            authStore.showToast('Failed to close work order.', 'error');
          }
        }
      );
    };

    const deleteLog = (id) => {
      authStore.showConfirm(
        'Delete Log',
        'Permanently delete this log? (Will not automatically revert vehicle status)',
        async () => {
          try {
            await axios.delete(`/maintenance-logs/${id}`);
            authStore.showToast('Maintenance log deleted successfully.', 'success');
            fetchLogs();
            fetchVehicles();
          } catch (err) {
            authStore.showToast('Failed to delete maintenance log.', 'error');
          }
        }
      );
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
      fetchVehicles();
    });

    return {
      logs,
      dispatchableVehicles,
      pagination,
      form,
      error,
      hasPermission,
      setPage,
      submitForm,
      closeWorkOrder,
      deleteLog,
      statusClass,
      statusDotClass
    };
  }
}
</script>
