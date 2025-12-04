<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black">
    <!-- Header -->
    <div class="bg-black/30 border-b border-white/10 backdrop-blur-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-white">Order Beheer</h1>
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
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Status</label>
            <select
              v-model="filters.status"
              @change="loadOrders"
              class="w-full px-4 py-2 bg-black/30 border border-white/10 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <option value="">Alle statussen</option>
              <option value="pending">In behandeling</option>
              <option value="paid">Betaald</option>
              <option value="active">Actief</option>
              <option value="cancelled">Geannuleerd</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Zoeken</label>
            <input
              v-model="filters.search"
              @input="loadOrders"
              type="text"
              placeholder="Bestelnummer, email..."
              class="w-full px-4 py-2 bg-black/30 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary"
            />
          </div>

          <div class="md:col-span-2 flex items-end justify-end">
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

      <!-- Orders Table -->
      <div v-else class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl overflow-hidden">
        <div v-if="!orders || orders.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <p class="mt-4 text-gray-400">Geen bestellingen gevonden</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-white/10">
            <thead class="bg-black/20">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Bestelling</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Klant</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Pakket</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Domeinen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Totaal</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Datum</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Acties</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <tr v-for="order in orders" :key="order.id" class="hover:bg-white/5 transition-colors">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-mono text-white">{{ order.order_number }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-300">
                    <div class="font-medium">{{ order.customer?.first_name }} {{ order.customer?.last_name }}</div>
                    <div class="text-gray-500 text-xs">{{ order.customer?.email }}</div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-300">{{ order.hosting_package?.name }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-300">
                    <div v-for="domain in order.domains" :key="domain.id" class="text-xs">
                      {{ domain.domain_name }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(order.status)">
                    {{ getStatusText(order.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-semibold text-white">€{{ order.total }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-400">{{ formatDate(order.created_at) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button
                    v-if="order.status === 'paid'"
                    @click="approveOrder(order)"
                    class="text-green-400 hover:text-green-300 mr-3"
                  >
                    Goedkeuren
                  </button>
                  <button
                    @click="viewOrder(order)"
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

    <!-- Order Detail Modal -->
    <div
      v-if="selectedOrder"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50"
      @click.self="selectedOrder = null"
    >
      <div class="bg-gradient-to-b from-gray-900 to-gray-800 border border-white/10 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-gray-900/95 backdrop-blur-sm border-b border-white/10 px-6 py-4 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white">Bestelling {{ selectedOrder.order_number }}</h3>
          <button
            @click="selectedOrder = null"
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
                <span class="text-gray-400">Naam:</span>
                <span class="text-white">{{ selectedOrder.customer?.first_name }} {{ selectedOrder.customer?.last_name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Email:</span>
                <span class="text-white">{{ selectedOrder.customer?.email }}</span>
              </div>
              <div v-if="selectedOrder.customer?.company" class="flex justify-between">
                <span class="text-gray-400">Bedrijf:</span>
                <span class="text-white">{{ selectedOrder.customer?.company }}</span>
              </div>
              <div v-if="selectedOrder.customer?.phone" class="flex justify-between">
                <span class="text-gray-400">Telefoon:</span>
                <span class="text-white">{{ selectedOrder.customer?.phone }}</span>
              </div>
            </div>
          </div>

          <!-- Order Info -->
          <div>
            <h4 class="text-sm font-medium text-gray-400 mb-3">Bestelling</h4>
            <div class="bg-black/30 rounded-lg p-4 space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-400">Pakket:</span>
                <span class="text-white">{{ selectedOrder.hosting_package?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Status:</span>
                <span :class="getStatusColor(selectedOrder.status)">{{ getStatusText(selectedOrder.status) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Subtotaal:</span>
                <span class="text-white">€{{ selectedOrder.subtotal }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">BTW:</span>
                <span class="text-white">€{{ selectedOrder.tax }}</span>
              </div>
              <div class="flex justify-between font-semibold pt-2 border-t border-white/10">
                <span class="text-gray-300">Totaal:</span>
                <span class="text-white">€{{ selectedOrder.total }}</span>
              </div>
            </div>
          </div>

          <!-- Domains -->
          <div v-if="selectedOrder.domains && selectedOrder.domains.length > 0">
            <h4 class="text-sm font-medium text-gray-400 mb-3">Domeinen</h4>
            <div class="bg-black/30 rounded-lg p-4">
              <div v-for="domain in selectedOrder.domains" :key="domain.id" class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
                <span class="text-white">{{ domain.domain_name }}</span>
                <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(domain.status)">
                  {{ getStatusText(domain.status) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="selectedOrder.status === 'paid'" class="flex justify-end space-x-3 pt-4 border-t border-white/10">
            <button
              @click="approveOrder(selectedOrder)"
              class="px-6 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all"
            >
              Bestelling goedkeuren
            </button>
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
const orders = ref<any[]>([]);
const selectedOrder = ref<any>(null);
const filters = ref({
  status: '',
  search: '',
});

const loadOrders = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.search) params.append('search', filters.value.search);

    const response = await api.get(`/admin/orders?${params.toString()}`);
    orders.value = response.data.data;
  } catch (error) {
    console.error('Failed to load orders:', error);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    status: '',
    search: '',
  };
  loadOrders();
};

const viewOrder = (order: any) => {
  selectedOrder.value = order;
};

const approveOrder = async (order: any) => {
  if (!confirm(`Weet je zeker dat je bestelling ${order.order_number} wilt goedkeuren?`)) {
    return;
  }

  try {
    await api.post(`/admin/orders/${order.id}/approve`);
    alert('Bestelling goedgekeurd! Provisioning is gestart.');
    selectedOrder.value = null;
    loadOrders();
  } catch (error: any) {
    alert(error.response?.data?.message || 'Fout bij goedkeuren van bestelling');
  }
};

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-500/20 text-yellow-400',
    paid: 'bg-blue-500/20 text-blue-400',
    active: 'bg-green-500/20 text-green-400',
    cancelled: 'bg-red-500/20 text-red-400',
    failed: 'bg-red-500/20 text-red-400',
  };
  return classes[status] || 'bg-gray-500/20 text-gray-400';
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'text-yellow-400',
    paid: 'text-blue-400',
    active: 'text-green-400',
    cancelled: 'text-red-400',
    failed: 'text-red-400',
  };
  return colors[status] || 'text-gray-400';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    pending: 'In behandeling',
    paid: 'Betaald',
    active: 'Actief',
    cancelled: 'Geannuleerd',
    failed: 'Mislukt',
  };
  return texts[status] || status;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('nl-NL', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onMounted(() => {
  loadOrders();
});
</script>
