export default [
  {
    component: 'CNavItem',
    name: 'Dashboard',
    to: '/dashboard',
    icon: 'cil-speedometer', // Lebih representatif untuk dashboard
    badge: {
      color: 'primary',
    },
  },

  {
    component: 'CNavTitle',
    name: 'Administrasi',
    roles: ['superadmin'],
  },

  {
    component: 'CNavGroup',
    name: 'Manajemen Pengguna',
    icon: 'cil-people',
    roles: ['superadmin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Data User',
        to: '/user',
        icon: 'cil-user',
      },
      {
        component: 'CNavItem',
        name: 'Log Aktivitas',
        to: '/activitylog',
        icon: 'cil-history',
      },
    ],
  },

  {
    component: 'CNavTitle',
    name: 'Manajemen Kategori',
    roles: ['superadmin'],
  },
  {
    component: 'CNavItem',
    name: 'Kategori',
    to: '/kategori',
    icon: 'cil-list-rich',
    roles: ['superadmin'],
  },
  {
    component: 'CNavItem',
    name: 'Kategori Jasa',
    to: '/kategorijasa',
    icon: 'cil-layers',
    roles: ['superadmin'],
  },

  {
    component: 'CNavTitle',
    name: 'Master Data',
  },
  {
    component: 'CNavGroup',
    name: 'Data Umum',
    icon: 'cil-library',
    items: [
      {
        component: 'CNavItem',
        name: 'Merek',
        to: '/base/merek',
        icon: 'cil-tags',
      },
      {
        component: 'CNavItem',
        name: 'Unit',
        to: '/base/unit',
        icon: 'cil-puzzle',
      },
    ],
  },

  {
    component: 'CNavGroup',
    name: 'Proyek',
    icon: 'cil-factory',
    items: [
      {
        component: 'CNavItem',
        name: 'Proyek',
        to: '/base/proyek/proyek',
        icon: 'cil-briefcase',
      },
    ],
  },

  {
    component: 'CNavGroup',
    name: 'Pembelian',
    icon: 'cil-cart',
    items: [
      {
        component: 'CNavItem',
        name: 'Pembelian Material',
        to: '/base/purchase/pembelian',
        icon: 'cil-basket',
      },
    ],
  },

  {
    component: 'CNavGroup',
    name: 'Transaksi Keuangan',
    icon: 'cil-cash',
    items: [
      {
        component: 'CNavItem',
        name: 'Pencatatan Pemasukan',
        to: '/base/pemasukan',
        icon: 'cil-arrow-circle-top',
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Pengeluaran',
        to: '/base/pengeluaran',
        icon: 'cil-arrow-circle-bottom',
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Piutang',
        to: '/base/piutang',
        icon: 'cil-money',
      },
      {
        component: 'CNavItem',
        name: 'Kasbon',
        to: '/base/kasbon',
        icon: 'cil-wallet',
      },
      {
        component: 'CNavItem',
        name: 'Termin',
        to: '/base/termin/termin',
        icon: 'cil-calendar',
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Invoice',
        to: '/base/invoice',
        icon: 'cil-file',
      },
      {
        component: 'CNavItem',
        name: 'Metode Pembayaran',
        to: '/base/paymentmethod',
        icon: 'cil-credit-card',
      },
    ],
  },

  {
    component: 'CNavTitle',
    name: 'Pengaturan',
  },
  {
    component: 'CNavGroup',
    name: 'Akun',
    icon: 'cil-user',
    items: [
      {
        component: 'CNavItem',
        name: 'Profile',
        to: '/profile',
        icon: 'cil-user',
      },
      {
        component: 'CNavItem',
        name: 'Settings',
        to: '/settings',
        icon: 'cil-settings',
      },
    ],
  },
]
