<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">Onze Hosting Pakketten</h1>
        <p class="mt-4 text-xl text-gray-600 dark:text-gray-300">
          Kies het pakket dat het beste bij jouw behoeften past
        </p>
      </div>

      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-600 dark:text-gray-300">Pakketten laden...</p>
      </div>

      <div v-else class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="pkg in packages"
          :key="pkg.id"
          class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-lg hover:shadow-xl transition-shadow border border-gray-200 dark:border-gray-700"
        >
          <div class="px-6 py-8">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ pkg.name }}</h3>
            <p class="mt-4 text-gray-600 dark:text-gray-300">{{ pkg.description }}</p>
            
            <div class="mt-8">
              <p class="text-4xl font-bold text-gray-900 dark:text-white">
                €{{ pkg.price }}
                <span class="text-base font-normal text-gray-600 dark:text-gray-300">/maand</span>
              </p>
            </div>

            <ul class="mt-8 space-y-4">
              <li class="flex items-start">
                <span class="text-primary-500 mr-2">✓</span>
                <span class="text-gray-700 dark:text-gray-300">{{ Math.round(pkg.disk_space_mb / 1024) }}GB Opslag</span>
              </li>
              <li class="flex items-start">
                <span class="text-primary-500 mr-2">✓</span>
                <span class="text-gray-700 dark:text-gray-300">{{ pkg.bandwidth_gb }}GB Bandbreedte</span>
              </li>
              <li class="flex items-start">
                <span class="text-primary-500 mr-2">✓</span>
                <span class="text-gray-700 dark:text-gray-300">{{ pkg.domains }} {{ pkg.domains === 1 ? 'Domein' : 'Domeinen' }}</span>
              </li>
              <li class="flex items-start">
                <span class="text-primary-500 mr-2">✓</span>
                <span class="text-gray-700 dark:text-gray-300">{{ pkg.databases }} {{ pkg.databases === 1 ? 'Database' : 'Databases' }}</span>
              </li>
              <li class="flex items-start">
                <span class="text-primary-500 mr-2">✓</span>
                <span class="text-gray-700 dark:text-gray-300">{{ pkg.email_accounts }} E-mail Accounts</span>
              </li>
            </ul>

            <div class="mt-8">
              <router-link
                :to="{ name: 'order', query: { package: pkg.id } }"
                class="block w-full bg-primary-500 text-white text-center px-6 py-3 rounded-md font-medium hover:bg-primary-600"
              >
                Bestel Nu
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api';

interface HostingPackage {
  id: number;
  name: string;
  description: string;
  price: string;
  price_yearly: string;
  disk_space_mb: number;
  bandwidth_gb: number;
  domains: number;
  databases: number;
  email_accounts: number;
}

const packages = ref<HostingPackage[]>([]);
const loading = ref(true);

const fetchPackages = async () => {
  try {
    const response = await api.get('/hosting-packages');
    packages.value = response.data.data;
  } catch (error) {
    console.error('Error fetching packages:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchPackages();
});
</script>
