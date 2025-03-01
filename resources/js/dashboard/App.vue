<script setup>
import { onBeforeMount } from 'vue'
import { useColorModes } from '@coreui/vue'

import { useThemeStore } from '@dashboard/stores/theme.js'
import { useRouter } from 'vue-router'

// Optional: bisa kasih guard biar kalau belum login redirect ke /login
const router = useRouter()
if (!localStorage.getItem('token')) {
    router.push('/login')
}
const { isColorModeSet, setColorMode } = useColorModes(
  'coreui-free-vue-admin-template-theme',
)
const currentTheme = useThemeStore()

onBeforeMount(() => {
  const urlParams = new URLSearchParams(window.location.href.split('?')[1])
  let theme = urlParams.get('theme')

  if (theme !== null && theme.match(/^[A-Za-z0-9\s]+/)) {
    theme = theme.match(/^[A-Za-z0-9\s]+/)[0]
  }

  if (theme) {
    setColorMode(theme)
    return
  }

  if (isColorModeSet()) {
    return
  }

  setColorMode(currentTheme.theme)
})
</script>

<template>
  <router-view />
</template>

<style lang="scss">
// Import Main styles for this application
@use '/resources/js/dashboard/styles/style.scss';  // pastikan pathnya sesuai alias @dashboard
@use '@dashboard/styles/examples';
</style>
