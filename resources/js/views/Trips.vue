<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">Trip Orchestrator</h1>
      <p class="text-slate-400 text-sm">Create, dispatch, monitor, and complete operational dispatches in real-time.</p>
    </div>

    <!-- Main Grid Layout: Form Left, List Right -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Left Column: Dispatcher Form (Wizard) -->
      <div v-if="hasPermission('trips.create')" class="lg:col-span-4 space-y-6">
        <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-5">
          <div class="flex justify-between items-center pb-3 border-b border-slate-800">
            <h2 class="text-lg font-bold text-white">Dispatch Wizard</h2>
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 text-[10px] font-bold uppercase tracking-wider">
              Step 1: Create Draft
            </span>
          </div>

          <!-- Error notification -->
          <div v-if="backendError" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="flex-1">{{ backendError }}</span>
          </div>

          <!-- Dynamic Cargo Weight Warning badge -->
          <div v-if="isWeightWarningActive" class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 text-xs flex items-center gap-2 animate-pulse">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="flex-1 font-semibold">
              Weight Warning: {{ createForm.cargo_weight_kg }} kg exceeds Vehicle Max Limit of {{ selectedVehicle.max_load_capacity_kg }} kg.
            </span>
          </div>

          <form @submit.prevent="submitCreateForm" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Source*</label>
                <input 
                  v-model="createForm.source" 
                  type="text" 
                  required 
                  placeholder="e.g. Mumbai"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-655 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>

              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Destination*</label>
                <input 
                  v-model="createForm.destination" 
                  type="text" 
                  required 
                  placeholder="e.g. Pune"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-655 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>
            </div>

            <div class="space-y-1 text-left">
              <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Select Vehicle*</label>
              <select 
                v-model="createForm.vehicle_id" 
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="">Choose vehicle (Only Available)</option>
                <option v-for="v in availableVehicles" :key="v.id" :value="v.id">
                  {{ v.name }} ({{ v.registration_number }} - {{ v.max_load_capacity_kg }}kg)
                </option>
              </select>
            </div>

            <div class="space-y-1 text-left">
              <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Select Driver*</label>
              <select 
                v-model="createForm.driver_id" 
                required
                class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="">Choose driver (Only Available)</option>
                <option v-for="d in availableDrivers" :key="d.id" :value="d.id">
                  {{ d.name }} (Score: {{ d.safety_score }})
                </option>
              </select>
            </div>

            <div class="grid grid-cols-3 gap-2">
              <div class="space-y-1 text-left">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Cargo (kg)*</label>
                <input 
                  v-model.number="createForm.cargo_weight_kg" 
                  type="number" 
                  required
                  min="1"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>

              <div class="space-y-1 text-left">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Dist. (km)*</label>
                <input 
                  v-model.number="createForm.planned_distance_km" 
                  type="number" 
                  required
                  min="1"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>

              <div class="space-y-1 text-left">
                <label class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Revenue*</label>
                <input 
                  v-model.number="createForm.revenue" 
                  type="number" 
                  required
                  min="0"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>
            </div>

            <button 
              type="submit"
              :disabled="isWeightWarningActive"
              class="w-full py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs transition-all shadow-md disabled:opacity-55 disabled:cursor-not-allowed flex items-center justify-center gap-1.5"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
              </svg>
              Create Draft Trip
            </button>
          </form>
        </div>
      </div>

      <!-- Right Column: Live Trips Directory -->
      <div :class="hasPermission('trips.create') ? 'lg:col-span-8' : 'lg:col-span-12'" class="space-y-6">
        
        <!-- Filters Panel -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-900/40 border border-slate-800">
          <input 
            v-model="search" 
            @input="debounceFetch"
            type="text" 
            placeholder="Search by source, destination..."
            class="px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
          />

          <select 
            v-model="filters.status" 
            @change="fetchTrips"
            class="px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
          >
            <option value="">All Statuses</option>
            <option value="Draft">Draft</option>
            <option value="Dispatched">Dispatched</option>
            <option value="Completed">Completed</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>

        <!-- Table -->
        <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                  <th class="py-3 px-4">Route Details</th>
                  <th class="py-3 px-4">Vehicle</th>
                  <th class="py-3 px-4">Driver</th>
                  <th class="py-3 px-4">Cargo & Distance</th>
                  <th class="py-3 px-4">Revenue</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800 text-xs text-slate-300">
                <tr v-if="trips.length === 0" class="h-32 text-center">
                  <td colspan="7" class="text-slate-500 text-xs">No dispatches registered yet.</td>
                </tr>
                <tr v-for="trip in trips" :key="trip.id" class="hover:bg-slate-900/40 transition-colors">
                  <td class="py-3 px-4 font-semibold">
                    <div class="text-white">{{ trip.source }} → {{ trip.destination }}</div>
                    <div class="text-slate-500 text-[9px] uppercase mt-0.5" v-if="trip.dispatched_at">
                      {{ formatDateTime(trip.dispatched_at) }}
                    </div>
                  </td>
                  <td class="py-3 px-4 font-mono text-[10px]">
                    <div>{{ trip.vehicle?.name }}</div>
                    <div class="text-slate-500 text-[9px] mt-0.5 font-sans">{{ trip.vehicle?.registration_number }}</div>
                  </td>
                  <td class="py-3 px-4">
                    <div>{{ trip.driver?.name }}</div>
                  </td>
                  <td class="py-3 px-4 text-[10px]">
                    <div>{{ trip.cargo_weight_kg }} kg</div>
                    <div class="text-slate-500 mt-0.5">Plnd: {{ trip.planned_distance_km }} km</div>
                  </td>
                  <td class="py-3 px-4 font-semibold text-slate-200">₹{{ trip.revenue }}</td>
                  <td class="py-3 px-4">
                    <span 
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold border"
                      :class="statusClass(trip.status)"
                    >
                      {{ trip.status }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-right">
                    <div class="inline-flex items-center gap-1 justify-end">
                      <button 
                        v-if="trip.status === 'Draft' && hasPermission('trips.update')"
                        @click="dispatchTrip(trip.id)" 
                        class="py-1 px-2.5 rounded bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-[10px] transition-colors"
                      >
                        Dispatch
                      </button>
                      <button 
                        v-if="trip.status === 'Dispatched' && hasPermission('trips.complete')"
                        @click="openCompleteModal(trip)" 
                        class="py-1 px-2.5 rounded bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-[10px] transition-colors"
                      >
                        Complete
                      </button>
                      <button 
                        v-if="(trip.status === 'Draft' || trip.status === 'Dispatched') && hasPermission('trips.update')"
                        @click="cancelTrip(trip.id)" 
                        class="py-1 px-2 rounded bg-rose-500/10 hover:bg-rose-500 text-rose-450 hover:text-white border border-rose-500/20 text-[10px] font-semibold transition-colors"
                      >
                        Cancel
                      </button>
                      <span v-if="trip.status === 'Completed'" class="text-emerald-400 text-[10px] font-bold">Finished</span>
                      <span v-if="trip.status === 'Cancelled'" class="text-slate-500 text-[10px] font-bold">Cancelled</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="pagination.last_page > 1" class="p-3 border-t border-slate-800 bg-slate-900/10 flex justify-between items-center text-[10px]">
            <span class="text-slate-500">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
            <div class="flex gap-2.5">
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

    <!-- Complete Trip Modal (Still needed for details capturing) -->
    <div v-if="completeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">Complete Trip Execution</h2>
        <p class="text-xs text-slate-400 mb-4">Record actual odometer distance traveled and fuel consumed to restore asset availability.</p>

        <form @submit.prevent="submitCompleteForm" class="space-y-4">
          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Actual Distance Traveled (km)*</label>
            <input 
              v-model.number="completeForm.actual_distance_km" 
              type="number" 
              required
              min="1"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Fuel Consumed (Liters)</label>
            <input 
              v-model.number="completeForm.fuel_consumed_liters" 
              type="number" 
              min="0"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Actual Revenue Realized (₹)*</label>
            <input 
              v-model.number="completeForm.revenue" 
              type="number" 
              required
              min="0"
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/60 mt-6">
            <button 
              type="button" 
              @click="completeModalOpen = false"
              class="py-2.5 px-5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl transition-all"
            >
              Cancel
            </button>
            <button 
              type="submit"
              class="py-2.5 px-5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-emerald-600/10"
            >
              Complete Trip
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
  name: 'Trips',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const trips = ref([]);
    const availableVehicles = ref([]);
    const availableDrivers = ref([]);
    const search = ref('');
    const filters = ref({ status: '' });
    const pagination = ref({ current_page: 1, last_page: 1 });

    const completeModalOpen = ref(false);
    const selectedTrip = ref(null);
    const backendError = ref('');

    const createForm = ref({
      source: '',
      destination: '',
      vehicle_id: '',
      driver_id: '',
      cargo_weight_kg: 0,
      planned_distance_km: 0,
      revenue: 0
    });

    const completeForm = ref({
      actual_distance_km: 0,
      fuel_consumed_liters: null,
      revenue: 0
    });

    let debounceTimeout = null;

    const fetchTrips = async () => {
      try {
        const response = await axios.get('/trips', {
          params: {
            search: search.value,
            status: filters.value.status,
            page: pagination.value.current_page
          }
        });
        trips.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching trips:', err);
      }
    };

    const fetchAvailableAssets = async () => {
      try {
        const vRes = await axios.get('/vehicles', { params: { status: 'Available', per_page: 100 } });
        availableVehicles.value = vRes.data.data;

        const dRes = await axios.get('/drivers', { params: { status: 'Available', dispatchable: 1, per_page: 100 } });
        availableDrivers.value = dRes.data.data;
      } catch (err) {
        console.error('Error fetching available assets:', err);
      }
    };

    const selectedVehicle = computed(() => {
      return availableVehicles.value.find(v => v.id === createForm.value.vehicle_id);
    });

    const isWeightWarningActive = computed(() => {
      if (!selectedVehicle.value) return false;
      return createForm.value.cargo_weight_kg > selectedVehicle.value.max_load_capacity_kg;
    });

    const debounceFetch = () => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchTrips();
      }, 300);
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchTrips();
    };

    const submitCreateForm = async () => {
      backendError.value = '';
      try {
        await axios.post('/trips', createForm.value);
        authStore.showToast('Trip created as Draft.', 'success');
        
        // Reset form
        createForm.value = {
          source: '',
          destination: '',
          vehicle_id: '',
          driver_id: '',
          cargo_weight_kg: 0,
          planned_distance_km: 0,
          revenue: 0
        };

        // Reload data
        fetchTrips();
        fetchAvailableAssets();
      } catch (err) {
        if (err.response?.status === 422) {
          backendError.value = Object.values(err.response.data.errors).flat().join(' ');
        } else {
          authStore.showToast('Failed to save trip.', 'error');
        }
      }
    };

    const dispatchTrip = (id) => {
      authStore.showConfirm(
        'Dispatch Trip',
        'Are you ready to dispatch this trip? Statuses of vehicle and driver will transition to "On Trip".',
        async () => {
          try {
            await axios.post(`/trips/${id}/dispatch`);
            authStore.showToast('Trip dispatched successfully.', 'success');
            fetchTrips();
            fetchAvailableAssets();
          } catch (err) {
            authStore.showToast(err.response?.data?.message || 'Dispatch failed due to validation rules.', 'error');
          }
        }
      );
    };

    const openCompleteModal = (trip) => {
      selectedTrip.value = trip;
      completeForm.value = {
        actual_distance_km: trip.planned_distance_km,
        fuel_consumed_liters: null,
        revenue: trip.revenue
      };
      completeModalOpen.value = true;
    };

    const submitCompleteForm = async () => {
      try {
        await axios.post(`/trips/${selectedTrip.value.id}/complete`, completeForm.value);
        completeModalOpen.value = false;
        authStore.showToast('Trip marked as Completed.', 'success');
        fetchTrips();
        fetchAvailableAssets();
      } catch (err) {
        authStore.showToast(err.response?.data?.message || 'Failed to complete trip.', 'error');
      }
    };

    const cancelTrip = (id) => {
      authStore.showConfirm(
        'Cancel Dispatch',
        'Are you sure you want to cancel this trip dispatch?',
        async () => {
          try {
            await axios.post(`/trips/${id}/cancel`);
            authStore.showToast('Trip cancelled successfully.', 'success');
            fetchTrips();
            fetchAvailableAssets();
          } catch (err) {
            authStore.showToast('Failed to cancel trip.', 'error');
          }
        }
      );
    };

    const formatDateTime = (val) => {
      if (!val) return '';
      return new Date(val).toLocaleString('en-IN', { dateStyle: 'short', timeStyle: 'short' });
    };

    const statusClass = (status) => {
      switch (status) {
        case 'Draft': return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
        case 'Dispatched': return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        case 'Completed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'Cancelled': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
      }
    };

    const statusDotClass = (status) => {
      switch (status) {
        case 'Draft': return 'bg-slate-400';
        case 'Dispatched': return 'bg-indigo-500';
        case 'Completed': return 'bg-emerald-500';
        case 'Cancelled': return 'bg-rose-500';
        default: return 'bg-slate-500';
      }
    };

    onMounted(() => {
      fetchTrips();
      fetchAvailableAssets();
    });

    return {
      trips,
      availableVehicles,
      availableDrivers,
      search,
      filters,
      pagination,
      completeModalOpen,
      createForm,
      completeForm,
      backendError,
      hasPermission,
      selectedVehicle,
      isWeightWarningActive,
      fetchTrips,
      debounceFetch,
      setPage,
      submitCreateForm,
      dispatchTrip,
      openCompleteModal,
      submitCompleteForm,
      cancelTrip,
      formatDateTime,
      statusClass,
      statusDotClass
    };
  }
}
</script>
