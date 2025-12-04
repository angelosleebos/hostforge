<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black">
    <!-- Header -->
    <div class="bg-black/30 border-b border-white/10 backdrop-blur-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-white">Klanten Overzicht</h1>
          <router-link
            to="/admin"
            class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-white/10 rounded-lg hover:bg-white/5 transition-colors"
          >
            ← Terug naar dashboard
          </router-link>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Filters -->
      <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
            <select
              v-model="filters.status"
              @change="loadCustomers"
              class="w-full px-4 py-2 bg-black/30 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="">Alle statussen</option>
              <option value="pending">In behandeling</option>
              <option value="approved">Goedgekeurd</option>
              <option value="rejected">Afgewezen</option>
              <option value="suspended">Geschorst</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Zoeken</label>
            <input
              v-model="filters.search"
              @input="loadCustomers"
              type="text"
              placeholder="Naam, email, bedrijf..."
              class="w-full px-4 py-2 bg-black/30 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div class="flex items-end">
            <button
              @click="resetFilters"
              class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-white/10 rounded-lg hover:bg-white/5 transition-colors"
            >
              Reset filters
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <!-- Customers Table -->
      <div v-else class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl overflow-hidden">
        <div v-if="!customers || customers.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="mt-4 text-gray-400">Geen klanten gevonden</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10">
            <thead class="bg-black/20">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Klant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Bedrijf</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Telefoon</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Orders</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Geregistreerd</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Acties</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="customer in customers" :key="customer.id" class="hover:bg-white/5 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-white">{{ customer.full_name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-300">{{ customer.company || '-' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-300">{{ customer.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-300">{{ customer.phone || '-' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(customer.status)">
                    {{ getStatusText(customer.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-300">{{ customer.orders?.length || 0 }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-400">{{ formatDate(customer.created_at) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    @click="viewCustomer(customer)"
                    class="text-primary hover:text-primary/80"
                  >
                    Details
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Customer Detail Modal -->
    <div
      v-if="selectedCustomer"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
      @click.self="selectedCustomer = null"
    >
      <div class="bg-gradient-to-b from-gray-900 to-gray-800 border border-white/10 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gray-900/95 backdrop-blur-sm border-b border-white/10 px-6 py-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white">{{ selectedCustomer.full_name }}</h3>
          <button
            @click="selectedCustomer = null"
            class="text-gray-400 hover:text-white"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-6">
          <!-- Customer Info -->
          <div>
            <h4 class="text-sm font-medium text-gray-400 mb-3">Klantgegevens</h4>
            <div class="bg-black/30 rounded-lg p-4 space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-400">Email:</span>
                <span class="text-white">{{ selectedCustomer.email }}</span>
              </div>
              <div v-if="selectedCustomer.company" class="flex justify-between">
                <span class="text-gray-400">Bedrijf:</span>
                <span class="text-white">{{ selectedCustomer.company }}</span>
              </div>
              <div v-if="selectedCustomer.phone" class="flex justify-between">
                <span class="text-gray-400">Telefoon:</span>
                <span class="text-white">{{ selectedCustomer.phone }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Status:</span>
                <span :class="getStatusColor(selectedCustomer.status)">{{ getStatusText(selectedCustomer.status) }}</span>
              </div>
              <div v-if="selectedCustomer.vat_number" class="flex justify-between">
                <span class="text-gray-400">BTW nummer:</span>
                <span class="text-white">{{ selectedCustomer.vat_number }}</span>
              </div>
            </div>
          </div>

          <!-- Orders -->
          <div v-if="selectedCustomer.orders && selectedCustomer.orders.length > 0">
            <h4 class="text-sm font-medium text-gray-400 mb-3">Bestellingen ({{ selectedCustomer.orders.length }})</h4>
            <div class="bg-black/30 rounded-lg p-4 space-y-2">
              <div v-for="order in selectedCustomer.orders" :key="order.id" class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
                <div>
                  <span class="text-white font-mono text-sm">{{ order.order_number }}</span>
                  <span class="text-gray-500 text-xs ml-2">€{{ order.total }}</span>
                </div>
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(order.status)">
                  {{ getStatusText(order.status) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else>
            <p class="text-gray-400 text-sm">Geen bestellingen</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api';

const router = useRouter();
const loading = ref(true);
const customers = ref<any[]>([]);
const selectedCustomer = ref<any>(null);
const filters = ref({
  status: '',
  search: '',
});

const loadCustomers = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.search) params.append('search', filters.value.search);

    const response = await api.get(`/admin/customers?${params.toString()}`);
    customers.value = response.data.data;
  } catch (error: any) {
    console.error('Failed to load customers:', error);
    if (error.response?.status === 401) {
      router.push('/admin/login');
    }
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    status: '',
    search: '',
  };
  loadCustomers();
};

const viewCustomer = (customer: any) => {
  selectedCustomer.value = customer;
};

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-500/20 text-yellow-400',
    approved: 'bg-green-500/20 text-green-400',
    rejected: 'bg-red-500/20 text-red-400',
    suspended: 'bg-orange-500/20 text-orange-400',
    active: 'bg-green-500/20 text-green-400',
    paid: 'bg-blue-500/20 text-blue-400',
    cancelled: 'bg-red-500/20 text-red-400',
  };
  return classes[status] || 'bg-gray-500/20 text-gray-400';
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'text-yellow-400',
    approved: 'text-green-400',
    rejected: 'text-red-400',
    suspended: 'text-orange-400',
    active: 'text-green-400',
    paid: 'text-blue-400',
    cancelled: 'text-red-400',
  };
  return colors[status] || 'text-gray-400';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: 'In behandeling',
    approved: 'Goedgekeurd',
    rejected: 'Afgewezen',
    suspended: 'Geschorst',
    active: 'Actief',
    paid: 'Betaald',
    cancelled: 'Geannuleerd',
  };
  return texts[status] || status;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('nl-NL', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

onMounted(() => {
  const adminToken = localStorage.getItem('admin_token');
  if (adminToken) {
    loadCustomers();
  }
});
</script>
