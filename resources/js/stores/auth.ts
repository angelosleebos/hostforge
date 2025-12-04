import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'

export interface Admin {
  id: number
  name: string
  email: string
}

export const useAuthStore = defineStore('auth', () => {
  const admin = ref<Admin | null>(null)
  const token = ref<string | null>(localStorage.getItem('admin_token'))

  const isAuthenticated = computed(() => !!token.value)

  async function login(email: string, password: string) {
    const response = await api.post('/admin/login', { email, password })
    const data = response.data?.data
    if (!data?.token || !data?.admin) {
      throw new Error('Invalid login response')
    }
    token.value = data.token
    admin.value = data.admin
    localStorage.setItem('admin_token', data.token)
    localStorage.setItem('admin', JSON.stringify(data.admin))
  }

  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (err) {
      console.warn('Logout API call failed', err)
    }
    token.value = null
    admin.value = null
    localStorage.removeItem('admin_token')
    localStorage.removeItem('admin')
  }

  function restoreSession() {
    const storedToken = localStorage.getItem('admin_token')
    const storedAdmin = localStorage.getItem('admin')
    if (storedToken && storedAdmin) {
      token.value = storedToken
      try {
        admin.value = JSON.parse(storedAdmin)
      } catch {
        admin.value = null
      }
    }
  }

  return {
    admin,
    token,
    isAuthenticated,
    login,
    logout,
    restoreSession,
  }
})
