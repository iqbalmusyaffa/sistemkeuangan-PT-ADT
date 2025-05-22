export default [
  {
    component: 'CNavItem',
    name: 'Dashboard',
    to: '/dashboard',
    icon: 'fa-tachometer-alt', // Valid Font Awesome icon
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
    icon: 'fa-users', // Valid Font Awesome icon
    roles: ['superadmin'],
    items: [
      {
        component: 'CNavItem',
        name: 'Data User',
        to: '/user',
        icon: 'fa-user', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Log Aktivitas',
        to: '/activitylog',
        icon: 'fa-history', // Valid Font Awesome icon
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
    icon: 'fa-list', // Valid Font Awesome icon
    roles: ['superadmin'],
  },
  {
    component: 'CNavItem',
    name: 'Kategori Jasa',
    to: '/kategorijasa',
    icon: 'fa-layer-group', // Valid Font Awesome icon
    roles: ['superadmin'],
  },

  {
    component: 'CNavTitle',
    name: 'Master Data',
  },
  {
    component: 'CNavGroup',
    name: 'Data Umum',
    icon: 'fa-book', // Valid Font Awesome icon
    items: [
      {
        component: 'CNavItem',
        name: 'Merek',
        to: '/base/merek',
        icon: 'fa-tags', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Unit',
        to: '/base/unit',
        icon: 'fa-puzzle-piece', // Valid Font Awesome icon
      },
    ],
  },

  {
    component: 'CNavGroup',
    name: 'Proyek',
    icon: 'fa-industry', // Valid Font Awesome icon
    items: [
      {
        component: 'CNavItem',
        name: 'Proyek',
        to: '/base/proyek/proyek',
        icon: 'fa-briefcase', // Valid Font Awesome icon
      },
    ],
  },

  {
    component: 'CNavGroup',
    name: 'Pembelian',
    icon: 'fa-shopping-cart', // Valid Font Awesome icon
    items: [
      {
        component: 'CNavItem',
        name: 'Pembelian Material',
        to: '/base/purchase/pembelian',
        icon: 'fa-shopping-basket', // Valid Font Awesome icon
      },
    ],
  },

  {
    component: 'CNavGroup',
    name: 'Transaksi Keuangan',
    icon: 'fa-money-bill-wave', // Valid Font Awesome icon
    items: [
      {
        component: 'CNavItem',
        name: 'Pencatatan Pemasukan',
        to: '/base/pemasukan',
        icon: 'fa-arrow-up', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Pengeluaran',
        to: '/base/pengeluaran',
        icon: 'fa-arrow-down', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Kasbon',
        to: '/base/kasbon',
        icon: 'fa-wallet', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Termin',
        to: '/base/termin/termin',
        icon: 'fa-calendar-alt', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Pencatatan Invoice',
        to: '/base/invoice',
        icon: 'fa-file-alt', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Metode Pembayaran',
        to: '/base/paymentmethod',
        icon: 'fa-credit-card', // Valid Font Awesome icon
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
    icon: 'fa-user', // Valid Font Awesome icon
    items: [
      {
        component: 'CNavItem',
        name: 'Profile',
        to: '/profile',
        icon: 'fa-user', // Valid Font Awesome icon
      },
      {
        component: 'CNavItem',
        name: 'Settings',
        to: '/settings',
        icon: 'fa-cogs', // Valid Font Awesome icon
      },
    ],
  },
];
