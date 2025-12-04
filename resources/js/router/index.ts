import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'home',
    component: () => import('../pages/Home.vue'),
  },
  {
    path: '/hosting',
    name: 'hosting',
    component: () => import('../pages/Hosting.vue'),
  },
  {
    path: '/order',
    name: 'order',
    component: () => import('../pages/Order.vue'),
  },
  {
    path: '/order/success',
    name: 'order-success',
    component: () => import('../pages/OrderSuccess.vue'),
  },
  {
    path: '/payment/return',
    name: 'payment-return',
    component: () => import('../pages/PaymentReturn.vue'),
  },
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('../pages/admin/Dashboard.vue'),
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: () => import('../pages/admin/Login.vue'),
  },
  {
    path: '/admin/pending-customers',
    name: 'admin-pending-customers',
    component: () => import('../pages/admin/PendingCustomers.vue'),
  },
  {
    path: '/admin/orders',
    name: 'admin-orders',
    component: () => import('../pages/admin/Orders.vue'),
  },
  {
    path: '/customer/login',
    name: 'customer-login',
    component: () => import('../pages/customer/Login.vue'),
  },
  {
    path: '/customer/dashboard',
    name: 'customer-dashboard',
    component: () => import('../pages/customer/Dashboard.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
