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
        path: '/kategorijasa',
        name: 'KategoriJasa',
        component: () => import('@/views/kategorijasa/Kategorijasa.vue'),
        meta: { requiresAuth: true,requiresSuperadmin: true },
      },
      {
        path: '/activitylog',
        name: 'ActivityLog',
        component: () => import('@/views/activitylog/ActivityLog.vue'),
        meta: { requiresAuth: true },
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
        path: '/settings',
        name: 'Settings',
        component: () => import('@/views/settings/Settings.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/merek',
        name: 'Merek',
        component: () => import('@/views/base/merek/Merek.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/invoice',
        name: 'Invoice',
        component: () => import('@/views/base/invoice/Invoice.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/invoice/show',
        name: 'InvoiceShow',
        component: () => import('@/views/base/invoice/InvoiceShowModal.vue'),
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
        path: '/base/proyek/proyek',
        name: 'Proyek',
        component: () => import('@/views/base/proyek/Proyek.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/proyek/:id/detail',
        name: 'ProyekDetail',
        component: () => import('@/views/base/proyek/ProyekDetail.vue'),
        meta: { requiresAuth: true },
      },
      {
        path: '/base/termin/termin',
        name: 'Termin',
        component: () => import('@/views/base/termin/Termin.vue'),
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
        path: '/base/paymentmethod',
        name: 'PaymentMethod',
        component: () => import('@/views/base/paymentmethod/Payment.vue'),
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
        path: '/base/kasbon',
        name: 'Kasbon',
        component: () => import('@/views/base/kasbon/Kasbon.vue'),
        meta: { requiresAuth: true },
      },
    
    //   {
    //     path: '/base',
    //     name: 'Base',
    //     component: { render: () => h(resolveComponent('router-view')) },
    //     redirect: '/base/breadcrumbs',
    //     meta: { requiresAuth: true },
    //     children: [
    //       {
    //         path: 'accordion',
    //         name: 'Accordion',
    //         component: () => import('@/views/base/Accordion.vue'),
    //       },
    //       {
    //         path: 'breadcrumbs',
    //         name: 'Breadcrumbs',
    //         component: () => import('@/views/base/Breadcrumbs.vue'),
    //       },
    //       {
    //         path: 'cards',
    //         name: 'Cards',
    //         component: () => import('@/views/base/Cards.vue'),
    //       },
    //     ],
    //   },
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
  const role = sessionStorage.getItem('role')

  // Jika sudah login dan mencoba akses login, redirect ke dashboard
  if (token && to.path === '/login') {
    return next('/dashboard')
  }

  // Jika route membutuhkan autentikasi
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!token) {
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('role')
      return next('/login')
    }

    try {
      // Cek token valid
      const response = await api.get('/profile', {
        headers: { Authorization: `Bearer ${token}` }
      })

      // Jika user tidak valid
      if (!response.data || response.data.status !== 'active') {
        sessionStorage.removeItem('token')
        sessionStorage.removeItem('role')
        return next('/login')
      }

      // Cek role untuk route yang membutuhkan superadmin
      if (to.meta.requiresSuperadmin && role !== 'superadmin') {
        return next('/dashboard')
      }

      return next()
    } catch (error) {
      console.error('Auth error:', error)
      sessionStorage.removeItem('token')
      sessionStorage.removeItem('role')
      return next('/login')
    }
  }

  return next()
})

export default router
