<script setup>
import { onMounted, ref } from 'vue'
import { useColorModes } from '@coreui/vue'
import axios from 'axios'
import dayjs from 'dayjs'

import AppBreadcrumb from '@/components/dashboard/AppBreadcrumb.vue'
import AppHeaderDropdownAccnt from '@/components/dashboard/AppHeaderDropdownAccnt.vue'
import { useSidebarStore } from '@/stores/sidebar.js'

const headerClassNames = ref('mb-4 p-0')
const { colorMode, setColorMode } = useColorModes('coreui-free-vue-admin-template-theme')
const sidebar = useSidebarStore()

const recentActivities = ref([])
const formatTime = (datetime) => dayjs(datetime).format('DD MMM [pukul] HH:mm')

const fetchRecentActivities = async () => {
  try {
    const token = sessionStorage.getItem('token')
    const res = await axios.get('/api/activity-log?limit=5', {
      headers: { Authorization: `Bearer ${token}` }
    })
    recentActivities.value = res.data.data || res.data
  } catch (error) {
    console.error('Error fetching recent activities:', error)
  }
}

onMounted(() => {
  document.addEventListener('scroll', () => {
    if (document.documentElement.scrollTop > 0) {
      headerClassNames.value = 'mb-4 p-0 shadow-sm'
    } else {
      headerClassNames.value = 'mb-4 p-0'
    }
  })
  fetchRecentActivities()
})
</script>

<template>
  <CHeader position="sticky" :class="headerClassNames">
    <CContainer class="border-bottom px-4" fluid>
      <CHeaderToggler @click="sidebar.toggleVisible()" style="margin-inline-start: -14px">
        <CIcon icon="cil-menu" size="lg" />
      </CHeaderToggler>
      <CHeaderNav class="ms-auto">
        <CNavItem>
          <CDropdown placement="bottom-end" variant="nav-item">
            <CDropdownToggle :caret="false">
              <CIcon icon="cil-bell" size="lg" />
              <CBadge
                v-if="recentActivities.length > 0"
                color="danger"
                class="position-absolute top-0 end-0 translate-middle-y rounded-pill"
                style="font-size: 0.5em; padding: 0.25em 0.4em;"
              >
                {{ recentActivities.length }}
              </CBadge>
            </CDropdownToggle>
            <CDropdownMenu class="pt-0" style="width: 300px">
              <CDropdownHeader
                component="h6"
                class="bg-light fw-semibold py-2 px-3"
              >
                Activity Log Terbaru
              </CDropdownHeader>
              <div style="max-height: 300px; overflow-y: auto;">
                <CDropdownItem
                  v-for="activity in recentActivities"
                  :key="activity.id"
                  class="d-flex flex-column px-3 py-2"
                >
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-truncate">
                      <b>{{ activity.user?.name || 'System' }}</b>
                      {{ activity.action ? 'melakukan ' + activity.action : activity.activity }}
                    </small>
                    <small class="text-muted ms-2">{{ formatTime(activity.created_at) }}</small>
                  </div>
                </CDropdownItem>
                <CDropdownItem v-if="recentActivities.length === 0" class="text-center text-muted py-2">
                  Belum ada aktivitas
                </CDropdownItem>
              </div>
              <CDropdownDivider />
              <CDropdownItem class="text-center" @click="$router.push('/activitylog')">
                Lihat Semua Aktivitas
              </CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </CNavItem>
      </CHeaderNav>
      <CHeaderNav>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <CNavItem>
          <CButton
            color="link"
            class="px-2"
            @click="setColorMode(colorMode === 'dark' ? 'light' : 'dark')"
          >
            <CIcon
              :icon="colorMode === 'dark' ? 'cil-sun' : 'cil-moon'"
              size="lg"
              :class="{ 'text-warning': colorMode === 'dark' }"
            />
          </CButton>
        </CNavItem>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <AppHeaderDropdownAccnt />
      </CHeaderNav>
    </CContainer>
    <CContainer class="px-4" fluid>
      <AppBreadcrumb />
    </CContainer>
  </CHeader>
</template>
