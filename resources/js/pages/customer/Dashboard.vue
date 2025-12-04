<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black">
    <!-- Header -->
    <div class="bg-black/30 border-b border-white/10 backdrop-blur-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-white">Klantportaal</h1>
          <div class="flex items-center space-x-4">
            <span class="text-gray-300">{{ customer?.first_name }} {{ customer?.last_name }}</span>
            <button
              @click="handleLogout"
              class="px-4 py-2 text-sm text-gray-300 hover:text-white border border-white/10 rounded-lg hover:bg-white/5 transition-colors"
            >
              Uitloggen
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <!-- Dashboard Content -->
      <div v-else>
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-400 text-sm mb-1">Actieve diensten</p>
                <p class="text-3xl font-bold text-white">{{ stats.active_orders || 0 }}</p>
              </div>
              <div class="w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-400 text-sm mb-1">Totaal uitgegeven</p>
                <p class="text-3xl font-bold text-white">€{{ stats.total_spent || '0.00' }}</p>
              </div>
              <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
          </div>

          <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-gray-400 text-sm mb-1">Account status</p>
                <p class="text-xl font-semibold" :class="getStatusColor(customer?.status)">
                  {{ getStatusText(customer?.status) }}
                </p>
              </div>
              <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-6">
          <h2 class="text-xl font-bold text-white mb-6">Recente bestellingen</h2>
          
          <div v-if="!recentOrders || recentOrders.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <p class="mt-4 text-gray-400">Je hebt nog geen bestellingen</p>
            <router-link
              to="/order"
              class="inline-block mt-4 px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
            >
              Bestelling plaatsen
            </router-link>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10">
              <thead>
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Bestelnummer</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Pakket</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Domeinen</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Totaal</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Datum</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-white/5">
                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-white/5 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-mono text-white">{{ order.order_number }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-300">{{ order.hosting_package?.name }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="text-sm text-gray-300">
                      <div v-for="domain in order.domains" :key="domain.id">
                        {{ domain.domain_name }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full" :class="getOrderStatusClass(order.status)">
                      {{ getOrderStatusText(order.status) }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm font-semibold text-white">€{{ order.total }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-400">{{ formatDate(order.created_at) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="recentOrders && recentOrders.length > 0" class="mt-6 text-center">
            <router-link
              to="/customer/orders"
              class="text-primary hover:text-primary/80 font-medium text-sm"
            >
              Alle bestellingen bekijken →
            </router-link>
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
const customer = ref<any>(null);
const stats = ref<any>({});
const recentOrders = ref<any[]>([]);

const loadDashboard = async () => {
  try {
    const token = localStorage.getItem('customer_token');
    if (!token) {
      router.push('/customer/login');
      return;
    }

    // Set auth header
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    const [meResponse, dashboardResponse] = await Promise.all([
      api.get('/customer/me'),
      api.get('/customer/dashboard'),
    ]);

    customer.value = meResponse.data.data;
    stats.value = dashboardResponse.data.data.stats;
    recentOrders.value = dashboardResponse.data.data.recent_orders;
  } catch (error: any) {
    if (error.response?.status === 401) {
      localStorage.removeItem('customer_token');
      localStorage.removeItem('customer');
      router.push('/customer/login');
    }
  } finally {
    loading.value = false;
  }
};

const handleLogout = async () => {
  try {
    await api.post('/customer/logout');
  } catch (error) {
    // Ignore errors
  } finally {
    localStorage.removeItem('customer_token');
    localStorage.removeItem('customer');
    delete api.defaults.headers.common['Authorization'];
    router.push('/customer/login');
  }
};

const getStatusColor = (status: string) => {
  const colors: Record<string, string> = {
    approved: 'text-green-400',
    pending: 'text-yellow-400',
    rejected: 'text-red-400',
  };
  return colors[status] || 'text-gray-400';
};

const getStatusText = (status: string) => {
  const texts: Record<string, string> = {
    approved: 'Goedgekeurd',
    pending: 'In afwachting',
    rejected: 'Afgewezen',
  };
  return texts[status] || status;
};

const getOrderStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-500/20 text-yellow-400',
    paid: 'bg-blue-500/20 text-blue-400',
    active: 'bg-green-500/20 text-green-400',
    cancelled: 'bg-red-500/20 text-red-400',
    failed: 'bg-red-500/20 text-red-400',
  };
  return classes[status] || 'bg-gray-500/20 text-gray-400';
};

const getOrderStatusText = (status: string) => {
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
    month: 'long',
    day: 'numeric',
  });
};

onMounted(() => {
  loadDashboard();
});
</script>
