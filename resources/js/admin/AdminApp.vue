<template>
  <div id="admin-app" class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <nav v-if="isAuthenticated" class="bg-black border-b border-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/admin" class="text-2xl font-bold text-primary-500">
                Admin Dashboard
              </router-link>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                to="/admin"
                class="text-gray-300 hover:bg-gray-900 hover:text-primary-500 inline-flex items-center px-3 py-2 rounded-md text-sm font-medium"
              >
                Dashboard
              </router-link>
              <router-link
                to="/admin/customers"
                class="text-gray-300 hover:bg-gray-900 hover:text-primary-500 inline-flex items-center px-3 py-2 rounded-md text-sm font-medium"
              >
                Klanten
              </router-link>
              <router-link
                to="/admin/orders"
                class="text-gray-300 hover:bg-gray-900 hover:text-primary-500 inline-flex items-center px-3 py-2 rounded-md text-sm font-medium"
              >
                Orders
              </router-link>
              <router-link
                to="/admin/billing"
                class="text-gray-300 hover:bg-gray-900 hover:text-primary-500 inline-flex items-center px-3 py-2 rounded-md text-sm font-medium"
              >
                Facturatie
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <button
              @click="handleLogout"
              class="text-gray-300 hover:bg-gray-900 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
            >
              Uitloggen
            </button>
          </div>
        </div>
      </div>
    </nav>

    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const isAuthenticated = computed(() => {
  return !!localStorage.getItem('auth_token');
});

const handleLogout = () => {
  localStorage.removeItem('auth_token');
  router.push({ name: 'admin-login' });
};
</script>
