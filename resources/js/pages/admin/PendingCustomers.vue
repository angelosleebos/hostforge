<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2">Klanten goedkeuren</h1>
        <p class="text-slate-300">Beoordeel en keur nieuwe klantregistraties goed</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
      </div>

      <!-- Empty State -->
      <div v-else-if="customers.length === 0" class="bg-white/10 backdrop-blur-lg rounded-2xl p-12 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h2 class="text-2xl font-bold text-white mb-2">Geen openstaande aanvragen</h2>
        <p class="text-slate-300">Alle klanten zijn al goedgekeurd of afgewezen</p>
      </div>

      <!-- Customer Cards -->
      <div v-else class="grid gap-6">
        <div
          v-for="customer in customers"
          :key="customer.id"
          class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 hover:border-purple-500/50 transition-all"
        >
          <div class="flex flex-col lg:flex-row gap-6">
            <!-- Customer Info -->
            <div class="flex-1">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h3 class="text-2xl font-bold text-white mb-1">
                    {{ customer.full_name }}
                  </h3>
                  <p v-if="customer.company" class="text-purple-300 font-medium">
                    {{ customer.company }}
                  </p>
                </div>
                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm font-medium">
                  Pending
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-slate-400 mb-1">Email</p>
                  <p class="text-white font-medium">{{ customer.email }}</p>
                </div>
                <div>
                  <p class="text-slate-400 mb-1">Telefoon</p>
                  <p class="text-white font-medium">{{ customer.phone || '-' }}</p>
                </div>
                <div>
                  <p class="text-slate-400 mb-1">Adres</p>
                  <p class="text-white font-medium">
                    {{ customer.address || '-' }}<br v-if="customer.address">
                    {{ customer.postal_code }} {{ customer.city }}
                  </p>
                </div>
                <div>
                  <p class="text-slate-400 mb-1">Land</p>
                  <p class="text-white font-medium">{{ customer.country || '-' }}</p>
                </div>
                <div>
                  <p class="text-slate-400 mb-1">BTW nummer</p>
                  <p class="text-white font-medium">{{ customer.vat_number || '-' }}</p>
                </div>
                <div>
                  <p class="text-slate-400 mb-1">Registratie datum</p>
                  <p class="text-white font-medium">
                    {{ new Date(customer.created_at).toLocaleDateString('nl-NL') }}
                  </p>
                </div>
              </div>

              <!-- Orders -->
              <div v-if="customer.orders && customer.orders.length > 0" class="mt-4">
                <p class="text-slate-400 text-sm mb-2">Bestellingen ({{ customer.orders.length }})</p>
                <div class="space-y-2">
                  <div
                    v-for="order in customer.orders"
                    :key="order.id"
                    class="bg-white/5 rounded-lg p-3 text-sm"
                  >
                    <div class="flex justify-between items-start">
                      <div>
                        <p class="text-white font-medium">{{ order.order_number }}</p>
                        <p class="text-slate-400 text-xs">{{ order.package_name }}</p>
                      </div>
                      <p class="text-purple-300 font-bold">€{{ order.total }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-3 lg:w-64">
              <!-- Approve -->
              <button
                @click="openApproveDialog(customer)"
                :disabled="processingCustomerId === customer.id"
                class="w-full px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg"
              >
                <span v-if="processingCustomerId === customer.id" class="flex items-center justify-center">
                  <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                  Verwerken...
                </span>
                <span v-else>✓ Goedkeuren</span>
              </button>

              <!-- Reject -->
              <button
                @click="openRejectDialog(customer)"
                :disabled="processingCustomerId === customer.id"
                class="w-full px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-rose-700 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg"
              >
                ✗ Afwijzen
              </button>

              <!-- View Details -->
              <button
                @click="viewCustomer(customer)"
                class="w-full px-6 py-3 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-all border border-white/20"
              >
                Details bekijken
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve Dialog -->
    <div
      v-if="showApproveDialog"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="closeDialogs"
    >
      <div class="bg-slate-800 rounded-2xl p-8 max-w-md w-full border border-white/20">
        <h3 class="text-2xl font-bold text-white mb-4">Klant goedkeuren</h3>
        <p class="text-slate-300 mb-6">
          Weet je zeker dat je <strong class="text-white">{{ selectedCustomer?.full_name }}</strong> wilt goedkeuren?
        </p>
        <p class="text-sm text-slate-400 mb-6">
          De klant ontvangt een welkomst email en kan direct inloggen.
        </p>
        <div class="flex gap-3">
          <button
            @click="approveCustomer"
            :disabled="processing"
            class="flex-1 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all disabled:opacity-50"
          >
            <span v-if="processing">Verwerken...</span>
            <span v-else>Goedkeuren</span>
          </button>
          <button
            @click="closeDialogs"
            :disabled="processing"
            class="flex-1 px-6 py-3 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-all"
          >
            Annuleren
          </button>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <div
      v-if="showRejectDialog"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
      @click.self="closeDialogs"
    >
      <div class="bg-slate-800 rounded-2xl p-8 max-w-md w-full border border-white/20">
        <h3 class="text-2xl font-bold text-white mb-4">Klant afwijzen</h3>
        <p class="text-slate-300 mb-4">
          Waarom wijs je <strong class="text-white">{{ selectedCustomer?.full_name }}</strong> af?
        </p>
        <textarea
          v-model="rejectionReason"
          placeholder="Reden voor afwijzing (optioneel)"
          class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:border-purple-500 mb-6"
          rows="4"
        ></textarea>
        <div class="flex gap-3">
          <button
            @click="rejectCustomer"
            :disabled="processing"
            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white font-semibold rounded-xl hover:from-red-600 hover:to-rose-700 transition-all disabled:opacity-50"
          >
            <span v-if="processing">Verwerken...</span>
            <span v-else>Afwijzen</span>
          </button>
          <button
            @click="closeDialogs"
            :disabled="processing"
            class="flex-1 px-6 py-3 bg-white/10 text-white font-semibold rounded-xl hover:bg-white/20 transition-all"
          >
            Annuleren
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notification -->
    <div
      v-if="notification.show"
      class="fixed bottom-8 right-8 bg-slate-800 border border-white/20 rounded-xl p-4 shadow-xl z-50 max-w-md"
      :class="notification.type === 'success' ? 'border-green-500/50' : 'border-red-500/50'"
    >
      <div class="flex items-center gap-3">
        <div
          class="w-10 h-10 rounded-full flex items-center justify-center"
          :class="notification.type === 'success' ? 'bg-green-500/20' : 'bg-red-500/20'"
        >
          <span v-if="notification.type === 'success'" class="text-green-400 text-xl">✓</span>
          <span v-else class="text-red-400 text-xl">✗</span>
        </div>
        <div class="flex-1">
          <p class="text-white font-medium">{{ notification.message }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'

interface Customer {
  id: number
  email: string
  first_name: string
  last_name: string
  full_name: string
  company: string | null
  phone: string | null
  address: string | null
  postal_code: string | null
  city: string | null
  country: string | null
  vat_number: string | null
  status: string
  created_at: string
  orders?: any[]
}

const customers = ref<Customer[]>([])
const loading = ref(true)
const processing = ref(false)
const processingCustomerId = ref<number | null>(null)
const showApproveDialog = ref(false)
const showRejectDialog = ref(false)
const selectedCustomer = ref<Customer | null>(null)
const rejectionReason = ref('')
const notification = ref({
  show: false,
  type: 'success' as 'success' | 'error',
  message: ''
})

async function fetchPendingCustomers() {
  try {
    loading.value = true
    const response = await axios.get('/api/admin/customers/pending')
    customers.value = response.data.data
  } catch (error) {
    showNotification('error', 'Fout bij ophalen van klanten')
    console.error(error)
  } finally {
    loading.value = false
  }
}

function openApproveDialog(customer: Customer) {
  selectedCustomer.value = customer
  showApproveDialog.value = true
}

function openRejectDialog(customer: Customer) {
  selectedCustomer.value = customer
  rejectionReason.value = ''
  showRejectDialog.value = true
}

function closeDialogs() {
  if (!processing.value) {
    showApproveDialog.value = false
    showRejectDialog.value = false
    selectedCustomer.value = null
    rejectionReason.value = ''
  }
}

async function approveCustomer() {
  if (!selectedCustomer.value) return

  try {
    processing.value = true
    processingCustomerId.value = selectedCustomer.value.id

    await axios.patch(`/api/admin/customers/${selectedCustomer.value.id}/status`, {
      status: 'approved'
    })

    showNotification('success', `${selectedCustomer.value.full_name} is goedgekeurd`)
    
    // Remove from list
    customers.value = customers.value.filter(c => c.id !== selectedCustomer.value?.id)
    
    closeDialogs()
  } catch (error) {
    showNotification('error', 'Fout bij goedkeuren van klant')
    console.error(error)
  } finally {
    processing.value = false
    processingCustomerId.value = null
  }
}

async function rejectCustomer() {
  if (!selectedCustomer.value) return

  try {
    processing.value = true
    processingCustomerId.value = selectedCustomer.value.id

    await axios.patch(`/api/admin/customers/${selectedCustomer.value.id}/status`, {
      status: 'rejected',
      reason: rejectionReason.value
    })

    showNotification('success', `${selectedCustomer.value.full_name} is afgewezen`)
    
    // Remove from list
    customers.value = customers.value.filter(c => c.id !== selectedCustomer.value?.id)
    
    closeDialogs()
  } catch (error) {
    showNotification('error', 'Fout bij afwijzen van klant')
    console.error(error)
  } finally {
    processing.value = false
    processingCustomerId.value = null
  }
}

function viewCustomer(customer: Customer) {
  // TODO: Navigate to customer detail page
  console.log('View customer:', customer)
}

function showNotification(type: 'success' | 'error', message: string) {
  notification.value = { show: true, type, message }
  setTimeout(() => {
    notification.value.show = false
  }, 5000)
}

onMounted(() => {
  fetchPendingCustomers()
})
</script>
