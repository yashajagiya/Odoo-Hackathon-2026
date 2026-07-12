<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Expense Ledger</h1>
        <p class="text-slate-400 text-sm">Track tolls, fines, repairs, and other operational expenses.</p>
      </div>

      <button 
        v-if="hasPermission('expenses.create')"
        @click="openModal"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Log Expense
      </button>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6">Vehicle</th>
              <th class="py-4 px-6">Category</th>
              <th class="py-4 px-6">Description</th>
              <th class="py-4 px-6">Expense Date</th>
              <th class="py-4 px-6">Amount</th>
              <th class="py-4 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="expenses.length === 0" class="h-32 text-center">
              <td colspan="6" class="text-slate-500">No expenses logged in ledger yet.</td>
            </tr>
            <tr v-for="expense in expenses" :key="expense.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-mono text-xs">
                <div class="font-bold text-white">{{ expense.vehicle?.name }}</div>
                <div class="text-slate-500 text-[10px] mt-0.5 font-sans">{{ expense.vehicle?.registration_number }}</div>
              </td>
              <td class="py-4 px-6">
                <span 
                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold border"
                  :class="categoryClass(expense.type)"
                >
                  {{ expense.type }}
                </span>
              </td>
              <td class="py-4 px-6">
                <div class="text-slate-200">{{ expense.description || 'No Description' }}</div>
                <div class="text-slate-500 text-[10px] uppercase mt-0.5" v-if="expense.trip">
                  Trip Route: {{ expense.trip.source }} → {{ expense.trip.destination }}
                </div>
              </td>
              <td class="py-4 px-6 text-slate-400">{{ expense.date }}</td>
              <td class="py-4 px-6 font-extrabold text-white text-base">₹{{ expense.amount?.toLocaleString() }}</td>
              <td class="py-4 px-6 text-right">
                <button 
                  v-if="hasPermission('expenses.delete')"
                  @click="deleteExpense(expense.id)" 
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

    <!-- Create Expense Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">Log Expense Entry</h2>

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
                {{ v.name }} ({{ v.registration_number }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Expense Type*</label>
              <select 
                v-model="form.type" 
                required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
              >
                <option value="Fuel">Fuel</option>
                <option value="Tolls">Tolls</option>
                <option value="Repairs">Repairs</option>
                <option value="Fines">Fines</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Amount (₹)*</label>
              <input 
                v-model.number="form.amount" 
                type="number" 
                required
                min="1"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Select Trip (Optional)</label>
            <select 
              v-model="form.trip_id" 
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
            >
              <option :value="null">None</option>
              <option v-for="t in trips" :key="t.id" :value="t.id">
                {{ t.source }} → {{ t.destination }} (₹{{ t.revenue }})
              </option>
            </select>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Description*</label>
              <input 
                v-model="form.description" 
                type="text" 
                required
                placeholder="e.g. NH-4 Expressway toll payment..."
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Expense Date*</label>
            <input 
              v-model="form.date" 
              type="date" 
              required
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
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
              Log Expense
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
  name: 'Expenses',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const expenses = ref([]);
    const vehicles = ref([]);
    const trips = ref([]);
    const pagination = ref({ current_page: 1, last_page: 1 });
    const modalOpen = ref(false);

    const form = ref({
      vehicle_id: '',
      trip_id: null,
      type: 'Tolls',
      amount: 0,
      description: '',
      date: new Date().toISOString().split('T')[0]
    });

    const fetchExpenses = async () => {
      try {
        const response = await axios.get('/expenses', {
          params: { page: pagination.value.current_page }
        });
        expenses.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching expenses:', err);
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

    const fetchTrips = async () => {
      try {
        const response = await axios.get('/trips', { params: { per_page: 100 } });
        trips.value = response.data.data;
      } catch (err) {
        console.error('Error loading trips:', err);
      }
    };

    const setPage = (page) => {
      pagination.value.current_page = page;
      fetchExpenses();
    };

    const openModal = () => {
      fetchVehicles();
      fetchTrips();
      form.value = {
        vehicle_id: '',
        trip_id: null,
        type: 'Tolls',
        amount: 0,
        description: '',
        date: new Date().toISOString().split('T')[0]
      };
      modalOpen.value = true;
    };

    const submitForm = async () => {
      try {
        await axios.post('/expenses', form.value);
        modalOpen.value = false;
        fetchExpenses();
      } catch (err) {
        alert('Failed to log expense.');
      }
    };

    const deleteExpense = async (id) => {
      if (!confirm('Are you sure you want to delete this expense log?')) return;
      try {
        await axios.delete(`/expenses/${id}`);
        fetchExpenses();
      } catch (err) {
        alert('Failed to delete expense.');
      }
    };

    const categoryClass = (type) => {
      switch (type) {
        case 'Fuel': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'Tolls': return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        case 'Repairs': return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        case 'Fines': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
      }
    };

    onMounted(() => {
      fetchExpenses();
    });

    return {
      expenses,
      vehicles,
      trips,
      pagination,
      modalOpen,
      form,
      hasPermission,
      setPage,
      openModal,
      submitForm,
      deleteExpense,
      categoryClass
    };
  }
}
</script>
