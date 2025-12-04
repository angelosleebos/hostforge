import axios, { type AxiosInstance } from 'axios';

const api: AxiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// Request interceptor for API calls
api.interceptors.request.use(
  (config) => {
    // Check if this is an admin or customer request
    const isAdminRequest = config.url?.startsWith('/admin');
    const isCustomerRequest = config.url?.startsWith('/customer');
    
    let token = null;
    if (isAdminRequest) {
      token = localStorage.getItem('admin_token');
    } else if (isCustomerRequest) {
      token = localStorage.getItem('customer_token');
    } else {
      // Fallback to generic auth_token for other requests
      token = localStorage.getItem('auth_token');
    }
    
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for API calls
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    console.error('API Error:', error.response?.status, error.response?.data);
    if (error.response?.status === 401) {
      console.log('Unauthorized, removing tokens and redirecting to login');
      
      // Determine which token to remove and where to redirect based on the request URL
      const requestUrl = error.config?.url || '';
      if (requestUrl.startsWith('/admin')) {
        localStorage.removeItem('admin_token');
        if (!window.location.pathname.includes('/admin/login')) {
          window.location.href = '/admin/login';
        }
      } else if (requestUrl.startsWith('/customer')) {
        localStorage.removeItem('customer_token');
        if (!window.location.pathname.includes('/customer/login')) {
          window.location.href = '/customer/login';
        }
      } else {
        localStorage.removeItem('auth_token');
      }
    }
    return Promise.reject(error);
  }
);

export default api;
