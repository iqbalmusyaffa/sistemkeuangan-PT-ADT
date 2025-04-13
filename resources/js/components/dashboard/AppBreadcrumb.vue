<script setup>
import { onMounted, ref, computed } from "vue";
import router from "@/routes/route";
import { CBreadcrumb, CBreadcrumbItem } from "@coreui/vue";
import { useAuthStore } from "@/stores/auth"; // Import store auth

const breadcrumbs = ref([]);
const auth = useAuthStore(); // Menggunakan store auth

// Fungsi untuk mendapatkan breadcrumb
const getBreadcrumbs = () => {
  const crumbs = router.currentRoute.value.matched.map((route) => ({
    active: route.path === router.currentRoute.value.fullPath,
    name: route.name,
    path: `${router.options.history.base}${route.path}`,
  }));

  // Tambahkan nama pengguna di breadcrumb jika user terautentikasi
  if (auth.user) {
    crumbs.unshift({
      active: false,
      name: `👤 ${auth.user.name}`,
      path: "/profile",
    });
  }

  return crumbs;
};

// Update breadcrumb setelah rute berubah
router.afterEach(() => {
  breadcrumbs.value = getBreadcrumbs();
});

// Ambil data user saat komponen dipasang
onMounted(async () => {
  await auth.fetchUser(); // Ambil data user saat komponen dipasang
  breadcrumbs.value = getBreadcrumbs();
});
</script>

<template>
  <CBreadcrumb class="my-0">
    <CBreadcrumbItem
      v-for="item in breadcrumbs"
      :key="item.path"
      :href="item.active ? '' : item.path"
      :active="item.active"
    >
      {{ item.name }}
    </CBreadcrumbItem>
  </CBreadcrumb>
</template>
