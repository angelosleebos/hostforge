import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '../api'

export interface Customer {
  id: number
  email: string
  first_name: string
  last_name: string
  full_name: string
  company?: string
  phone?: string
  status: string
}

export interface Order {
  id: number
  order_number: string
  status: string
  total_amount: string
  customer?: Customer
}

export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export const useAdminStore = defineStore('admin', () => {
  const customers = ref<Customer[]>([])
  const customersMeta = ref<PaginationMeta | null>(null)
  const customersLinks = ref<PaginationLinks | null>(null)
  const customersLoading = ref(false)
  const customersError = ref<string | null>(null)

  const orders = ref<Order[]>([])
  const ordersMeta = ref<PaginationMeta | null>(null)
  const ordersLinks = ref<PaginationLinks | null>(null)
  const ordersLoading = ref(false)
  const ordersError = ref<string | null>(null)

  async function fetchCustomers(page = 1) {
    customersLoading.value = true
    customersError.value = null
    try {
      const response = await api.get(`/admin/customers?page=${page}`)
      customers.value = response.data?.data || []
      customersMeta.value = response.data?.meta || null
      customersLinks.value = response.data?.links || null
    } catch (err: any) {
      customersError.value = err?.response?.data?.message || 'Failed to fetch customers'
      throw err
    } finally {
      customersLoading.value = false
    }
  }

  async function fetchOrders(page = 1) {
    ordersLoading.value = true
    ordersError.value = null
    try {
      const response = await api.get(`/admin/orders?page=${page}`)
      orders.value = response.data?.data || []
      ordersMeta.value = response.data?.meta || null
      ordersLinks.value = response.data?.links || null
    } catch (err: any) {
      ordersError.value = err?.response?.data?.message || 'Failed to fetch orders'
      throw err
    } finally {
      ordersLoading.value = false
    }
  }

  return {
    customers,
    customersMeta,
    customersLinks,
    customersLoading,
    customersError,
    fetchCustomers,
    orders,
    ordersMeta,
    ordersLinks,
    ordersLoading,
    ordersError,
    fetchOrders,
  }
})
