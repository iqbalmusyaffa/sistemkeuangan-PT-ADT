export default [
    {
      component: 'CNavItem',
      name: 'Dashboard',
      to: '/dashboard',
      icon: 'cil-speedometer',
      badge: {
        color: 'primary',
        text: 'NEW',
      },
    },
    {
      component: 'CNavTitle',
      name: 'Manajemen Pengguna',
    },
    {
      component: 'CNavItem',
      name: 'User',
      to: '/user',
      icon: 'cil-user',
    },
    {
      component: 'CNavItem',
      name: 'Kategori',
      to: '/kategori',
      icon: 'cil-list',
    },
    {
      component: 'CNavTitle',
      name: 'Master Data',
    },
    {
      component: 'CNavGroup',
      name: 'Master Data',
      to: '/base',
      icon: 'cil-folder',
      items: [
        {
          component: 'CNavItem',
          name: 'Data Perusahaan',
          to: '/base/company',
        },
        {
          component: 'CNavItem',
          name: 'Pencatatan Pemasukan',
          to: '/base/pemasukan',
        },
        {
          component: 'CNavItem',
          name: 'Pencatatan Pengeluaran',
          to: '/base/pengeluaran',
        },
        {
          component: 'CNavItem',
          name: 'Pencatatan Piutang',
          to: '/base/piutang',
        },
        {
          component: 'CNavItem',
          name: 'Kasbon',
          to: '/base/kasbon',
        },
      ],
    },
    {
      component: 'CNavTitle',
      name: 'Lainnya',
    },
    {
      component: 'CNavItem',
      name: 'Profile',
      to: '/profile',
      icon: 'cil-user',
    },
    {
      component: 'CNavItem',
      name: 'Widgets',
      to: '/widgets',
      icon: 'cil-calculator',
      badge: {
        color: 'primary',
        text: 'NEW',
        shape: 'pill',
      },
    },
    {
      component: 'CNavTitle',
      name: 'Halaman Error',
    },
    {
      component: 'CNavItem',
      name: 'Error 404',
      to: '/pages/404',
      icon: 'cil-ban',
    },
    {
      component: 'CNavItem',
      name: 'Error 500',
      to: '/pages/500',
      icon: 'cil-ban',
    },
  ]
