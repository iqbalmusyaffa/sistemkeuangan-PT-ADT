<template>
  <div class="settings-wrapper py-3 px-2 px-md-4">
    <CCard class="settings-card shadow-sm">
      <CCardHeader class="bg-light border-bottom">
        <CIcon icon="cil-settings" /> <span class="ms-2 fw-semibold">Settings</span>
      </CCardHeader>
      <CCardBody>
        <div v-if="isLoading" class="text-center py-4">
          <CSpinner />
        </div>
        <div v-else-if="error" class="alert alert-danger">
          {{ error }}
        </div>
        <CTabs v-else class="settings-tab">
          <!-- Profile Settings -->
          <CTab title="Profile" active>
            <CCard class="mb-3 border-0 bg-transparent">
              <CCardBody>
                <CRow class="justify-content-center align-items-center g-4 flex-column flex-md-row">
                  <CCol xs="12" md="4" class="d-flex flex-column align-items-center justify-content-center mb-3 mb-md-0">
                    <CAvatar
                      :src="userProfile?.avatar || 'https://ui-avatars.com/api/?name=User&background=6c63ff&color=fff'"
                      size="xxl"
                      class="mb-3 shadow"
                      style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #e4e6ef;"
                    />
                    <CButton color="primary" size="sm" class="mt-2 px-4 w-100 w-md-auto">
                      Change Photo
                    </CButton>
                  </CCol>
                  <CCol xs="12" md="8" class="d-flex flex-column align-items-center align-items-md-start">
                    <CForm @submit.prevent="saveProfile" class="w-100" style="max-width: 400px;">
                      <CFormLabel class="fw-semibold">Name</CFormLabel>
                      <CFormInput v-model="userProfile.name" class="mb-3" />
                      <CFormLabel class="fw-semibold">Email</CFormLabel>
                      <CFormInput type="email" v-model="userProfile.email" class="mb-3" />
                      <CFormLabel class="fw-semibold">Phone</CFormLabel>
                      <CFormInput v-model="userProfile.phone" class="mb-3" />
                      <div class="d-flex justify-content-center justify-content-md-start w-100">
                        <CButton color="primary" class="mt-2 px-4" type="submit" :disabled="isLoading">
                          <CSpinner v-if="isLoading" size="sm" class="me-2" />
                          Save Changes
                        </CButton>
                      </div>
                    </CForm>
                  </CCol>
                </CRow>
              </CCardBody>
            </CCard>
          </CTab>

          <!-- Application Settings -->
          <CTab title="Application">
            <CCard class="mb-3 border-0 bg-transparent">
              <CCardBody>
                <CForm @submit.prevent="saveAppSettings">
                  <CRow class="g-3 justify-content-center">
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Theme</CFormLabel>
                      <CFormSelect v-model="appSettings.theme" class="mb-2">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="system">System Default</option>
                      </CFormSelect>
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Language</CFormLabel>
                      <CFormSelect v-model="appSettings.language" class="mb-2">
                        <option value="id">Indonesia</option>
                        <option value="en">English</option>
                      </CFormSelect>
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Timezone</CFormLabel>
                      <CFormSelect v-model="appSettings.timezone" class="mb-2">
                        <option value="Asia/Jakarta">Jakarta (GMT+7)</option>
                        <option value="Asia/Singapore">Singapore (GMT+8)</option>
                      </CFormSelect>
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Date Format</CFormLabel>
                      <CFormSelect v-model="appSettings.dateFormat" class="mb-2">
                        <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                        <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                        <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                      </CFormSelect>
                    </CCol>
                  </CRow>
                  <div class="d-flex justify-content-center justify-content-md-end mt-3">
                    <CButton color="primary" type="submit" class="px-4" :disabled="isLoading">
                      <CSpinner v-if="isLoading" size="sm" class="me-2" />
                      Save Preferences
                    </CButton>
                  </div>
                </CForm>
              </CCardBody>
            </CCard>
          </CTab>

          <!-- Notification Settings -->
          <CTab title="Notifications">
            <CCard class="mb-3 border-0 bg-transparent">
              <CCardBody>
                <CForm @submit.prevent="saveNotificationSettings">
                  <CRow class="g-3 justify-content-center">
                    <CCol xs="12" md="6">
                      <CFormCheck
                        id="emailNotifications"
                        label="Email Notifications"
                        v-model="notificationSettings.email"
                        class="mb-2"
                      />
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormCheck
                        id="inAppNotifications"
                        label="In-App Notifications"
                        v-model="notificationSettings.inApp"
                        class="mb-2"
                      />
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Notification Frequency</CFormLabel>
                      <CFormSelect v-model="notificationSettings.frequency" class="mb-2">
                        <option value="realtime">Real-time</option>
                        <option value="daily">Daily Digest</option>
                        <option value="weekly">Weekly Summary</option>
                      </CFormSelect>
                    </CCol>
                  </CRow>
                  <div class="d-flex justify-content-center justify-content-md-end mt-3">
                    <CButton color="primary" type="submit" class="px-4" :disabled="isLoading">
                      <CSpinner v-if="isLoading" size="sm" class="me-2" />
                      Save Notification Settings
                    </CButton>
                  </div>
                </CForm>
              </CCardBody>
            </CCard>
          </CTab>

          <!-- Security Settings -->
          <CTab title="Security">
            <CCard class="mb-3 border-0 bg-transparent">
              <CCardBody>
                <CForm @submit.prevent="saveSecuritySettings">
                  <CRow class="g-3 justify-content-center">
                    <CCol xs="12">
                      <CFormCheck
                        id="twoFactor"
                        label="Enable Two-Factor Authentication"
                        v-model="securitySettings.twoFactor"
                        class="mb-2"
                      />
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Change Password</CFormLabel>
                      <CFormInput type="password" placeholder="Current Password" class="mb-2" />
                      <CFormInput type="password" placeholder="New Password" class="mb-2" />
                      <CFormInput type="password" placeholder="Confirm New Password" class="mb-2" />
                    </CCol>
                    <CCol xs="12">
                      <CFormLabel class="fw-semibold">Active Sessions</CFormLabel>
                      <div class="table-responsive">
                        <CTable hover responsive class="mb-0">
                          <CTableHead>
                            <CTableRow>
                              <CTableHeaderCell>Device</CTableHeaderCell>
                              <CTableHeaderCell>Location</CTableHeaderCell>
                              <CTableHeaderCell>Last Active</CTableHeaderCell>
                              <CTableHeaderCell>Action</CTableHeaderCell>
                            </CTableRow>
                          </CTableHead>
                          <CTableBody>
                            <CTableRow v-for="session in securitySettings.sessions" :key="session?.id">
                              <CTableDataCell>{{ session?.device }}</CTableDataCell>
                              <CTableDataCell>{{ session?.location }}</CTableDataCell>
                              <CTableDataCell>{{ session?.lastActive }}</CTableDataCell>
                              <CTableDataCell>
                                <CButton color="danger" size="sm">
                                  Logout
                                </CButton>
                              </CTableDataCell>
                            </CTableRow>
                            <CTableRow v-if="!securitySettings.sessions?.length">
                              <CTableDataCell colspan="4" class="text-center">
                                No active sessions
                              </CTableDataCell>
                            </CTableRow>
                          </CTableBody>
                        </CTable>
                      </div>
                    </CCol>
                  </CRow>
                  <div class="d-flex justify-content-center justify-content-md-end mt-3">
                    <CButton color="primary" type="submit" class="px-4" :disabled="isLoading">
                      <CSpinner v-if="isLoading" size="sm" class="me-2" />
                      Save Security Settings
                    </CButton>
                  </div>
                </CForm>
              </CCardBody>
            </CCard>
          </CTab>

          <!-- Data Management -->
          <CTab title="Data Management">
            <CCard class="mb-3 border-0 bg-transparent">
              <CCardBody>
                <CForm @submit.prevent="saveDataSettings">
                  <CRow class="g-3 justify-content-center">
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Export Data</CFormLabel>
                      <div class="d-flex gap-2 flex-wrap flex-md-nowrap">
                        <CButton 
                          color="primary" 
                          @click="exportData('pdf')"
                          :disabled="isLoading"
                          class="mb-2 mb-md-0"
                        >
                          <CSpinner v-if="isLoading" size="sm" class="me-2" />
                          Export as PDF
                        </CButton>
                        <CButton 
                          color="success" 
                          @click="exportData('excel')"
                          :disabled="isLoading"
                          class="mb-2 mb-md-0"
                        >
                          <CSpinner v-if="isLoading" size="sm" class="me-2" />
                          Export as Excel
                        </CButton>
                      </div>
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Activity Log Retention</CFormLabel>
                      <CFormSelect v-model="dataSettings.logRetention" class="mb-2">
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                        <option value="180">180 days</option>
                        <option value="365">1 year</option>
                      </CFormSelect>
                    </CCol>
                    <CCol xs="12" md="6">
                      <CFormLabel class="fw-semibold">Backup Frequency</CFormLabel>
                      <CFormSelect v-model="dataSettings.backupFrequency" class="mb-2">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                      </CFormSelect>
                    </CCol>
                  </CRow>
                  <div class="d-flex justify-content-center justify-content-md-end mt-3">
                    <CButton color="primary" type="submit" class="px-4" :disabled="isLoading">
                      <CSpinner v-if="isLoading" size="sm" class="me-2" />
                      Save Data Settings
                    </CButton>
                  </div>
                </CForm>
              </CCardBody>
            </CCard>
          </CTab>
        </CTabs>
      </CCardBody>
    </CCard>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const router = useRouter()

// Profile Settings
const userProfile = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  avatar: null
})

// Application Settings
const appSettings = ref({
  theme: 'light',
  language: 'id',
  timezone: 'Asia/Jakarta',
  dateFormat: 'DD/MM/YYYY'
})

// Notification Settings
const notificationSettings = ref({
  email: true,
  inApp: true,
  frequency: 'realtime'
})

// Security Settings
const securitySettings = ref({
  twoFactor: false,
  sessions: []
})

// Data Management Settings
const dataSettings = ref({
  logRetention: '90',
  backupFrequency: 'weekly'
})

// Loading and error states
const isLoading = ref(false)
const error = ref(null)

// Methods
const exportData = async (format) => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    const response = await axios.get(`/api/export/settings/${format}`, {
      headers: { Authorization: `Bearer ${token}` },
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `settings.${format}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Error exporting data:', error)
  } finally {
    isLoading.value = false
  }
}

// Save profile changes
const saveProfile = async () => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    await axios.post('/api/settings/profile', userProfile.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
    // Show success message
  } catch (error) {
    console.error('Error saving profile:', error)
  } finally {
    isLoading.value = false
  }
}

// Save application settings
const saveAppSettings = async () => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    await axios.post('/api/settings/application', appSettings.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
    // Show success message
  } catch (error) {
    console.error('Error saving application settings:', error)
  } finally {
    isLoading.value = false
  }
}

// Save notification settings
const saveNotificationSettings = async () => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    await axios.post('/api/settings/notifications', notificationSettings.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
    // Show success message
  } catch (error) {
    console.error('Error saving notification settings:', error)
  } finally {
    isLoading.value = false
  }
}

// Save security settings
const saveSecuritySettings = async () => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    await axios.post('/api/settings/security', securitySettings.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
    // Show success message
  } catch (error) {
    console.error('Error saving security settings:', error)
  } finally {
    isLoading.value = false
  }
}

// Save data settings
const saveDataSettings = async () => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    await axios.post('/api/settings/data', dataSettings.value, {
      headers: { Authorization: `Bearer ${token}` }
    })
    // Show success message
  } catch (error) {
    console.error('Error saving data settings:', error)
  } finally {
    isLoading.value = false
  }
}

// Fetch initial data
const fetchSettings = async () => {
  try {
    isLoading.value = true
    const token = sessionStorage.getItem('token')
    const response = await axios.get('/api/settings', {
      headers: { Authorization: `Bearer ${token}` }
    })
    
    if (response.data) {
      // Update all settings with fetched data
      if (response.data.profile) {
        userProfile.value = { ...userProfile.value, ...response.data.profile }
      }
      if (response.data.application) {
        appSettings.value = { ...appSettings.value, ...response.data.application }
      }
      if (response.data.notifications) {
        notificationSettings.value = { ...notificationSettings.value, ...response.data.notifications }
      }
      if (response.data.security) {
        securitySettings.value = { ...securitySettings.value, ...response.data.security }
      }
      if (response.data.data) {
        dataSettings.value = { ...dataSettings.value, ...response.data.data }
      }
    }
  } catch (error) {
    console.error('Error fetching settings:', error)
  } finally {
    isLoading.value = false
  }
}

// Call fetchSettings when component is mounted
onMounted(() => {
  fetchSettings()
})
</script>

<style scoped>
.settings-wrapper {
  min-height: 100vh;
  background: #f8f9fa;
}
.settings-card {
  max-width: 900px;
  margin: 0 auto;
  border-radius: 1rem;
}
.settings-tab {
  padding-top: 1rem;
}
.bg-transparent {
  background: transparent !important;
}
@media (max-width: 768px) {
  .settings-card {
    padding: 0.5rem !important;
    border-radius: 0.5rem;
  }
  .settings-tab .btn {
    width: 100%;
    margin-bottom: 0.5rem;
  }
  .settings-wrapper {
    padding: 0 !important;
  }
}
</style> 