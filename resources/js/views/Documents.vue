<template>
  <div class="space-y-6 text-left">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold text-white tracking-tight">Document Vault</h1>
        <p class="text-slate-400 text-sm">Secure storage for vehicle registrations, insurance contracts, and fitness certs.</p>
      </div>

      <button 
        v-if="hasPermission('documents.create')"
        @click="openModal"
        class="py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-xl transition-all shadow-md shadow-indigo-600/10 flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Upload Document
      </button>
    </div>

    <!-- Data Table -->
    <div class="bg-[#0d1527] border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-900/30 text-slate-400 text-xs font-bold uppercase tracking-wider">
              <th class="py-4 px-6">Vehicle</th>
              <th class="py-4 px-6">Document Type</th>
              <th class="py-4 px-6">Issue Date</th>
              <th class="py-4 px-6">Expiry Date</th>
              <th class="py-4 px-6 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800 text-sm text-slate-300">
            <tr v-if="documents.length === 0" class="h-32 text-center">
              <td colspan="5" class="text-slate-500">No vehicle documents uploaded yet.</td>
            </tr>
            <tr v-for="doc in documents" :key="doc.id" class="hover:bg-slate-900/40 transition-colors">
              <td class="py-4 px-6 font-mono text-xs">
                <div class="font-bold text-white">{{ doc.vehicle?.name }}</div>
                <div class="text-slate-500 text-[10px] mt-0.5 font-sans">{{ doc.vehicle?.registration_number }}</div>
              </td>
              <td class="py-4 px-6">
                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-200 text-xs font-semibold uppercase">
                  {{ doc.document_type }}
                </span>
              </td>
              <td class="py-4 px-6 text-slate-400">{{ doc.issue_date || '-' }}</td>
              <td class="py-4 px-6">
                <div class="flex items-center gap-2">
                  <span class="text-slate-200">{{ doc.expiry_date || '-' }}</span>
                  <span 
                    v-if="doc.is_expired" 
                    class="px-2 py-0.5 rounded bg-rose-500/15 text-rose-400 border border-rose-500/20 text-[9px] font-bold"
                  >
                    EXPIRED
                  </span>
                  <span 
                    v-else-if="doc.days_until_expiry <= 30 && doc.days_until_expiry >= 0" 
                    class="px-2 py-0.5 rounded bg-amber-500/15 text-amber-400 border border-amber-500/20 text-[9px] font-bold animate-pulse"
                  >
                    Expiring in {{ doc.days_until_expiry }} days
                  </span>
                </div>
              </td>
              <td class="py-4 px-6 text-right">
                <div class="inline-flex items-center gap-2">
                  <!-- Download attachment -->
                  <a 
                    :href="doc.download_url" 
                    target="_blank"
                    class="p-1.5 rounded-lg bg-indigo-600/10 hover:bg-indigo-600 text-indigo-400 hover:text-white border border-indigo-500/20 transition-colors flex items-center justify-center"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                  </a>
                  <!-- Delete Document -->
                  <button 
                    v-if="hasPermission('documents.delete')"
                    @click="deleteDocument(doc.id)" 
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

    <!-- Upload Document Modal -->
    <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-[#0d1527] border border-slate-800 rounded-3xl w-full max-w-md shadow-2xl p-8 relative">
        <h2 class="text-xl font-bold text-white mb-6">Upload Document</h2>

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

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Document Type*</label>
            <input 
              v-model="form.document_type" 
              type="text" 
              required
              placeholder="e.g. Insurance, Registration, PUC..."
              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-600 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Issue Date</label>
              <input 
                v-model="form.issue_date" 
                type="date" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>

            <div class="space-y-1.5 text-left">
              <label class="text-xs font-semibold text-slate-400">Expiry Date</label>
              <input 
                v-model="form.expiry_date" 
                type="date" 
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-300 text-sm focus:outline-none focus:border-indigo-500 transition-colors"
              />
            </div>
          </div>

          <div class="space-y-1.5 text-left">
            <label class="text-xs font-semibold text-slate-400">Select File* (PDF, JPG, PNG, max 5MB)</label>
            <input 
              type="file" 
              required
              @change="handleFileUpload"
              class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-600/10 file:text-indigo-400 file:hover:bg-indigo-600/20 file:cursor-pointer cursor-pointer border border-slate-800/80 rounded-xl p-2.5 bg-slate-950/60"
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
              Upload
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
  name: 'Documents',
  setup() {
    const authStore = useAuthStore();
    const hasPermission = (permission) => authStore.hasPermission(permission);

    const documents = ref([]);
    const vehicles = ref([]);
    const pagination = ref({ current_page: 1, last_page: 1 });
    const modalOpen = ref(false);

    const form = ref({
      vehicle_id: '',
      document_type: '',
      issue_date: '',
      expiry_date: '',
      file: null
    });

    const fetchDocuments = async () => {
      try {
        const response = await axios.get('/vehicle-documents', {
          params: { page: pagination.value.current_page }
        });
        documents.value = response.data.data;
        pagination.value.current_page = response.data.meta.current_page;
        pagination.value.last_page = response.data.meta.last_page;
      } catch (err) {
        console.error('Error fetching documents:', err);
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
      fetchDocuments();
    };

    const openModal = () => {
      fetchVehicles();
      form.value = {
        vehicle_id: '',
        document_type: '',
        issue_date: '',
        expiry_date: '',
        file: null
      };
      modalOpen.value = true;
    };

    const handleFileUpload = (e) => {
      form.value.file = e.target.files[0];
    };

    const submitForm = async () => {
      try {
        const formData = new FormData();
        formData.append('vehicle_id', form.value.vehicle_id);
        formData.append('document_type', form.value.document_type);
        if (form.value.issue_date) formData.append('issue_date', form.value.issue_date);
        if (form.value.expiry_date) formData.append('expiry_date', form.value.expiry_date);
        formData.append('file', form.value.file);

        await axios.post('/vehicle-documents', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });

        modalOpen.value = false;
        authStore.showToast('Document uploaded successfully.', 'success');
        fetchDocuments();
      } catch (err) {
        authStore.showToast('Failed to upload document.', 'error');
      }
    };

    const deleteDocument = (id) => {
      authStore.showConfirm(
        'Delete Document',
        'Are you sure you want to delete this document permanently?',
        async () => {
          try {
            await axios.delete(`/vehicle-documents/${id}`);
            authStore.showToast('Document deleted successfully.', 'success');
            fetchDocuments();
          } catch (err) {
            authStore.showToast('Failed to delete document.', 'error');
          }
        }
      );
    };

    onMounted(() => {
      fetchDocuments();
    });

    return {
      documents,
      vehicles,
      pagination,
      modalOpen,
      form,
      hasPermission,
      setPage,
      openModal,
      handleFileUpload,
      submitForm,
      deleteDocument
    };
  }
}
</script>
