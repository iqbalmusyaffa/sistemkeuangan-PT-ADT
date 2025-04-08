import { h, resolveComponent } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/utils/axios'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Login from '@/components/Login.vue'
import Register from '@/components/Register.vue'

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
    redirect: '/dashboard',
    component: DefaultLayout,
    children: [
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () =>
          import('@/views/dashboard/Dashboard.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/kategori',
        name: 'Kategori',
        component: () => import('@/views/kategori/Kategori.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/user',
        name: 'User',
        component: () => import('@/views/user/User.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/profile',
        name: 'Profile',
        component: () => import('@/views/profile/Profile.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/company',
        name: 'DataPerusahaan',
        component: () => import('@/views/base/company/Company.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/pemasukan',
        name: 'Pemasukan',
        component: () => import('@/views/base/pemasukan/Pemasukan.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/pemasukan/:id',
        name: 'DetailPemasukan',
        component: () => import('@/views/base/pemasukan/DetailPemasukan.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/pengeluaran',
        name: 'Pengeluaran',
        component: () => import('@/views/base/pengeluaran/Pengeluaran.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/pengeluaran/:id',
        name: 'DetailPengeluaran',
        component: () => import('@/views/base/pengeluaran/DetailPengeluaran.vue'),
        meta: { requiresAuth: true },
      },
    //   {
    //     path: '/base/piutang',
    //     name: 'Piutang',
    //     component: () => import('@/views/base/piutang/Piutang.vue'),
    //     meta: { requiresAuth: true },
    //   },
    //   {
    //     path: '/base/kasbon',
    //     name: 'Kasbon',
    //     component: () => import('@/views/base/kasbon/Kasbon.vue'),
    //     meta: { requiresAuth: true },
    //   },
      {
        path: '/base',
        name: 'Base',
        component: { render: () => h(resolveComponent('router-view')) },
        redirect: '/base/breadcrumbs',
        meta: { requiresAuth: true },
        children: [
          {
            path: 'accordion',
            name: 'Accordion',
            component: () => import('@/views/base/Accordion.vue'),
          },
          {
            path: 'breadcrumbs',
            name: 'Breadcrumbs',
            component: () => import('@/views/base/Breadcrumbs.vue'),
          },
          {
            path: 'cards',
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
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

// Middleware untuk proteksi halaman dengan token
router.beforeEach(async (to, from, next) => {
  const token = localStorage.getItem('token')

  if (token && (to.path === '/login' || to.path === '/register')) {
    return next('/dashboard')
  }

  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      return next('/login')
    }

    try {
      await api.get('/user', {
        headers: { Authorization: `Bearer ${token}` },
      })
      return next()
    } catch (error) {
      localStorage.removeItem('token')
      return next('/login')
    }
  }

  return next()
})

export default router
