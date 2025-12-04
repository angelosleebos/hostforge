<template>
  <div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-8">
          <h1 class="text-3xl font-bold text-gray-900">Bestel Hosting</h1>
          <p class="mt-2 text-gray-500">Vul je gegevens in om te bestellen</p>

          <form @submit.prevent="handleSubmit" class="mt-8 space-y-6">
            <!-- Customer Information -->
            <div class="space-y-4">
              <h2 class="text-xl font-semibold text-gray-900">Klantgegevens</h2>
              
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Voornaam *</label>
                  <input
                    v-model="form.first_name"
                    type="text"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700">Achternaam *</label>
                  <input
                    v-model="form.last_name"
                    type="text"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email *</label>
                <input
                  v-model="form.email"
                  type="email"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Bedrijf</label>
                <input
                  v-model="form.company"
                  type="text"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Telefoonnummer *</label>
                <input
                  v-model="form.phone"
                  type="tel"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
            </div>

            <!-- Hosting Package -->
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Hosting Pakket</h2>
              <select
                v-model="form.hosting_package_id"
                required
                class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Selecteer een pakket</option>
                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                  {{ pkg.name }} - €{{ pkg.price }}/maand
                </option>
              </select>
            </div>

            <!-- Billing Cycle -->
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Facturatiecyclus</h2>
              <div class="mt-2 space-y-2">
                <label class="inline-flex items-center">
                  <input
                    v-model="form.billing_cycle"
                    type="radio"
                    value="monthly"
                    class="form-radio text-indigo-600"
                  />
                  <span class="ml-2">Maandelijks</span>
                </label>
                <label class="inline-flex items-center ml-6">
                  <input
                    v-model="form.billing_cycle"
                    type="radio"
                    value="yearly"
                    class="form-radio text-indigo-600"
                  />
                  <span class="ml-2">Jaarlijks (2 maanden gratis!)</span>
                </label>
              </div>
            </div>

            <!-- Domain -->
            <div>
              <h2 class="text-xl font-semibold text-gray-900">Domein</h2>
              <div class="mt-2 flex gap-2">
                <input
                  v-model="form.domain_name"
                  type="text"
                  placeholder="mijnwebsite"
                  required
                  class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
                <select
                  v-model="form.tld"
                  class="border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="com">.com</option>
                  <option value="nl">.nl</option>
                  <option value="be">.be</option>
                  <option value="org">.org</option>
                </select>
              </div>
            </div>

            <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded">
              {{ error }}
            </div>

            <div>
              <button
                type="submit"
                :disabled="submitting"
                class="w-full bg-indigo-600 text-white px-6 py-3 rounded-md font-medium hover:bg-indigo-700 disabled:opacity-50"
              >
                {{ submitting ? 'Bezig met bestellen...' : 'Bestelling Plaatsen' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../api';

interface HostingPackage {
  id: number;
  name: string;
  price: string;
}

const router = useRouter();
const route = useRoute();

const packages = ref<HostingPackage[]>([]);
const submitting = ref(false);
const error = ref('');

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  company: '',
  phone: '',
  hosting_package_id: route.query.package || '',
  billing_cycle: 'monthly',
  domain_name: '',
  tld: 'nl',
});

const fetchPackages = async () => {
  try {
    const response = await api.get('/hosting-packages');
    packages.value = response.data.data;
  } catch (err) {
    console.error('Error fetching packages:', err);
  }
};

const handleSubmit = async () => {
  submitting.value = true;
  error.value = '';

  try {
    const response = await api.post('/orders', {
      customer: {
        first_name: form.value.first_name,
        last_name: form.value.last_name,
        email: form.value.email,
        company: form.value.company,
        phone: form.value.phone,
      },
      order: {
        hosting_package_id: form.value.hosting_package_id,
        billing_cycle: form.value.billing_cycle,
        domains: [
          {
            domain_name: form.value.domain_name,
            tld: form.value.tld,
            register_domain: true,
          },
        ],
      },
    });

    // Redirect to Mollie checkout
    if (response.data.data.payment_url) {
      window.location.href = response.data.data.payment_url;
    } else {
      throw new Error('Geen betaal-URL ontvangen');
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Er is een fout opgetreden. Probeer het opnieuw.';
    submitting.value = false;
  }
};

onMounted(() => {
  fetchPackages();
});
</script>
