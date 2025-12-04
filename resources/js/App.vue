<template>
  <div id="app" class="min-h-screen bg-white dark:bg-gray-900">
    <nav class="bg-white dark:bg-black shadow-sm border-b border-gray-200 dark:border-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/" class="text-2xl font-bold text-primary-500">
                HostingFirm
              </router-link>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <router-link
                to="/"
                class="border-transparent text-gray-600 dark:text-gray-300 hover:border-primary-500 hover:text-gray-900 dark:hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                Home
              </router-link>
              <router-link
                to="/hosting"
                class="border-transparent text-gray-600 dark:text-gray-300 hover:border-primary-500 hover:text-gray-900 dark:hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                Hosting Pakketten
              </router-link>
              <router-link
                to="/order"
                class="border-transparent text-gray-600 dark:text-gray-300 hover:border-primary-500 hover:text-gray-900 dark:hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
              >
                Bestellen
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <main>
      <router-view />
    </main>

    <footer class="bg-white dark:bg-black mt-16 border-t border-gray-200 dark:border-gray-800">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-gray-500 dark:text-gray-400 text-sm">
          &copy; 2025 HostingFirm. Alle rechten voorbehouden.
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
// Public app component
</script>
