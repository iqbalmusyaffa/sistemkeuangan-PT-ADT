<script setup>
import { onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import { CContainer } from '@coreui/vue'
import AppFooter from '@/components/dashboard/AppFooter.vue'
import AppHeader from '@/components/dashboard/AppHeader.vue'
import AppSidebar from '@/components/dashboard/AppSidebar.vue'

const auth = useAuthStore()
const router = useRouter()

let checkInterval = null

onMounted(() => {
  // Cek setiap 10 detik apakah token masih berlaku
  checkInterval = setInterval(() => {
    const stillValid = auth.checkTokenExpiry()
    if (!stillValid) {
      router.push('/login')
    }
  }, 10 * 1000) // setiap 10 detik
})

onBeforeUnmount(() => {
  if (checkInterval) clearInterval(checkInterval)
})
</script>

<template>
  <div>
    <AppSidebar />
    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />
      <div class="body flex-grow-1">
        <CContainer class="px-4" lg>
          <router-view />
        </CContainer>
      </div>
      <AppFooter />
    </div>
  </div>
</template>
