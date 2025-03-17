<script setup>
import { useRouter } from 'vue-router'
import axios from 'axios'
import avatar from '@/assets/images/avatars/8.jpg'

const router = useRouter()
const itemsCount = 42
// Navigasi ke halaman profil
const goToProfile = () => {
  router.push('/profile')
}
const logout = async () => {
  try {
    const token = localStorage.getItem('token')

    if (token) {
      await axios.post('http://localhost:8000/api/logout', {}, {
        headers: { Authorization: `Bearer ${token}` },
        withCredentials: true,
      })
    }

    localStorage.removeItem('token')
    router.push('/login')
  } catch (error) {
    console.error('Logout gagal:', error)
    localStorage.removeItem('token')
    router.push('/login')
  }
}
</script>

<template>
  <CDropdown placement="bottom-end" variant="nav-item">
    <CDropdownToggle class="py-0 pe-0" :caret="false">
      <CAvatar :src="avatar" size="md" />
    </CDropdownToggle>
    <CDropdownMenu class="pt-0">
      <CDropdownHeader
        component="h6"
        class="bg-body-secondary text-body-secondary fw-semibold my-2"
      >
        Settings
      </CDropdownHeader>
      <CDropdownItem @click="goToProfile">
        <CIcon icon="cil-user" /> Profile
      </CDropdownItem>
      <CDropdownItem> <CIcon icon="cil-settings" /> Settings </CDropdownItem>
      <CDropdownDivider />
      <CDropdownItem @click="logout">
        <CIcon icon="cil-lock-locked" /> Logout
      </CDropdownItem>
    </CDropdownMenu>
  </CDropdown>
</template>
