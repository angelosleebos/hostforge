<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
        <div class="text-center mb-8">
          <div class="mx-auto w-16 h-16 bg-gradient-to-r from-primary to-purple-600 rounded-xl flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-white mb-2">Admin Login</h2>
          <p class="text-gray-400">Toegang tot het beheerportaal</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">E-mailadres</label>
            <input
              v-model="form.email"
              type="email"
              required
              autocomplete="email"
              class="w-full px-4 py-3 bg-black/30 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="admin@hostforge.nl"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Wachtwoord</label>
            <input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password"
              class="w-full px-4 py-3 bg-black/30 border border-white/10 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
              placeholder="••••••••"
            />
          </div>

          <div v-if="error" class="bg-red-500/10 border border-red-500/20 rounded-lg p-4">
            <p class="text-red-400 text-sm">{{ error }}</p>
          </div>

          <button
            type="submit"
            :disabled="submitting"
            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg text-white bg-gradient-to-r from-primary to-purple-600 hover:from-primary/90 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="!submitting">Inloggen</span>
            <span v-else class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Bezig met inloggen...
            </span>
          </button>
        </form>

        <div class="mt-6 text-center">
          <router-link to="/" class="text-gray-400 hover:text-white text-sm transition-colors">
            ← Terug naar homepage
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../../api';

const router = useRouter();
const form = ref({
  email: '',
  password: '',
});
const error = ref('');
const submitting = ref(false);

const handleLogin = async () => {
  error.value = '';
  submitting.value = true;

  try {
    const response = await api.post('/admin/login', form.value);
    
    // Store token
    localStorage.setItem('admin_token', response.data.data.token);
    localStorage.setItem('admin', JSON.stringify(response.data.data.admin));
    
    // Redirect to admin dashboard
    router.push('/admin');
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Inloggen mislukt. Controleer je gegevens.';
    submitting.value = false;
  }
};
</script>
