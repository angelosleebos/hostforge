<template>
  <div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-12 text-center">
          <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>

          <h1 class="text-3xl font-bold text-gray-900">Bestelling Ontvangen!</h1>
          <p class="mt-4 text-lg text-gray-500">
            Bedankt voor je bestelling. We hebben je gegevens ontvangen.
          </p>

          <div v-if="orderNumber" class="mt-6 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">Ordernummer</p>
            <p class="text-xl font-bold text-gray-900">{{ orderNumber }}</p>
          </div>

          <div class="mt-8 space-y-4">
            <p class="text-gray-600">
              Je ontvangt binnenkort een bevestigingsmail met alle details.
            </p>
            <p class="text-gray-600">
              Ons team zal je bestelling zo snel mogelijk verwerken.
            </p>
          </div>

          <div class="mt-8">
            <router-link
              to="/"
              class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-md font-medium hover:bg-indigo-700"
            >
              Terug naar Home
            </router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();
const orderNumber = computed(() => route.query.order as string);
</script>
