<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <div class="bg-white/5 backdrop-blur-sm border border-white/10 rounded-xl p-8">
        <div class="text-center mb-8">
          <h2 class="text-3xl font-bold text-white mb-2">Welkom terug</h2>
          <p class="text-gray-400">Log in op je klantaccount</p>
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
              placeholder="jouw@email.nl"
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
          <p class="text-gray-400 text-sm">
            Nog geen account?
            <router-link to="/customer/register" class="text-primary hover:text-primary/80 font-medium">
              Registreren
            </router-link>
          </p>
        </div>

        <div class="mt-4 text-center">
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
    const response = await api.post('/customer/login', form.value);
    
    // Store token
    localStorage.setItem('customer_token', response.data.data.token);
    localStorage.setItem('customer', JSON.stringify(response.data.data.customer));
    
    // Redirect to dashboard
    router.push('/customer/dashboard');
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Inloggen mislukt. Probeer het opnieuw.';
    submitting.value = false;
  }
};
</script>
