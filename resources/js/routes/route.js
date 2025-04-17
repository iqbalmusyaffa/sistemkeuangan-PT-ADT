import { h, resolveComponent } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import api from '@/utils/axios'
import DefaultLayout from '@/layouts/DefaultLayout.vue'
import Login from '@/components/Login.vue'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
  },
  {
    path: '/',
    redirect: '/dashboard',
    component: DefaultLayout,
    children: [
      {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/Dashboard.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/kategori',
        name: 'Kategori',
        component: () => import('@/views/kategori/Kategori.vue'),
        meta: { requiresAuth: true,requiresSuperadmin: true },
      },
      {
        path: '/user',
        name: 'User',
        component: () => import('@/views/user/User.vue'),
        meta: { requiresAuth: true,requiresSuperadmin: true },
      },
      {
        path: '/profile',
        name: 'Profile',
        component: () => import('@/views/profile/Profile.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/merek',
        name: 'Merek',
        component: () => import('@/views/base/merek/Merek.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/unit',
        name: 'Unit',
        component: () => import('@/views/base/unit/Unit.vue'),
        meta: { requiresAuth: true, },
      },
      {
        path: '/base/purchase/pembelian',
        name: 'Pembelian',
        component: () => import('@/views/base/purchase/Pembelian.vue'),
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
  const token = sessionStorage.getItem('token')
  const role = sessionStorage.getItem('role');  // Ambil role dari sessionStorage

  // Jika sudah login, redirect dari login ke dashboard
  if (token && (to.path === '/login')) {
    return next('/dashboard')
  }

  // Jika route membutuhkan autentikasi
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) return next('/login') // Jika token tidak ada, arahkan ke login

    try {
      // Cek apakah token valid dan apakah user ada di database
      const response = await api.get('/profile', {
        headers: { Authorization: `Bearer ${token}` },
      })

      // Jika user tidak ada atau tidak aktif, redirect ke login atau halaman lain
      if (!response.data || response.data.status !== 'active') {
        sessionStorage.removeItem('token') // Hapus token jika user tidak valid
        sessionStorage.removeItem('role');  // Hapus role jika user tidak valid
        return next('/login')
      }
 // Cek apakah role sesuai untuk route ini
 if (to.meta.requiresSuperadmin && role !== 'superadmin') {
    return next('/dashboard');  // Arahkan ke dashboard jika bukan superadmin
  }
      // Jika user valid, lanjutkan ke route yang diminta
      return next()
    } catch (error) {
      // Jika ada error pada pengecekan profil (misalnya token expired)
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('role');
      return next('/login')
    }
  }

  return next() // Lanjutkan jika tidak perlu autentikasi
})

export default router
