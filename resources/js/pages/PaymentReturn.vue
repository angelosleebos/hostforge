<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">
      <!-- Loading State -->
      <div v-if="loading" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary mx-auto mb-4"></div>
        <h2 class="text-2xl font-bold text-white mb-2">Betaling verwerken...</h2>
        <p class="text-gray-400">Even geduld terwijl we je betaling verwerken</p>
      </div>

      <!-- Success State -->
      <div v-else-if="status === 'success'" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-500/20 mb-4">
            <svg class="h-10 w-10 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-2">Betaling geslaagd!</h2>
          <p class="text-gray-300 mb-6">
            Bedankt voor je bestelling. Je ontvangt binnenkort een bevestigingsmail.
          </p>
          
          <div v-if="order" class="bg-black/30 rounded-lg p-6 mb-6 text-left">
            <h3 class="text-lg font-semibold text-white mb-4">Bestelgegevens</h3>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-400">Bestelnummer:</span>
                <span class="text-white font-mono">{{ order.order_number }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Status:</span>
                <span class="text-green-400">{{ order.status }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-400">Totaal:</span>
                <span class="text-white font-semibold">€{{ order.total }}</span>
              </div>
            </div>
          </div>

          <div class="space-x-4">
            <router-link
              to="/"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary/90 transition-colors"
            >
              Naar Homepage
            </router-link>
            <router-link
              to="/customer/dashboard"
              class="inline-flex items-center px-6 py-3 border border-white/20 text-base font-medium rounded-md text-white hover:bg-white/5 transition-colors"
            >
              Mijn Dashboard
            </router-link>
          </div>
        </div>
      </div>

      <!-- Failed State -->
      <div v-else-if="status === 'failed'" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-500/20 mb-4">
            <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-2">Betaling mislukt</h2>
          <p class="text-gray-300 mb-6">
            Er is iets misgegaan met je betaling. Probeer het opnieuw of neem contact met ons op.
          </p>
          
          <div class="space-x-4">
            <router-link
              :to="`/order?package=${route.query.package || ''}`"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary/90 transition-colors"
            >
              Opnieuw proberen
            </router-link>
            <router-link
              to="/"
              class="inline-flex items-center px-6 py-3 border border-white/20 text-base font-medium rounded-md text-white hover:bg-white/5 transition-colors"
            >
              Naar Homepage
            </router-link>
          </div>
        </div>
      </div>

      <!-- Cancelled State -->
      <div v-else-if="status === 'cancelled'" class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
        <div class="text-center">
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-500/20 mb-4">
            <svg class="h-10 w-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-2">Betaling geannuleerd</h2>
          <p class="text-gray-300 mb-6">
            Je hebt de betaling geannuleerd. Je kunt altijd opnieuw proberen wanneer je wilt.
          </p>
          
          <div class="space-x-4">
            <router-link
              :to="`/order?package=${route.query.package || ''}`"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary/90 transition-colors"
            >
              Terug naar bestellen
            </router-link>
            <router-link
              to="/"
              class="inline-flex items-center px-6 py-3 border border-white/20 text-base font-medium rounded-md text-white hover:bg-white/5 transition-colors"
            >
              Naar Homepage
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api';

const route = useRoute();
const loading = ref(true);
const status = ref<'success' | 'failed' | 'cancelled' | null>(null);
const order = ref<any>(null);

const checkPaymentStatus = async () => {
  try {
    const orderNumber = route.query.order as string;
    
    if (!orderNumber) {
      status.value = 'failed';
      loading.value = false;
      return;
    }

    // Fetch order status
    const response = await api.get(`/orders/${orderNumber}`);
    order.value = response.data.data;

    // Determine status based on order status
    if (order.value.status === 'paid' || order.value.status === 'active') {
      status.value = 'success';
    } else if (order.value.status === 'cancelled') {
      status.value = 'cancelled';
    } else {
      // Still pending, check if payment was cancelled
      const wasCancelled = route.query.cancelled === 'true';
      status.value = wasCancelled ? 'cancelled' : 'failed';
    }
  } catch (error) {
    console.error('Error checking payment status:', error);
    status.value = 'failed';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  // Add a small delay to show the loading state
  setTimeout(() => {
    checkPaymentStatus();
  }, 1000);
});
</script>
