import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initializes with no token or admin', () => {
    const store = useAuthStore()
    expect(store.token).toBeNull()
    expect(store.admin).toBeNull()
    expect(store.isAuthenticated).toBe(false)
  })

  it('restores session from localStorage', () => {
    localStorage.setItem('admin_token', 'test-token-123')
    localStorage.setItem('admin', JSON.stringify({ id: 1, name: 'Admin', email: 'admin@test.com' }))

    const store = useAuthStore()
    store.restoreSession()

    expect(store.token).toBe('test-token-123')
    expect(store.admin).toEqual({ id: 1, name: 'Admin', email: 'admin@test.com' })
    expect(store.isAuthenticated).toBe(true)
  })

  it('clears session on logout', () => {
    const store = useAuthStore()
    store.token = 'test-token'
    store.admin = { id: 1, name: 'Admin', email: 'admin@test.com' }
    localStorage.setItem('admin_token', 'test-token')
    localStorage.setItem('admin', JSON.stringify(store.admin))

    store.logout()

    expect(store.token).toBeNull()
    expect(store.admin).toBeNull()
    expect(localStorage.getItem('admin_token')).toBeNull()
    expect(localStorage.getItem('admin')).toBeNull()
  })
})
