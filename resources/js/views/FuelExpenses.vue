<template>
  <div class="space-y-10 text-left">
    <!-- Header -->
    <div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">Fuel & Expense Management</h1>
      <p class="text-slate-400 text-sm">Log refuel events, record transit tolls, repairs, and general expenses in a unified journal.</p>
    </div>

    <!-- SECTION 1: FUEL REGISTRY -->
    <div class="space-y-4">
      <div class="flex items-center justify-between border-b border-slate-800 pb-2">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
          Fuel Refuels Journal
        </h2>
        <span class="text-xs text-slate-500 font-mono">Top Section</span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Log Fuel Entry Form (Left) -->
        <div class="lg:col-span-4">
          <div class="p-5 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-350">Log Fuel Entry</h3>
            
            <form @submit.prevent="submitFuelForm" class="space-y-4">
              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Vehicle*</label>
                <select 
                  v-model="fuelForm.vehicle_id" 
                  required
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
                >
                  <option value="">Select vehicle</option>
                  <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.name }} ({{ v.registration_number }})</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Liters*</label>
                  <input 
                    v-model.number="fuelForm.liters" 
                    type="number" 
                    step="0.01"
                    required
                    min="0.1"
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>

                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Cost/Liter (₹)*</label>
                  <input 
                    v-model.number="fuelForm.cost_per_liter" 
                    type="number" 
                    step="0.01"
                    required
                    min="0.1"
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>
              </div>

              <div class="space-y-1 text-left">
                <div class="flex justify-between items-center">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Cost (₹)</label>
                  <span class="text-[9px] text-slate-500">Auto-calc: ₹{{ (fuelForm.liters * fuelForm.cost_per_liter).toFixed(2) }}</span>
                </div>
                <input 
                  v-model.number="fuelForm.total_cost" 
                  type="number" 
                  step="0.01"
                  placeholder="Leave blank to auto-calculate"
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-700 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                />
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Odometer (km)*</label>
                  <input 
                    v-model.number="fuelForm.odometer_km" 
                    type="number" 
                    required
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>

                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Date*</label>
                  <input 
                    v-model="fuelForm.date" 
                    type="date" 
                    required
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-305 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>
              </div>

              <button 
                type="submit"
                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-1.5"
              >
                Log Fuel Entry
              </button>
            </form>
          </div>
        </div>

        <!-- Fuel Logs List Table (Right) -->
        <div class="lg:col-span-8">
          <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                    <th class="py-3 px-4">Vehicle</th>
                    <th class="py-3 px-4">Volume (Liters)</th>
                    <th class="py-3 px-4">Rate (₹/L)</th>
                    <th class="py-3 px-4">Total Cost</th>
                    <th class="py-3 px-4">Odometer</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4 text-right" v-if="hasPermission('fuel-logs.delete')">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-xs text-slate-300">
                  <tr v-if="fuelLogs.length === 0" class="h-28 text-center">
                    <td colspan="7" class="text-slate-500">No fuel entries logged yet.</td>
                  </tr>
                  <tr v-for="log in fuelLogs" :key="log.id" class="hover:bg-slate-900/40">
                    <td class="py-3 px-4 font-mono text-[10px]">
                      <div class="font-bold text-white">{{ log.vehicle?.name }}</div>
                      <div class="text-slate-500 text-[9px] mt-0.5 font-sans">{{ log.vehicle?.registration_number }}</div>
                    </td>
                    <td class="py-3 px-4 text-slate-200">{{ log.liters }} L</td>
                    <td class="py-3 px-4 text-slate-400">₹{{ log.cost_per_liter }}</td>
                    <td class="py-3 px-4 font-semibold text-white">₹{{ log.total_cost }}</td>
                    <td class="py-3 px-4 text-slate-400 font-mono">{{ log.odometer_km }} km</td>
                    <td class="py-3 px-4 text-slate-500">{{ log.date }}</td>
                    <td class="py-3 px-4 text-right" v-if="hasPermission('fuel-logs.delete')">
                      <button 
                        @click="deleteFuelLog(log.id)"
                        class="p-1 rounded bg-rose-500/10 hover:bg-rose-500 text-rose-450 hover:text-white border border-rose-500/20"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Fuel -->
            <div v-if="fuelPagination.last_page > 1" class="p-3 border-t border-slate-800 bg-slate-900/10 flex justify-between items-center text-[10px]">
              <span class="text-slate-500">Page {{ fuelPagination.current_page }} of {{ fuelPagination.last_page }}</span>
              <div class="flex gap-2">
                <button 
                  @click="setFuelPage(fuelPagination.current_page - 1)" 
                  :disabled="fuelPagination.current_page === 1"
                  class="px-2.5 py-1 rounded bg-slate-800 text-slate-350 disabled:opacity-40"
                >Prev</button>
                <button 
                  @click="setFuelPage(fuelPagination.current_page + 1)" 
                  :disabled="fuelPagination.current_page === fuelPagination.last_page"
                  class="px-2.5 py-1 rounded bg-slate-800 text-slate-350 disabled:opacity-40"
                >Next</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SECTION 2: GENERAL EXPENSES -->
    <div class="space-y-4 pt-4">
      <div class="flex items-center justify-between border-b border-slate-800 pb-2">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
          Operational Expenses Ledger
        </h2>
        <span class="text-xs text-slate-500 font-mono">Bottom Section</span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Log Expense Form (Left) -->
        <div class="lg:col-span-4">
          <div class="p-5 rounded-3xl bg-slate-900/40 border border-slate-800 space-y-4">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-350">Log Expense Entry</h3>
            
            <form @submit.prevent="submitExpenseForm" class="space-y-4">
              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Vehicle*</label>
                <select 
                  v-model="expenseForm.vehicle_id" 
                  required
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
                >
                  <option value="">Select vehicle</option>
                  <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.name }} ({{ v.registration_number }})</option>
                </select>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Expense Type*</label>
                  <select 
                    v-model="expenseForm.type" 
                    required
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
                  >
                    <option value="Fuel">Fuel</option>
                    <option value="Tolls">Tolls</option>
                    <option value="Repairs">Repairs</option>
                    <option value="Fines">Fines</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Amount (₹)*</label>
                  <input 
                    v-model.number="expenseForm.amount" 
                    type="number" 
                    required
                    min="1"
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>
              </div>

              <div class="space-y-1 text-left">
                <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Linked Trip (Optional)</label>
                <select 
                  v-model="expenseForm.trip_id" 
                  class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-xs focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer"
                >
                  <option :value="null">None (General expense)</option>
                  <option v-for="t in trips" :key="t.id" :value="t.id">{{ t.source }} → {{ t.destination }} (₹{{ t.revenue }})</option>
                </select>
              </div>

              <div class="grid grid-cols-1 gap-3">
                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Description*</label>
                  <input 
                    v-model="expenseForm.description" 
                    type="text" 
                    required
                    placeholder="e.g. Expressway Toll tax receipt"
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-700 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>

                <div class="space-y-1 text-left">
                  <label class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Expense Date*</label>
                  <input 
                    v-model="expenseForm.date" 
                    type="date" 
                    required
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-slate-305 text-xs focus:outline-none focus:border-indigo-500 transition-colors"
                  />
                </div>
              </div>

              <button 
                type="submit"
                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-1.5"
              >
                Log Expense Entry
              </button>
            </form>
          </div>
        </div>

        <!-- Expenses List Table (Right) -->
        <div class="lg:col-span-8">
          <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-[10px] font-bold uppercase tracking-wider">
                    <th class="py-3 px-4">Vehicle</th>
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4">Linked Trip</th>
                    <th class="py-3 px-4">Description</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4 text-right" v-if="hasPermission('expenses.delete')">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-xs text-slate-300">
                  <tr v-if="expenses.length === 0" class="h-28 text-center">
                    <td colspan="7" class="text-slate-500">No expense logs registered yet.</td>
                  </tr>
                  <tr v-for="exp in expenses" :key="exp.id" class="hover:bg-slate-900/40">
                    <td class="py-3 px-4 font-mono text-[10px]">
                      <div class="font-bold text-white">{{ exp.vehicle?.name }}</div>
                      <div class="text-slate-500 text-[9px] mt-0.5 font-sans">{{ exp.vehicle?.registration_number }}</div>
                    </td>
                    <td class="py-3 px-4">
                      <span 
                        class="px-2 py-0.5 rounded-full text-[9px] border font-semibold"
                        :class="categoryClass(exp.type)"
                      >
                        {{ exp.type }}
                      </span>
                    </td>
                    <td class="py-3 px-4 font-semibold text-slate-400">
                      {{ exp.trip ? `${exp.trip.source} → ${exp.trip.destination}` : 'None' }}
                    </td>
                    <td class="py-3 px-4 text-slate-300 truncate max-w-[150px]">{{ exp.description }}</td>
                    <td class="py-3 px-4 font-bold text-white">₹{{ exp.amount }}</td>
                    <td class="py-3 px-4 text-slate-500">{{ exp.date }}</td>
                    <td class="py-3 px-4 text-right" v-if="hasPermission('expenses.delete')">
                      <button 
                        @click="deleteExpense(exp.id)"
                        class="p-1 rounded bg-rose-500/10 hover:bg-rose-500 text-rose-450 hover:text-white border border-rose-500/20"
                      >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination Expenses -->
            <div v-if="expensePagination.last_page > 1" class="p-3 border-t border-slate-800 bg-slate-900/10 flex justify-between items-center text-[10px]">
              <span class="text-slate-500">Page {{ expensePagination.current_page }} of {{ expensePagination.last_page }}</span>
              <div class="flex gap-2">
                <button 
                  @click="setExpensePage(expensePagination.current_page - 1)" 
                  :disabled="expensePagination.current_page === 1"
                  class="px-2.5 py-1 rounded bg-slate-800 text-slate-350 disabled:opacity-40"
                >Prev</button>
                <button 
                  @click="setExpensePage(expensePagination.current_page + 1)" 
                  :disabled="expensePagination.current_page === expensePagination.last_page"
                  class="px-2.5 py-1 rounded bg-slate-800 text-slate-350 disabled:opacity-40"
                >Next</button>
              </div>
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
  name: 'FuelExpenses',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    // Dynamic Lists & Pagination
    const vehicles = ref([]);
    const trips = ref([]);
    
    const fuelLogs = ref([]);
    const fuelPagination = ref({ current_page: 1, last_page: 1 });

    const expenses = ref([]);
    const expensePagination = ref({ current_page: 1, last_page: 1 });

    // Forms
    const fuelForm = ref({
      vehicle_id: '',
      liters: 0,
      cost_per_liter: 0,
      total_cost: null,
      odometer_km: 0,
      date: new Date().toISOString().split('T')[0]
    });

    const expenseForm = ref({
      vehicle_id: '',
      trip_id: null,
      type: 'Tolls',
      amount: 0,
      description: '',
      date: new Date().toISOString().split('T')[0]
    });

    const fetchData = async () => {
      try {
        const vRes = await axios.get('/vehicles', { params: { per_page: 100 } });
        vehicles.value = vRes.data.data;

        const tRes = await axios.get('/trips', { params: { per_page: 100 } });
        trips.value = tRes.data.data;

        fetchFuelLogs();
        fetchExpenses();
      } catch (err) {
        console.error('Error fetching baseline data:', err);
      }
    };

    const fetchFuelLogs = async () => {
      try {
        const res = await axios.get('/fuel-logs', { params: { page: fuelPagination.value.current_page } });
        fuelLogs.value = res.data.data;
        fuelPagination.value.current_page = res.data.meta.current_page;
        fuelPagination.value.last_page = res.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching fuel logs:', err);
      }
    };

    const fetchExpenses = async () => {
      try {
        const res = await axios.get('/expenses', { params: { page: expensePagination.value.current_page } });
        expenses.value = res.data.data;
        expensePagination.value.current_page = res.data.meta.current_page;
        expensePagination.value.last_page = res.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching expenses:', err);
      }
    };

    const setFuelPage = (page) => {
      fuelPagination.value.current_page = page;
      fetchFuelLogs();
    };

    const setExpensePage = (page) => {
      expensePagination.value.current_page = page;
      fetchExpenses();
    };

    const submitFuelForm = async () => {
      try {
        await axios.post('/fuel-logs', fuelForm.value);
        authStore.showToast('Fuel log entry recorded successfully.', 'success');
        
        fuelForm.value = {
          vehicle_id: '',
          liters: 0,
          cost_per_liter: 0,
          total_cost: null,
          odometer_km: 0,
          date: new Date().toISOString().split('T')[0]
        };
        fetchFuelLogs();
      } catch (err) {
        authStore.showToast('Failed to record fuel log.', 'error');
      }
    };

    const submitExpenseForm = async () => {
      try {
        await axios.post('/expenses', expenseForm.value);
        authStore.showToast('Expense ledger entry recorded.', 'success');
        
        expenseForm.value = {
          vehicle_id: '',
          trip_id: null,
          type: 'Tolls',
          amount: 0,
          description: '',
          date: new Date().toISOString().split('T')[0]
        };
        fetchExpenses();
      } catch (err) {
        authStore.showToast('Failed to log expense.', 'error');
      }
    };

    const deleteFuelLog = (id) => {
      authStore.showConfirm(
        'Delete Fuel Log',
        'Are you sure you want to permanently delete this fuel record?',
        async () => {
          try {
            await axios.delete(`/fuel-logs/${id}`);
            authStore.showToast('Fuel log deleted.', 'success');
            fetchFuelLogs();
          } catch (err) {
            authStore.showToast('Failed to delete fuel record.', 'error');
          }
        }
      );
    };

    const deleteExpense = (id) => {
      authStore.showConfirm(
        'Delete Expense Record',
        'Are you sure you want to delete this expense record permanently?',
        async () => {
          try {
            await axios.delete(`/expenses/${id}`);
            authStore.showToast('Expense record deleted.', 'success');
            fetchExpenses();
          } catch (err) {
            authStore.showToast('Failed to delete expense record.', 'error');
          }
        }
      );
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
      fetchData();
    });

    return {
      vehicles,
      trips,
      fuelLogs,
      fuelPagination,
      expenses,
      expensePagination,
      fuelForm,
      expenseForm,
      hasPermission,
      setFuelPage,
      setExpensePage,
      submitFuelForm,
      submitExpenseForm,
      deleteFuelLog,
      deleteExpense,
      categoryClass
    };
  }
}
</script>
