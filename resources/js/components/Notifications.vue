<template>
  <div class="notifications-container">
    <CDropdown variant="nav-item">
      <CDropdownToggle placement="bottom-end" class="py-0" :caret="false">
        <CIcon icon="cil-bell" size="lg" />
        <CBadge color="danger" class="position-absolute top-0 end-0" v-if="unreadCount > 0">
          {{ unreadCount }}
        </CBadge>
      </CDropdownToggle>
      <CDropdownMenu class="pt-0">
        <CDropdownHeader class="bg-light">
          <strong>Notifikasi</strong>
        </CDropdownHeader>
        <div v-if="notifications.length === 0" class="text-center p-3">
          Tidak ada notifikasi
        </div>
        <div v-else class="notifications-list" style="max-height: 300px; overflow-y: auto;">
          <div v-for="notification in notifications" :key="notification.id" 
               class="notification-item p-2 border-bottom" 
               :class="{ 'unread': !notification.read_at }"
               @click="markAsRead(notification)">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <div class="notification-title">{{ notification.data.message }}</div>
                <div class="notification-time text-muted small">
                  {{ formatDate(notification.created_at) }}
                </div>
              </div>
              <div v-if="!notification.read_at" class="ms-2">
                <span class="badge bg-primary">Baru</span>
              </div>
            </div>
          </div>
        </div>
        <CDropdownDivider />
        <CDropdownItem @click="markAllAsRead" v-if="unreadCount > 0">
          Tandai semua sebagai telah dibaca
        </CDropdownItem>
      </CDropdownMenu>
    </CDropdown>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'Notifications',
  setup() {
    const notifications = ref([])
    const unreadCount = ref(0)

    const fetchNotifications = async () => {
      try {
        const response = await axios.get('/api/notifications')
        notifications.value = response.data
        unreadCount.value = notifications.value.filter(n => !n.read_at).length
      } catch (error) {
        console.error('Error fetching notifications:', error)
      }
    }

    const markAsRead = async (notification) => {
      try {
        await axios.post(`/api/notifications/${notification.id}/mark-as-read`)
        notification.read_at = new Date().toISOString()
        unreadCount.value--
      } catch (error) {
        console.error('Error marking notification as read:', error)
      }
    }

    const markAllAsRead = async () => {
      try {
        await axios.post('/api/notifications/mark-all-as-read')
        notifications.value.forEach(notification => {
          notification.read_at = new Date().toISOString()
        })
        unreadCount.value = 0
      } catch (error) {
        console.error('Error marking all notifications as read:', error)
      }
    }

    const formatDate = (date) => {
      return new Date(date).toLocaleString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    onMounted(() => {
      fetchNotifications()
      // Poll for new notifications every minute
      setInterval(fetchNotifications, 60000)
    })

    return {
      notifications,
      unreadCount,
      markAsRead,
      markAllAsRead,
      formatDate
    }
  }
}
</script>

<style scoped>
.notifications-container {
  position: relative;
}

.notification-item {
  cursor: pointer;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: #f8f9fa;
}

.notification-item.unread {
  background-color: #e8f4ff;
}

.notification-title {
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.notification-time {
  font-size: 0.8rem;
}

.notifications-list {
  width: 300px;
}
</style> 