<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Trip Orchestrator</h1>
        <p class="text-slate-400 text-sm">Create, dispatch, monitor, and complete operational dispatches.</p>
      </div>

      <button 
        v-if="hasPermission('trips.create')"
        @click="openCreateModal"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Trip Dispatch
      </button>
    </div>

    <!-- Filters Panel -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
      <input 
        v-model="search" 
        @input="debounceFetch"
        type="text" 
        placeholder="Search by source, destination..."
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
      />

      <select 
        v-model="filters.status" 
        @change="fetchTrips"
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
      >
        <option value="">All Statuses</option>
        <option value="Draft">Draft</option>
        <option value="Dispatched">Dispatched</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
      </select>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6">Route</th>
              <th class="py-4 px-6">Vehicle</th>
              <th class="py-4 px-6">Driver</th>
              <th class="py-4 px-6">Cargo & Distances</th>
              <th class="py-4 px-6">Financials</th>
              <th class="py-4 px-6">Status</th>
              <th class="py-4 px-6 text-right">Dispatch Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="trips.length === 0" class="h-32 text-center">
              <td colspan="7" class="text-slate-500">No dispatches registered yet.</td>
            </tr>
            <tr v-for="trip in trips" :key="trip.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-semibold">
                <div class="text-white">{{ trip.source }} → {{ trip.destination }}</div>
                <div class="text-slate-500 text-[10px] uppercase mt-0.5" v-if="trip.dispatched_at">
                  Dispatched: {{ formatDateTime(trip.dispatched_at) }}
                </div>
              </td>
              <td class="py-4 px-6 font-mono text-xs">
                <div>{{ trip.vehicle?.name }}</div>
                <div class="text-slate-500 text-[10px] mt-0.5 font-sans">{{ trip.vehicle?.registration_number }}</div>
              </td>
              <td class="py-4 px-6">
                <div>{{ trip.driver?.name }}</div>
                <div class="text-slate-500 text-[10px] mt-0.5 uppercase tracking-wide font-semibold">{{ trip.driver?.license_category }}</div>
              </td>
              <td class="py-4 px-6 text-xs space-y-0.5">
                <div>Cargo: <span class="font-semibold text-slate-200">{{ trip.cargo_weight_kg }} kg</span></div>
                <div>Planned: <span class="font-semibold text-slate-200">{{ trip.planned_distance_km }} km</span></div>
                <div v-if="trip.actual_distance_km">Actual: <span class="font-semibold text-emerald-400">{{ trip.actual_distance_km }} km</span></div>
              </td>
              <td class="py-4 px-6 text-xs space-y-0.5">
                <div>Revenue: <span class="font-semibold text-white">₹{{ trip.revenue }}</span></div>
                <div v-if="trip.fuel_consumed_liters">Fuel: <span class="font-semibold text-slate-400">{{ trip.fuel_consumed_liters }} L</span></div>
              </td>
              <td class="py-4 px-6">
                <span 
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border"
                  :class="statusClass(trip.status)"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(trip.status)"></span>
                  {{ trip.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-right">
                <div class="inline-flex items-center gap-1.5 justify-end">
                  <!-- Dispatch draft -->
                  <button 
                    v-if="trip.status === 'Draft' && hasPermission('trips.update')"
                    @click="dispatchTrip(trip.id)" 
                    class="py-1 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition-colors shadow-md shadow-indigo-600/10"
                  >
                    Dispatch
                  </button>
                  
                  <!-- Complete dispatched -->
                  <button 
                    v-if="trip.status === 'Dispatched' && hasPermission('trips.complete')"
                    @click="openCompleteModal(trip)" 
                    class="py-1 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition-colors shadow-md shadow-emerald-600/10"
                  >
                    Complete
                  </button>

                  <!-- Cancel draft/dispatched -->
                  <button 
                    v-if="(trip.status === 'Draft' || trip.status === 'Dispatched') && hasPermission('trips.update')"
                    @click="cancelTrip(trip.id)" 
                    class="py-1 px-3 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-400 hover:text-white border border-rose-500/20 text-xs font-semibold transition-colors"
                  >
                    Cancel
                  </button>
                  
                  <span v-if="trip.status === 'Completed'" class="text-emerald-400 text-xs font-bold inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Finished
                  </span>
                  <span v-if="trip.status === 'Cancelled'" class="text-slate-500 text-xs font-bold">Cancelled</span>
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

    <!-- Create Trip Modal -->
    <div v-if="createModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl p-8 relative max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-white mb-6">Create New Trip</h2>

        <!-- Backend business rule validation error block -->
        <div v-if="backendError" class="mb-5 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-3">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>{{ backendError }}</span>
        </div>

        <form @submit.prevent="submitCreateForm" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Source Location*</label>
              <input 
                v-model="createForm.source" 
                type="text" 
                required 
                placeholder="Mumbai"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Destination Location*</label>
              <input 
                v-model="createForm.destination" 
                type="text" 
                required 
                placeholder="Pune"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Select Available Vehicle*</label>
            <select 
              v-model="createForm.vehicle_id" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
            >
              <option value="">Choose a vehicle (Only Available list)</option>
              <option v-for="v in availableVehicles" :key="v.id" :value="v.id">
                {{ v.name }} - {{ v.registration_number }} (Max: {{ v.max_load_capacity_kg }} kg)
              </option>
            </select>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Select Available Driver*</label>
            <select 
              v-model="createForm.driver_id" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
            >
              <option value="">Choose a driver (Only Available & Valid license)</option>
              <option v-for="d in availableDrivers" :key="d.id" :value="d.id">
                {{ d.name }} (Score: {{ d.safety_score }}, Expiry: {{ d.license_expiry_date }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Cargo Weight (kg)*</label>
              <input 
                v-model.number="createForm.cargo_weight_kg" 
                type="number" 
                required
                min="1"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Planned Distance (km)*</label>
              <input 
                v-model.number="createForm.planned_distance_km" 
                type="number" 
                required
                min="1"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Est. Revenue (₹)*</label>
              <input 
                v-model.number="createForm.revenue" 
                type="number" 
                required
                min="0"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t border-slate-800/60 mt-6">
            <button 
              type="button" 
              @click="createModalOpen = false"
              class="py-2.5 px-5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-xl transition-all"
            >
              Cancel
            </button>
            <button 
              type="submit"
              class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10"
            >
              Save as Draft
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Complete Trip Modal -->
    <div v-if="completeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
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
import { ref, onMounted } from 'vue';
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

    const createModalOpen = ref(false);
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

    const openCreateModal = () => {
      backendError.value = '';
      fetchAvailableAssets();
      createForm.value = {
        source: '',
        destination: '',
        vehicle_id: '',
        driver_id: '',
        cargo_weight_kg: 0,
        planned_distance_km: 0,
        revenue: 0
      };
      createModalOpen.value = true;
    };

    const submitCreateForm = async () => {
      backendError.value = '';
      try {
        await axios.post('/trips', createForm.value);
        createModalOpen.value = false;
        fetchTrips();
      } catch (err) {
        if (err.response?.status === 422) {
          backendError.value = Object.values(err.response.data.errors).flat().join(' ');
        } else {
          alert('Failed to save trip.');
        }
      }
    };

    const dispatchTrip = async (id) => {
      if (!confirm('Are you ready to dispatch this trip? Statuses of vehicle and driver will transition to "On Trip".')) return;
      try {
        await axios.post(`/trips/${id}/dispatch`);
        fetchTrips();
      } catch (err) {
        alert(err.response?.data?.message || 'Dispatch failed due to validation rules.');
      }
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
        fetchTrips();
      } catch (err) {
        alert(err.response?.data?.message || 'Failed to complete trip.');
      }
    };

    const cancelTrip = async (id) => {
      if (!confirm('Are you sure you want to cancel this trip dispatch?')) return;
      try {
        await axios.post(`/trips/${id}/cancel`);
        fetchTrips();
      } catch (err) {
        alert('Failed to cancel trip.');
      }
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
    });

    return {
      trips,
      availableVehicles,
      availableDrivers,
      search,
      filters,
      pagination,
      createModalOpen,
      completeModalOpen,
      createForm,
      completeForm,
      backendError,
      hasPermission,
      fetchTrips,
      debounceFetch,
      setPage,
      openCreateModal,
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
