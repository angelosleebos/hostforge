import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAdminStore } from '../resources/js/stores/admin'
import api from '../resources/js/api'

vi.mock('../resources/js/api')

describe('adminStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('fetches customers successfully', async () => {
    const mockResponse = {
      data: {
        data: [
          { id: 1, email: 'test@example.com', full_name: 'Test User', status: 'active' },
        ],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 1, from: 1, to: 1 },
        links: { first: null, last: null, prev: null, next: null },
      },
    }
    vi.mocked(api.get).mockResolvedValue(mockResponse)

    const store = useAdminStore()
    await store.fetchCustomers()

    expect(store.customers).toHaveLength(1)
    expect(store.customers[0].email).toBe('test@example.com')
    expect(store.customersLoading).toBe(false)
    expect(store.customersError).toBeNull()
  })

  it('handles fetch customers error', async () => {
    vi.mocked(api.get).mockRejectedValue({
      response: { data: { message: 'Unauthorized' }, status: 401 },
    })

    const store = useAdminStore()
    await expect(store.fetchCustomers()).rejects.toBeDefined()

    expect(store.customersError).toBe('Unauthorized')
    expect(store.customersLoading).toBe(false)
  })
})
