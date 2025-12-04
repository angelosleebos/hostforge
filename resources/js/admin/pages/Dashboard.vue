<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
      <div class="py-4">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
          <!-- Stats Cards -->
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900">{{ stats.totalCustomers }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-500">Totaal Klanten</p>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900">{{ stats.pendingOrders }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-500">Orders in Behandeling</p>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900">{{ stats.activeOrders }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-500">Actieve Orders</p>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900">€{{ stats.monthlyRevenue }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-500">Omzet Deze Maand</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="mt-8 bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Recente Orders</h3>
          </div>
          <div class="border-t border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Totaal</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="order in recentOrders" :key="order.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ order.order_number }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ order.customer.first_name }} {{ order.customer.last_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(order.status)">
                      {{ order.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    €{{ order.total }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(order.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../../api';

interface Stats {
  totalCustomers: number;
  pendingOrders: number;
  activeOrders: number;
  monthlyRevenue: string;
}

interface Order {
  id: number;
  order_number: string;
  status: string;
  total: string;
  created_at: string;
  customer: {
    first_name: string;
    last_name: string;
  };
}

const stats = ref<Stats>({
  totalCustomers: 0,
  pendingOrders: 0,
  activeOrders: 0,
  monthlyRevenue: '0.00',
});

const recentOrders = ref<Order[]>([]);

const fetchDashboardData = async () => {
  try {
    const [statsResponse, ordersResponse] = await Promise.all([
      api.get('/admin/dashboard/stats'),
      api.get('/admin/orders?per_page=10'),
    ]);
    
    stats.value = statsResponse.data;
    recentOrders.value = ordersResponse.data.data;
  } catch (error) {
    console.error('Error fetching dashboard data:', error);
  }
};

const getStatusClass = (status: string) => {
  const classes = {
    pending: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800',
    processing: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800',
    active: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800',
    cancelled: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800',
  };
  return classes[status as keyof typeof classes] || classes.pending;
};

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('nl-NL', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

onMounted(() => {
  fetchDashboardData();
});
</script>
