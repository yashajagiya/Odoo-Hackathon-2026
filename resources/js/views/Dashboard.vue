<template>
  <div class="space-y-8 text-left">
    <!-- Header with Filters -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Operations Command</h1>
        <p class="text-slate-400 text-sm">Real-time KPI metrics and fleet status monitoring.</p>
      </div>

      <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
        <select 
          v-model="filters.type" 
          @change="fetchDashboardData"
          class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer w-full sm:w-auto"
        >
          <option value="">All Vehicle Types</option>
          <option value="Mini Truck">Mini Truck</option>
          <option value="LCV">LCV</option>
          <option value="Pickup">Pickup</option>
          <option value="Medium Truck">Medium Truck</option>
          <option value="Heavy Truck">Heavy Truck</option>
          <option value="ICV">ICV</option>
        </select>

        <select 
          v-model="filters.region" 
          @change="fetchDashboardData"
          class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors cursor-pointer w-full sm:w-auto"
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

        <button 
          @click="fetchDashboardData"
          class="p-2 py-2 px-3 rounded-xl bg-indigo-600/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center gap-2 w-full sm:w-auto justify-center"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.248 8H18.25" />
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="i in 4" :key="i" class="h-32 bg-slate-900/50 border border-slate-800 animate-pulse rounded-3xl"></div>
    </div>

    <!-- KPI Grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Card 1: Available Vehicles -->
      <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-500/5 rounded-bl-[100px] pointer-events-none group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Available Fleet</p>
            <h3 class="text-3xl font-extrabold text-white mt-1">{{ kpis?.vehicles?.available }} <span class="text-sm font-normal text-slate-500">/ {{ kpis?.vehicles?.total }}</span></h3>
          </div>
        </div>
      </div>

      <!-- Card 2: Active Dispatches -->
      <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-500/5 rounded-bl-[100px] pointer-events-none group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Active Dispatches</p>
            <h3 class="text-3xl font-extrabold text-white mt-1">{{ kpis?.trips?.active }} <span class="text-sm font-normal text-slate-500">trips</span></h3>
          </div>
        </div>
      </div>

      <!-- Card 3: Fleet Utilization -->
      <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-24 h-24 bg-violet-500/5 rounded-bl-[100px] pointer-events-none group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <div>
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Fleet Utilization</p>
            <h3 class="text-3xl font-extrabold text-white mt-1">{{ kpis?.fleet_utilization_percent }}%</h3>
          </div>
        </div>
      </div>

      <!-- Card 4: Net Financial Margin -->
      <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800 relative overflow-hidden group">
        <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-500/5 rounded-bl-[100px] pointer-events-none group-hover:scale-110 transition-transform"></div>
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div>
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Net Operations Margin</p>
            <h3 class="text-3xl font-extrabold text-white mt-1">₹{{ formatNumber(kpis?.financials?.net_profit) }}</h3>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts and Stats Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Chart Left -->
      <div class="lg:col-span-8 p-6 rounded-3xl bg-slate-900/40 border border-slate-800 flex flex-col justify-between">
        <div>
          <h3 class="text-lg font-bold text-white mb-2">Financial Operational Split</h3>
          <p class="text-slate-400 text-xs mb-6">Revenue structure contrasted against fuel, maintenance, and auxiliary expenses.</p>
        </div>
        <div class="h-80 w-full relative">
          <canvas id="financialChart"></canvas>
        </div>
      </div>

      <!-- Chart Right / Status summary -->
      <div class="lg:col-span-4 p-6 rounded-3xl bg-slate-900/40 border border-slate-800 flex flex-col justify-between">
        <div>
          <h3 class="text-lg font-bold text-white mb-2">Fleet Status Allocation</h3>
          <p class="text-slate-400 text-xs mb-6">Breakdown of operational states.</p>
        </div>
        <div class="h-60 w-full relative flex items-center justify-center">
          <canvas id="statusChart"></canvas>
        </div>
        
        <div class="grid grid-cols-2 gap-2 mt-6 pt-4 border-t border-slate-800/65 text-xs">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
            <span class="text-slate-400">Available: {{ kpis?.vehicles?.available || 0 }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
            <span class="text-slate-400">On Trip: {{ kpis?.vehicles?.on_trip || 0 }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
            <span class="text-slate-400">In Shop: {{ kpis?.vehicles?.in_shop || 0 }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
            <span class="text-slate-400">Retired: {{ kpis?.vehicles?.retired || 0 }}</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Financial detail panel -->
    <div class="p-6 rounded-3xl bg-slate-900/40 border border-slate-800">
      <h3 class="text-lg font-bold text-white mb-6">Operations Financial Summary</h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
        <div class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80">
          <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Gross Revenue</p>
          <p class="text-2xl font-extrabold text-white mt-1">₹{{ formatNumber(kpis?.financials?.total_revenue) }}</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80">
          <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Fuel Cost Rollup</p>
          <p class="text-2xl font-extrabold text-rose-400 mt-1">₹{{ formatNumber(kpis?.financials?.total_fuel_cost) }}</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80">
          <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Maintenance Outflow</p>
          <p class="text-2xl font-extrabold text-rose-400 mt-1">₹{{ formatNumber(kpis?.financials?.total_maintenance_cost) }}</p>
        </div>
        <div class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80">
          <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider">Auxiliary Expenses</p>
          <p class="text-2xl font-extrabold text-rose-400 mt-1">₹{{ formatNumber(kpis?.financials?.total_expenses) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default {
  name: 'Dashboard',
  setup() {
    const kpis = ref(null);
    const loading = ref(true);
    
    const filters = ref({
      type: '',
      region: ''
    });

    let financialChart = null;
    let statusChart = null;

    const formatNumber = (num) => {
      if (!num) return '0.00';
      return Number(num).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    const fetchDashboardData = async () => {
      loading.value = true;
      try {
        const response = await axios.get('/dashboard', { params: filters.value });
        kpis.value = response.data.data;
        await nextTick();
        renderCharts();
      } catch (err) {
        console.error('Error fetching dashboard KPIs:', err);
      } finally {
        loading.value = false;
      }
    };

    const renderCharts = () => {
      // Destroy old chart instances if they exist
      if (financialChart) financialChart.destroy();
      if (statusChart) statusChart.destroy();

      const finEl = document.getElementById('financialChart');
      if (finEl && kpis.value) {
        financialChart = new Chart(finEl, {
          type: 'bar',
          data: {
            labels: ['Revenue', 'Fuel', 'Maintenance', 'Expenses'],
            datasets: [{
              label: 'Operational Ledger (₹)',
              data: [
                kpis.value.financials.total_revenue,
                kpis.value.financials.total_fuel_cost,
                kpis.value.financials.total_maintenance_cost,
                kpis.value.financials.total_expenses
              ],
              backgroundColor: [
                'rgba(16, 185, 129, 0.25)', // Emerald
                'rgba(244, 63, 94, 0.25)', // Rose
                'rgba(249, 115, 22, 0.25)', // Orange
                'rgba(139, 92, 246, 0.25)' // Violet
              ],
              borderColor: [
                'rgb(16, 185, 129)',
                'rgb(244, 63, 94)',
                'rgb(249, 115, 22)',
                'rgb(139, 92, 246)'
              ],
              borderWidth: 1.5,
              borderRadius: 8
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            },
            scales: {
              y: {
                grid: { color: 'rgba(255, 255, 255, 0.05)' },
                ticks: { color: 'rgb(148, 163, 184)' }
              },
              x: {
                grid: { display: false },
                ticks: { color: 'rgb(148, 163, 184)' }
              }
            }
          }
        });
      }

      const statusEl = document.getElementById('statusChart');
      if (statusEl && kpis.value) {
        statusChart = new Chart(statusEl, {
          type: 'doughnut',
          data: {
            labels: ['Available', 'On Trip', 'In Shop', 'Retired'],
            datasets: [{
              data: [
                kpis.value.vehicles.available,
                kpis.value.vehicles.on_trip,
                kpis.value.vehicles.in_shop,
                kpis.value.vehicles.retired
              ],
              backgroundColor: [
                'rgb(16, 185, 129)',
                'rgb(99, 102, 241)',
                'rgb(249, 115, 22)',
                'rgb(244, 63, 94)'
              ],
              borderWidth: 0,
              cutout: '75%'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: { display: false }
            }
          }
        });
      }
    };

    onMounted(() => {
      fetchDashboardData();
    });

    return {
      kpis,
      loading,
      filters,
      fetchDashboardData,
      formatNumber
    };
  }
}
</script>
