export default [
  {
    component: 'CNavItem',
    name: 'Dashboard',
    to: '/dashboard',
    icon: 'fa-tachometer-alt',
    badge: { color: 'primary' },
  },

  {
    component: 'CNavTitle',
    name: 'Administrasi',
    roles: ['superadmin'],
  },
  {
    component: 'CNavGroup',
    name: 'Manajemen Pengguna',
    icon: 'fa-users',
    roles: ['superadmin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Data User',
        to: '/user',
        icon: 'fa-user',
      },
      {
        component: 'CNavItem',
        name: 'Log Aktivitas',
        to: '/activitylog',
        icon: 'fa-history',
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
    icon: 'fa-list',
    roles: ['superadmin'],
  },
  {
    component: 'CNavItem',
    name: 'Kategori Jasa',
    to: '/kategorijasa',
    icon: 'fa-layer-group',
    roles: ['superadmin'],
  },

  {
    component: 'CNavTitle',
    name: 'Master Data',
  },
  {
    component: 'CNavGroup',
    name: 'Data Umum',
    icon: 'fa-book',
      roles: ['admin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Merek',
        to: '/base/merek',
        icon: 'fa-tags',
      },
      {
        component: 'CNavItem',
        name: 'Unit',
        to: '/base/unit',
        icon: 'fa-puzzle-piece',
      },
    ],
  },
  {
    component: 'CNavGroup',
    name: 'Proyek',
    icon: 'fa-industry',
    roles: ['admin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Proyek',
        to: '/base/proyek/proyek',
        icon: 'fa-briefcase',
      },
    ],
  },
  {
    component: 'CNavGroup',
    name: 'Laporan',
    icon: 'fa-chart-line',
    items: [
      {
        component: 'CNavItem',
        name: 'Laba Rugi',
        to: '/base/profit',
        icon: 'fa-balance-scale',
      },
    ],
  },
  {
    component: 'CNavGroup',
    name: 'Pembelian',
    icon: 'fa-shopping-cart',
    roles: ['admin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Pembelian Material',
        to: '/base/purchase/pembelian',
        icon: 'fa-shopping-basket',
      },
    ],
  },
  {
    component: 'CNavGroup',
    name: 'Transaksi Keuangan',
    icon: 'fa-money-bill-wave',
    roles: ['admin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Pencatatan Pemasukan',
        to: '/base/pemasukan',
        icon: 'fa-arrow-up',
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Pengeluaran',
        to: '/base/pengeluaran',
        icon: 'fa-arrow-down',
      },
    //   {
    //     component: 'CNavItem',
    //     name: 'Kasbon',
    //     to: '/base/kasbon',
    //     icon: 'fa-wallet',
    //   },
      {
        component: 'CNavItem',
        name: 'Termin',
        to: '/base/termin/termin',
        icon: 'fa-calendar-alt',
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Invoice',
        to: '/base/invoice',
        icon: 'fa-file-alt',
      },
      {
        component: 'CNavItem',
        name: 'Metode Pembayaran',
        to: '/base/paymentmethod',
        icon: 'fa-credit-card',
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
    icon: 'fa-user',
    items: [
      {
        component: 'CNavItem',
        name: 'Profile',
        to: '/profile',
        icon: 'fa-user',
      },
      {
        component: 'CNavItem',
        name: 'Settings',
        to: '/settings',
        icon: 'fa-cogs',
      },
    ],
  },
];
