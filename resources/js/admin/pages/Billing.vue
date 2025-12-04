<template>
  <div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Facturatie</h1>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
      <div class="py-4">
        <!-- Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900 dark:text-white">€{{ stats.monthlyRevenue }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Omzet Deze Maand</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.pendingInvoices }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Openstaande Facturen</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.dueInvoices }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Binnenkort Te Factureren</p>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="text-3xl font-bold text-gray-900 dark:text-white">€{{ stats.yearlyRevenue }}</div>
                </div>
              </div>
              <div class="mt-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Omzet Dit Jaar</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mb-6 flex gap-4">
          <button 
            @click="generateInvoices"
            :disabled="generating"
            class="bg-primary-500 hover:bg-primary-600 text-white px-6 py-2 rounded-md disabled:opacity-50"
          >
            {{ generating ? 'Bezig...' : 'Facturen Genereren' }}
          </button>
          <button class="border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-md">
            Export naar Moneybird
          </button>
        </div>

        <!-- Recent Invoices -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg border border-gray-200 dark:border-gray-700">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Recente Facturen</h3>
          </div>
          
          <div v-if="loading" class="p-8 text-center text-gray-600 dark:text-gray-300">
            Facturen laden...
          </div>

          <div v-else class="border-t border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Factuur #</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Klant</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Order</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Bedrag</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Datum</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Acties</th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="invoice in invoices" :key="invoice.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                    {{ invoice.invoice_number }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ invoice.customer_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ invoice.order_number }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    €{{ invoice.amount }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="getStatusClass(invoice.status)">
                      {{ invoice.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                    {{ formatDate(invoice.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="text-primary-500 hover:text-primary-600 mr-3">Download PDF</button>
                    <button class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">Bekijk</button>
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
  monthlyRevenue: string;
  yearlyRevenue: string;
  pendingInvoices: number;
  dueInvoices: number;
}

interface Invoice {
  id: number;
  invoice_number: string;
  customer_name: string;
  order_number: string;
  amount: string;
  status: string;
  created_at: string;
}

const stats = ref<Stats>({
  monthlyRevenue: '0.00',
  yearlyRevenue: '0.00',
  pendingInvoices: 0,
  dueInvoices: 0,
});

const invoices = ref<Invoice[]>([]);
const loading = ref(true);
const generating = ref(false);

const fetchBillingData = async () => {
  loading.value = true;
  try {
    const [statsResponse, invoicesResponse] = await Promise.all([
      api.get('/admin/billing/stats'),
      api.get('/admin/billing/invoices'),
    ]);
    
    stats.value = statsResponse.data;
    invoices.value = invoicesResponse.data.data;
  } catch (error) {
    console.error('Error fetching billing data:', error);
  } finally {
    loading.value = false;
  }
};

const generateInvoices = async () => {
  generating.value = true;
  try {
    await api.post('/admin/billing/generate-invoices');
    await fetchBillingData();
  } catch (error) {
    console.error('Error generating invoices:', error);
  } finally {
    generating.value = false;
  }
};

const getStatusClass = (status: string) => {
  const classes = {
    paid: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    pending: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    overdue: 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
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
  fetchBillingData();
});
</script>
