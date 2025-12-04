<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white sm:text-5xl md:text-6xl">
          Professionele Hosting voor<br />
          <span class="text-primary-500">Jouw Online Succes</span>
        </h1>
        <p class="mt-3 max-w-md mx-auto text-base text-gray-600 dark:text-gray-300 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
          Krachtige, betrouwbare en betaalbare hosting oplossingen voor jouw website of applicatie.
        </p>
        <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
          <div class="rounded-md shadow">
            <router-link
              to="/hosting"
              class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-500 hover:bg-primary-600 md:py-4 md:text-lg md:px-10"
            >
              Bekijk Pakketten
            </router-link>
          </div>
          <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
            <router-link
              to="/order"
              class="w-full flex items-center justify-center px-8 py-3 border border-gray-300 dark:border-gray-700 text-base font-medium rounded-md text-primary-500 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 md:py-4 md:text-lg md:px-10"
            >
              Direct Bestellen
            </router-link>
          </div>
        </div>
      </div>

      <div class="mt-20">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">99.9% Uptime</h3>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Betrouwbare hosting met gegarandeerde uptime voor jouw website.
              </p>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">24/7 Support</h3>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Ons support team staat altijd voor je klaar, dag en nacht.
              </p>
            </div>
          </div>

          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-white">Gratis SSL</h3>
              <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Beveilig jouw website met een gratis SSL certificaat.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Home page
</script>
