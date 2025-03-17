import { h, resolveComponent } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import axios from 'axios';

import DefaultLayout from '@/layouts/DefaultLayout.vue';
import Login from '@/components/Login.vue';
import Register from '@/components/Register.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
  },
  {
    path: '/',
    redirect: '/dashboard', // Redirect default ke dashboard
    component: DefaultLayout,
    children: [
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () =>
          import(/* webpackChunkName: "dashboard" */ '@/views/dashboard/Dashboard.vue'),
        meta: { requiresAuth: true }, // Proteksi halaman dashboard
      },
      {
        path: '/kategori',
        name: 'Kategori',
        component: () => import('@/views/kategori/Kategori.vue'),
        // component: () => import('@/views/theme/Colors.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/profile',
        name: 'Profile',
        component: () => import('@/views/profile/Profile.vue'),
        // component: () => import('@/views/theme/Colors.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/user',
        name: 'User',
        component: () => import('@/views/user/User.vue'),
        // component: () => import('@/views/theme/Colors.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/theme/typography',
        name: 'Typography',
        component: () => import('@/views/theme/Typography.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base',
        name: 'Base',
        component: { render: () => h(resolveComponent('router-view')) },
        redirect: '/base/breadcrumbs',
        meta: { requiresAuth: true },
        children: [
          {
            path: '/base/accordion',
            name: 'Accordion',
            component: () => import('@/views/base/Accordion.vue'),
          },
          {
            path: '/base/breadcrumbs',
            name: 'Breadcrumbs',
            component: () => import('@/views/base/Breadcrumbs.vue'),
          },
          {
            path: '/base/cards',
            name: 'Cards',
            component: () => import('@/views/base/Cards.vue'),
          },
        ],
      },
      {
        path: '/charts',
        name: 'Charts',
        component: () => import('@/views/charts/Charts.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/widgets',
        name: 'Widgets',
        component: () => import('@/views/widgets/Widgets.vue'),
        meta: { requiresAuth: true },
      },
    ],
  },
  {
    path: '/pages',
    redirect: '/pages/404',
    component: { render: () => h(resolveComponent('router-view')) },
    children: [
      {
        path: '404',
        name: 'Page404',
        component: () => import('@/views/pages/Page404.vue'),
      },
      {
        path: '500',
        name: 'Page500',
        component: () => import('@/views/pages/Page500.vue'),
      },
    ],
  },
  {
    path: '/:catchAll(.*)',
    redirect: '/pages/404',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

// Middleware untuk cek autentikasi sebelum masuk halaman yang butuh login
router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('token');

  // Redirect ke dashboard jika sudah login dan mencoba ke login atau register
  if (token && (to.path === '/login' || to.path === '/register')) {
    return next('/dashboard');
  }

  // Cek apakah rute membutuhkan autentikasi
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      return next('/login'); // Jika tidak ada token, redirect ke login
    }
    try {
      await axios.get('http://localhost:8000/api/user', {
        withCredentials: true,
        headers: { Authorization: `Bearer ${token}` },
      });
      return next(); // Jika sukses, lanjut ke halaman yang dituju
    } catch (error) {
      localStorage.removeItem('token');
      return next('/login'); // Jika token invalid, redirect ke login
    }
  }

  return next(); // Jika tidak butuh autentikasi, langsung lanjutkan
});

export default router;
