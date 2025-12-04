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
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
