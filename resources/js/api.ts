import axios, { type AxiosInstance, type AxiosError } from 'axios'

export interface ApiError {
  message: string
  status: number
  errors?: Record<string, string[]>
}

const api: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: false, // Use Bearer tokens only
  timeout: 30000,
})

// Request interceptor for API calls
api.interceptors.request.use(
  (config) => {
    const url = config.url || ''
    const isAdminRequest = url.startsWith('/admin')
    const isCustomerRequest = url.startsWith('/customer')

    let token: string | null = null
    if (isAdminRequest) {
      token = localStorage.getItem('admin_token')
    } else if (isCustomerRequest) {
      token = localStorage.getItem('customer_token')
    } else {
      token = localStorage.getItem('auth_token')
    }

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// Response interceptor for API calls
api.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const apiError: ApiError = {
      message: 'An error occurred',
      status: error.response?.status || 500,
    }

    if (error.response?.data) {
      const data = error.response.data as any
      apiError.message = data.message || apiError.message
      apiError.errors = data.errors
    }

    if (error.response?.status === 401) {
      const url = error.config?.url || ''
      if (url.startsWith('/admin')) {
        localStorage.removeItem('admin_token')
        localStorage.removeItem('admin')
      } else if (url.startsWith('/customer')) {
        localStorage.removeItem('customer_token')
      } else {
        localStorage.removeItem('auth_token')
      }
    }

    return Promise.reject(apiError)
  }
)

export default api
