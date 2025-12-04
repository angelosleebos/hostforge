import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('../pages/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/customers',
    name: 'admin-customers',
    component: () => import('../pages/Customers.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/orders',
    name: 'admin-orders',
    component: () => import('../pages/Orders.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/billing',
    name: 'admin-billing',
    component: () => import('../pages/Billing.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: () => import('../pages/Login.vue'),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// Navigation guard for authentication
router.beforeEach((to, _from, next) => {
  const token = localStorage.getItem('admin_token')

  if (to.meta.requiresAuth && !token) {
    next({
      name: 'admin-login',
      query: { redirect: to.fullPath },
    })
  } else if (to.name === 'admin-login' && token) {
    const redirect = (to.query.redirect as string) || '/admin'
    next(redirect)
  } else {
    next()
  }
})

export default router;
