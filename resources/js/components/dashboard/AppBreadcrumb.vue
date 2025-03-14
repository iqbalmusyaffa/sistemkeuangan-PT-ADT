<script setup>
import { onMounted, ref, computed } from "vue";
import router from "@/routes/route";
import { CBreadcrumb, CBreadcrumbItem } from "@coreui/vue";
import { useAuthStore } from "@/stores/auth"; // Import store auth

const breadcrumbs = ref([]);
const auth = useAuthStore(); // mengunakan store auth

const getBreadcrumbs = () => {
  const crumbs = router.currentRoute.value.matched.map((route) => ({
    active: route.path === router.currentRoute.value.fullPath,
    name: route.name,
    path: `${router.options.history.base}${route.path}`,
  }));

  // Tambahkan nama pengguna di breadcrumb
  if (auth.user) {
    crumbs.unshift({
      active: false,
      name: `👤 ${auth.user.name}`,
      path: "/profile",
    });
  }

  return crumbs;
};

router.afterEach(() => {
  breadcrumbs.value = getBreadcrumbs();
});

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
