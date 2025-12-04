<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Admin Dashboard</h1>
        <p class="text-slate-300">Welkom terug, beheerder</p>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 text-sm font-medium">Openstaande Aanvragen</h3>
            <div class="w-10 h-10 bg-yellow-500/20 rounded-xl flex items-center justify-center">
              <span class="text-2xl">⏳</span>
            </div>
          </div>
          <p class="text-4xl font-bold text-white mb-1">{{ stats.pendingCustomers }}</p>
          <p class="text-xs text-slate-400">Wachten op goedkeuring</p>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 text-sm font-medium">Actieve Klanten</h3>
            <div class="w-10 h-10 bg-green-500/20 rounded-xl flex items-center justify-center">
              <span class="text-2xl">👥</span>
            </div>
          </div>
          <p class="text-4xl font-bold text-white mb-1">{{ stats.activeCustomers }}</p>
          <p class="text-xs text-slate-400">Goedgekeurde accounts</p>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 text-sm font-medium">Openstaande Orders</h3>
            <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center">
              <span class="text-2xl">📦</span>
            </div>
          </div>
          <p class="text-4xl font-bold text-white mb-1">{{ stats.pendingOrders }}</p>
          <p class="text-xs text-slate-400">Te verwerken</p>
        </div>

        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-slate-300 text-sm font-medium">Omzet deze maand</h3>
            <div class="w-10 h-10 bg-purple-500/20 rounded-xl flex items-center justify-center">
              <span class="text-2xl">💰</span>
            </div>
          </div>
          <p class="text-4xl font-bold text-white mb-1">€{{ stats.monthlyRevenue }}</p>
          <p class="text-xs text-slate-400">Betaalde bestellingen</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <router-link
          to="/admin/pending-customers"
          class="bg-gradient-to-br from-yellow-500/20 to-orange-500/20 backdrop-blur-lg rounded-2xl p-6 border border-yellow-500/30 hover:border-yellow-500/50 transition-all group cursor-pointer"
        >
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-yellow-500/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <span class="text-3xl">⏳</span>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Klanten Goedkeuren</h3>
              <p class="text-yellow-200 text-sm">{{ stats.pendingCustomers }} wachten</p>
            </div>
          </div>
          <p class="text-slate-300 text-sm">Beoordeel en keur nieuwe klantregistraties goed</p>
        </router-link>

        <router-link
          to="/admin/orders"
          class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 backdrop-blur-lg rounded-2xl p-6 border border-blue-500/30 hover:border-blue-500/50 transition-all group cursor-pointer"
        >
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-blue-500/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <span class="text-3xl">📦</span>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Orders Beheren</h3>
              <p class="text-blue-200 text-sm">{{ stats.pendingOrders }} openstaand</p>
            </div>
          </div>
          <p class="text-slate-300 text-sm">Bekijk en beheer alle klantbestellingen</p>
        </router-link>

        <router-link
          to="/admin/customers"
          class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 backdrop-blur-lg rounded-2xl p-6 border border-green-500/30 hover:border-green-500/50 transition-all group cursor-pointer"
        >
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-green-500/30 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
              <span class="text-3xl">👥</span>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">Klanten Overzicht</h3>
              <p class="text-green-200 text-sm">{{ stats.activeCustomers }} actief</p>
            </div>
          </div>
          <p class="text-slate-300 text-sm">Bekijk alle klanten en hun details</p>
        </router-link>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
        <h2 class="text-2xl font-bold text-white mb-6">Recente Activiteit</h2>
        
        <div v-if="loading" class="flex justify-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div>
        </div>

        <div v-else-if="recentOrders.length === 0" class="text-center py-8 text-slate-400">
          Geen recente activiteit
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="order in recentOrders"
            :key="order.id"
            class="flex items-center justify-between p-4 bg-white/5 rounded-xl hover:bg-white/10 transition-all"
          >
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-purple-500/20 rounded-full flex items-center justify-center">
                <span class="text-lg">📝</span>
              </div>
              <div>
                <p class="text-white font-medium">{{ order.customer_name }}</p>
                <p class="text-sm text-slate-400">{{ order.package_name }} - {{ order.order_number }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="text-white font-bold">€{{ order.total }}</p>
              <span
                class="text-xs px-2 py-1 rounded-full"
                :class="{
                  'bg-yellow-500/20 text-yellow-300': order.status === 'pending',
                  'bg-green-500/20 text-green-300': order.status === 'paid',
                  'bg-blue-500/20 text-blue-300': order.status === 'active'
                }"
              >
                {{ getStatusLabel(order.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Stats {
  pendingCustomers: number
  activeCustomers: number
  pendingOrders: number
  monthlyRevenue: string
}

interface Order {
  id: number
  order_number: string
  customer_name: string
  package_name: string
  total: string
  status: string
}

const stats = ref<Stats>({
  pendingCustomers: 0,
  activeCustomers: 0,
  pendingOrders: 0,
  monthlyRevenue: '0.00'
})

const recentOrders = ref<Order[]>([])
const loading = ref(true)

async function fetchDashboardData() {
  try {
    loading.value = true
    const [statsResponse, ordersResponse] = await Promise.all([
      axios.get('/api/admin/dashboard/stats'),
      axios.get('/api/admin/orders?per_page=5&sort=-created_at')
    ])

    stats.value = statsResponse.data.data
    recentOrders.value = ordersResponse.data.data
  } catch (error) {
    console.error('Error fetching dashboard data:', error)
  } finally {
    loading.value = false
  }
}

function getStatusLabel(status: string): string {
  const labels: Record<string, string> = {
    pending: 'In afwachting',
    paid: 'Betaald',
    processing: 'In behandeling',
    active: 'Actief',
    cancelled: 'Geannuleerd'
  }
  return labels[status] || status
}

onMounted(() => {
  fetchDashboardData()
})
</script>
