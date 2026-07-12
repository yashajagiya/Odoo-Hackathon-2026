<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Vehicle Registry</h1>
        <p class="text-slate-400 text-sm">Manage Master list of fleet assets, configurations, and status.</p>
      </div>

      <button 
        v-if="hasPermission('vehicles.create')"
        @click="openModal()"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Register Vehicle
      </button>
    </div>

    <!-- Filters Panel -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-5 rounded-2xl bg-slate-900/40 border border-slate-800">
      <input 
        v-model="search" 
        @input="debounceFetch"
        type="text" 
        placeholder="Search by registration number, name..."
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
      />

      <select 
        v-model="filters.status" 
        @change="fetchVehicles"
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
      >
        <option value="">All Statuses</option>
        <option value="Available">Available</option>
        <option value="On Trip">On Trip</option>
        <option value="In Shop">In Shop</option>
        <option value="Retired">Retired</option>
      </select>

      <select 
        v-model="filters.type" 
        @change="fetchVehicles"
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
      >
        <option value="">All Types</option>
        <option value="Mini Truck">Mini Truck</option>
        <option value="LCV">LCV</option>
        <option value="Pickup">Pickup</option>
        <option value="Medium Truck">Medium Truck</option>
        <option value="Heavy Truck">Heavy Truck</option>
        <option value="ICV">ICV</option>
      </select>

      <select 
        v-model="filters.region" 
        @change="fetchVehicles"
        class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
      >
        <option value="">All Regions</option>
        <option value="Mumbai">Mumbai</option>
        <option value="Pune">Pune</option>
        <option value="Delhi">Delhi</option>
        <option value="Bangalore">Bangalore</option>
        <option value="Chennai">Chennai</option>
        <option value="Ahmedabad">Ahmedabad</option>
        <option value="Jaipur">Jaipur</option>
        <option value="Lucknow">Lucknow</option>
        <option value="Kolkata">Kolkata</option>
      </select>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6 cursor-pointer hover:text-white" @click="setSort('registration_number')">Registration</th>
              <th class="py-4 px-6 cursor-pointer hover:text-white" @click="setSort('name')">Vehicle Name</th>
              <th class="py-4 px-6">Model</th>
              <th class="py-4 px-6">Type</th>
              <th class="py-4 px-6 cursor-pointer hover:text-white" @click="setSort('odometer_km')">Odometer</th>
              <th class="py-4 px-6">Capacity</th>
              <th class="py-4 px-6">Status</th>
              <th class="py-4 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="vehicles.length === 0" class="h-32 text-center">
              <td colspan="8" class="text-slate-500">No vehicles registered yet.</td>
            </tr>
            <tr v-for="vehicle in vehicles" :key="vehicle.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-mono font-bold text-white">{{ vehicle.registration_number }}</td>
              <td class="py-4 px-6 font-semibold">{{ vehicle.name }}</td>
              <td class="py-4 px-6">{{ vehicle.model || '-' }}</td>
              <td class="py-4 px-6">
                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 text-xs">{{ vehicle.type }}</span>
              </td>
              <td class="py-4 px-6">{{ vehicle.odometer_km?.toLocaleString() }} km</td>
              <td class="py-4 px-6">{{ vehicle.max_load_capacity_kg?.toLocaleString() }} kg</td>
              <td class="py-4 px-6">
                <span 
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold border"
                  :class="statusClass(vehicle.status)"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(vehicle.status)"></span>
                  {{ vehicle.status }}
                </span>
              </td>
              <td class="py-4 px-6 text-right">
                <div class="inline-flex items-center gap-2">
                  <button 
                    v-if="hasPermission('vehicles.update')"
                    @click="openModal(vehicle)" 
                    class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <button 
                    v-if="hasPermission('vehicles.delete')"
                    @click="deleteVehicle(vehicle.id)" 
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

    <!-- Creation/Edition Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-xl shadow-2xl p-8 relative max-h-[90vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-white mb-6">{{ isEditing ? 'Edit Vehicle Profile' : 'Register New Vehicle' }}</h2>

        <form @submit.prevent="submitForm" class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Registration Number*</label>
              <input 
                v-model="form.registration_number" 
                type="text" 
                required 
                placeholder="MH-01-AB-1234"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
              <span v-if="validationErrors.registration_number" class="text-rose-400 text-[10px]">{{ validationErrors.registration_number[0] }}</span>
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Vehicle Name*</label>
              <input 
                v-model="form.name" 
                type="text" 
                required 
                placeholder="Tata Ace Gold"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Model</label>
              <input 
                v-model="form.model" 
                type="text" 
                placeholder="Ace Gold Plus"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Type*</label>
              <select 
                v-model="form.type" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="">Select Type</option>
                <option value="Mini Truck">Mini Truck</option>
                <option value="LCV">LCV</option>
                <option value="Pickup">Pickup</option>
                <option value="Medium Truck">Medium Truck</option>
                <option value="Heavy Truck">Heavy Truck</option>
                <option value="ICV">ICV</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Max Capacity (kg)</label>
              <input 
                v-model.number="form.max_load_capacity_kg" 
                type="number" 
                min="0"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Initial Odometer (km)</label>
              <input 
                v-model.number="form.odometer_km" 
                type="number" 
                min="0"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Acquisition Cost (₹)</label>
              <input 
                v-model.number="form.acquisition_cost" 
                type="number" 
                min="0"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Region</label>
              <input 
                v-model="form.region" 
                type="text" 
                placeholder="Mumbai"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left" v-if="isEditing">
              <label class="text-xs font-semibold text-slate-400">Status</label>
              <select 
                v-model="form.status" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="Available">Available</option>
                <option value="On Trip">On Trip</option>
                <option value="In Shop">In Shop</option>
                <option value="Retired">Retired</option>
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
              {{ isEditing ? 'Save Changes' : 'Register Vehicle' }}
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
  name: 'Vehicles',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const vehicles = ref([]);
    const search = ref('');
    const filters = ref({ status: '', type: '', region: '' });
    const pagination = ref({ current_page: 1, last_page: 1 });

    const sort_by = ref('created_at');
    const sort_dir = ref('desc');

    const modalOpen = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const validationErrors = ref({});

    const form = ref({
      registration_number: '',
      name: '',
      model: '',
      type: '',
      max_load_capacity_kg: 0,
      odometer_km: 0,
      acquisition_cost: 0,
      status: 'Available',
      region: ''
    });

    let debounceTimeout = null;

    const fetchVehicles = async () => {
      try {
        const response = await axios.get('/vehicles', {
          params: {
            search: search.value,
            ...filters.value,
            page: pagination.value.current_page,
            sort_by: sort_by.value,
            sort_dir: sort_dir.value
          }
        });
        vehicles.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error loading vehicles:', err);
      }
    };

    const debounceFetch = () => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        fetchVehicles();
      }, 300);
    };

    const setSort = (column) => {
      if (sort_by.value === column) {
        sort_dir.value = sort_dir.value === 'asc' ? 'desc' : 'asc';
      } else {
        sort_by.value = column;
        sort_dir.value = 'asc';
      }
      fetchVehicles();
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchVehicles();
    };

    const openModal = (vehicle = null) => {
      validationErrors.value = {};
      if (vehicle) {
        isEditing.value = true;
        editingId.value = vehicle.id;
        form.value = { ...vehicle };
      } else {
        isEditing.value = false;
        editingId.value = null;
        form.value = {
          registration_number: '',
          name: '',
          model: '',
          type: '',
          max_load_capacity_kg: 0,
          odometer_km: 0,
          acquisition_cost: 0,
          status: 'Available',
          region: ''
        };
      }
      modalOpen.value = true;
    };

    const submitForm = async () => {
      validationErrors.value = {};
      try {
        if (isEditing.value) {
          await axios.put(`/vehicles/${editingId.value}`, form.value);
          authStore.showToast('Vehicle profile updated successfully.', 'success');
        } else {
          await axios.post('/vehicles', form.value);
          authStore.showToast('Vehicle registered successfully.', 'success');
        }
        modalOpen.value = false;
        fetchVehicles();
      } catch (err) {
        if (err.response?.status === 422) {
          validationErrors.value = err.response.data.errors;
        } else {
          authStore.showToast('Failed to save vehicle data.', 'error');
        }
      }
    };

    const deleteVehicle = (id) => {
      authStore.showConfirm(
        'Retire Vehicle',
        'Are you sure you want to retire/delete this vehicle permanently?',
        async () => {
          try {
            await axios.delete(`/vehicles/${id}`);
            authStore.showToast('Vehicle deleted successfully.', 'success');
            fetchVehicles();
          } catch (err) {
            authStore.showToast('Failed to delete vehicle.', 'error');
          }
        }
      );
    };

    const statusClass = (status) => {
      switch (status) {
        case 'Available': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'On Trip': return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        case 'In Shop': return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        case 'Retired': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
      }
    };

    const statusDotClass = (status) => {
      switch (status) {
        case 'Available': return 'bg-emerald-500';
        case 'On Trip': return 'bg-indigo-500';
        case 'In Shop': return 'bg-orange-500';
        case 'Retired': return 'bg-rose-500';
        default: return 'bg-slate-500';
      }
    };

    onMounted(() => {
      fetchVehicles();
    });

    return {
      vehicles,
      search,
      filters,
      pagination,
      modalOpen,
      isEditing,
      form,
      validationErrors,
      hasPermission,
      fetchVehicles,
      debounceFetch,
      setSort,
      setPage,
      openModal,
      submitForm,
      deleteVehicle,
      statusClass,
      statusDotClass
    };
  }
}
</script>
