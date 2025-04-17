<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import avatar from '@/assets/images/avatars/8.jpg'

const router = useRouter()
const auth = useAuthStore()
const itemsCount = 42
// Navigasi ke halaman profil
const goToProfile = () => {
  router.push('/profile')
}
const logout = async () => {
  try {
    const token = sessionStorage.getItem('token')
    if (token) {
      await axios.post('/api/logout', {}, {
        headers: { Authorization: `Bearer ${token}` }
      })
    }
    
    // Gunakan auth store untuk logout
    auth.logout()
    
    // Redirect ke halaman login
    router.push('/login')
  } catch (error) {
    console.error('Logout gagal:', error)
    // Tetap lakukan logout client-side jika server error
    auth.logout()
    router.push('/login')
  }
}
</script>

<template>
  <CDropdown placement="bottom-end" variant="nav-item">
    <CDropdownToggle class="py-0 pe-0" :caret="false">
      <div class="d-flex align-items-center gap-2">
        <CAvatar :src="avatar" size="md" />
      </div>
    </CDropdownToggle>
    <CDropdownMenu class="pt-0" style="min-width: 200px">
      <CDropdownHeader
        component="h6"
        class="bg-light fw-semibold py-2 px-3"
      >
        Settings
      </CDropdownHeader>
      <CDropdownItem @click="goToProfile" class="d-flex align-items-center px-3 py-2">
        <CIcon icon="cil-user" class="me-2" /> Profile
      </CDropdownItem>
      <CDropdownItem class="d-flex align-items-center px-3 py-2">
        <CIcon icon="cil-settings" class="me-2" /> Settings
      </CDropdownItem>
      <CDropdownDivider class="my-1" />
      <CDropdownItem @click="logout" class="d-flex align-items-center px-3 py-2">
        <CIcon icon="cil-lock-locked" class="me-2" /> Logout
      </CDropdownItem>
    </CDropdownMenu>
  </CDropdown>
</template>

<style scoped>
:deep(.dropdown-item) {
  cursor: pointer;
}
:deep(.dropdown-item:hover) {
  background-color: #ebedef;
}
:deep(.dropdown-header) {
  background-color: #f8f9fa;
  margin-bottom: 0;
}
</style>
