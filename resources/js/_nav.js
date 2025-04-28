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
      roles: ['superadmin'], // Role yang bisa mengakses

    },
    {
      component: 'CNavItem',
      name: 'User',
      to: '/user',
      icon: 'cil-user',
      roles: ['superadmin'], // Role yang bisa mengakses

    },
    {
      component: 'CNavItem',
      name: 'Kategori',
      to: '/kategori',
      icon: 'cil-list',
      roles: ['superadmin'], // Role yang bisa mengakses

    },
    {
        component: 'CNavItem',
        name: 'Activity Log',
        to: '/activitylog',
        icon: 'cil-list',
        // roles: ['superadmin'], // Role yang bisa mengakses

      },
    {
        component: 'CNavItem',
        name: 'Kategori Jasa',
        to: '/kategorijasa',
        icon: 'cil-list',
        roles: ['superadmin'],
      },
    {
        component: 'CNavTitle',
        name: 'Master Data Pembelian',
      },
    {
        component: 'CNavGroup',
        name: 'Master Data Pembelian',
        to: '/base',
        icon: 'cil-folder',
        items: [
          {
              component: 'CNavItem',
              name: 'Merek',
              to: '/base/merek',
            },
            {
              component: 'CNavItem',
              name: 'Unit',
              to: '/base/unit',
            },
        ],
      },
      {
        component: 'CNavTitle',
        name: 'Master Data Proyek',
      },
    {
        component: 'CNavGroup',
        name: 'Master Data Proyek',
        to: '/base',
        icon: 'cil-folder',
        items: [
            {
              component: 'CNavItem',
              name: 'Proyek',
              to: '/base/proyek/proyek',
            },
            {
              component: 'CNavItem',
              name: 'Pembelian Material',
              to: '/base/purchase/pembelian',
            },
        ],
      },
      {
        component: 'CNavTitle',
        name: 'Master Data Transaksi',
      },
    {
      component: 'CNavGroup',
      name: 'Master Data Transaksi',
      to: '/base',
      icon: 'cil-folder',
      items: [
        // {
        //   component: 'CNavItem',
        //   name: 'Data Perusahaan',
        //   to: '/base/company',
        // },
        {
          component: 'CNavItem',
          name: 'Pencatatan Pemasukan',
          to: '/base/pemasukan',
        },
        {
            component: 'CNavItem',
            name: 'Pencatatan invoice',
            to: '/base/invoice',
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
        {
            component: 'CNavItem',
            name: 'Termin',
            to: '/base/termin/termin',
          },
      ],
    },
    // kolom master data penjualan
    {
        component: 'CNavTitle',
        name: 'Master Data Pembelian',
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
