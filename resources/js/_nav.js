export default [
  {
    component: 'CNavItem',
    name: 'Dashboard',
    to: '/dashboard',
    icon: 'fa-tachometer-alt',
    badge: { color: 'primary' },
  },

  // === FITUR UTAMA ===
  {
    component: 'CNavTitle',
    name: 'Transaksi Keuangan',
  },
  {
    component: 'CNavGroup',
    name: 'Pemasukan & Pengeluaran',
    icon: 'fa-money-bill-wave',
    roles: ['admin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Pemasukan',
        to: '/base/pemasukan',
        icon: 'fa-arrow-up',
      },
      {
        component: 'CNavItem',
        name: 'Pengeluaran',
        to: '/base/pengeluaran',
        icon: 'fa-arrow-down',
      },
      {
        component: 'CNavItem',
        name: 'Termin Pembayaran',
        to: '/base/termin/termin',
        icon: 'fa-calendar-alt',
      },
      {
        component: 'CNavItem',
        name: 'Invoice',
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
    name: 'Laporan',
    icon: 'fa-chart-line',
    items: [
      {
        component: 'CNavItem',
        name: 'Laporan Laba Rugi',
        to: '/base/profit',
        icon: 'fa-balance-scale',
      },
    ],
  },

  // === ADMINISTRASI SISTEM ===
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
        name: 'Data Pengguna',
        to: '/user',
        icon: 'fa-user',
      },
    ],
  },
  {
    component: 'CNavItem',
    name: 'Log Aktivitas',
    to: '/activitylog',
    icon: 'fa-history',
    roles: ['superadmin'],
  },
  {
    component: 'CNavItem',
    name: 'Kategori Material',
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

  // === PENGATURAN AKUN ===
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
        name: 'Profil',
        to: '/profile',
        icon: 'fa-user-circle',
      },
      {
        component: 'CNavItem',
        name: 'Pengaturan',
        to: '/settings',
        icon: 'fa-cogs',
      },
    ],
  },

  // === MASTER DATA (DITARUH PALING BAWAH) ===
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
        name: 'Data Proyek',
        to: '/base/proyek/proyek',
        icon: 'fa-briefcase',
      },
    ],
  },
];
