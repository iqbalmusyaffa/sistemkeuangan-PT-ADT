<script setup>
import avatar1 from '@/assets/images/avatars/1.jpg'
import avatar2 from '@/assets/images/avatars/2.jpg'
import avatar3 from '@/assets/images/avatars/3.jpg'
import avatar4 from '@/assets/images/avatars/4.jpg'
import avatar5 from '@/assets/images/avatars/5.jpg'
import avatar6 from '@/assets/images/avatars/6.jpg'
import MainChart from './MainChart.vue'
import WidgetsStatsA from './../widgets/WidgetsStatsTypeA.vue'
import WidgetsStatsD from './../widgets/WidgetsStatsTypeD.vue'
import { ref, onMounted } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { CWidgetStatsA } from '@coreui/vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const progressGroupExample1 = [
  { title: 'Monday', value1: 34, value2: 78 },
  { title: 'Tuesday', value1: 56, value2: 94 },
  { title: 'Wednesday', value1: 12, value2: 67 },
  { title: 'Thursday', value1: 43, value2: 91 },
  { title: 'Friday', value1: 22, value2: 73 },
  { title: 'Saturday', value1: 53, value2: 82 },
  { title: 'Sunday', value1: 9, value2: 69 },
]
const progressGroupExample2 = [
  { title: 'Male', icon: ['fas', 'male'], value: 53 },
  { title: 'Female', icon: ['fas', 'female'], value: 43 },
]

const progressGroupExample3 = [
  {
    title: 'Organic Search',
    icon: ['fab', 'google'],
    percent: 56,
    value: '191,235',
  },
  { title: 'Facebook', icon: ['fab', 'facebook'], percent: 15, value: '51,223' },
  { title: 'Twitter', icon: ['fab', 'twitter'], percent: 11, value: '37,564' },
  { title: 'LinkedIn', icon: ['fab', 'linkedin'], percent: 8, value: '27,319' },
]
const tableExample = [
  {
    avatar: { src: avatar1, status: 'success' },
    user: {
      name: 'Yiorgos Avraamu',
      new: true,
      registered: 'Jan 1, 2023',
    },
    country: { name: 'USA', flag: ['fas', 'flag'] },
    usage: {
      value: 50,
      period: 'Jun 11, 2023 - Jul 10, 2023',
      color: 'success',
    },
    payment: { name: 'Mastercard', icon: ['fab', 'cc-mastercard'] },
    activity: '10 sec ago',
  },
  {
    avatar: { src: avatar2, status: 'danger' },
    user: {
      name: 'Avram Tarasios',
      new: false,
      registered: 'Jan 1, 2023',
    },
    country: { name: 'Brazil', flag: ['fas', 'flag'] },
    usage: {
      value: 22,
      period: 'Jun 11, 2023 - Jul 10, 2023',
      color: 'info',
    },
    payment: { name: 'Visa', icon: ['fab', 'cc-visa'] },
    activity: '5 minutes ago',
  },
  {
    avatar: { src: avatar3, status: 'warning' },
    user: { name: 'Quintin Ed', new: true, registered: 'Jan 1, 2023' },
    country: { name: 'India', flag: ['fas', 'flag'] },
    usage: {
      value: 74,
      period: 'Jun 11, 2023 - Jul 10, 2023',
      color: 'warning',
    },
    payment: { name: 'Stripe', icon: ['fab', 'cc-stripe'] },
    activity: '1 hour ago',
  },
  {
    avatar: { src: avatar4, status: 'secondary' },
    user: { name: 'Enéas Kwadwo', new: true, registered: 'Jan 1, 2023' },
    country: { name: 'France', flag: ['fas', 'flag-france'] },
    usage: {
      value: 98,
      period: 'Jun 11, 2023 - Jul 10, 2023',
      color: 'danger',
    },
    payment: { name: 'PayPal', icon: ['fab', 'cc-paypal'] },
    activity: 'Last month',
  },
  {
    avatar: { src: avatar5, status: 'success' },
    user: {
      name: 'Agapetus Tadeáš',
      new: true,
      registered: 'Jan 1, 2023',
    },
    country: { name: 'Spain', flag: ['fas', 'flag-spain'] },
    usage: {
      value: 22,
      period: 'Jun 11, 2023 - Jul 10, 2023',
      color: 'primary',
    },
    payment: { name: 'Google Wallet', icon: ['fab', 'google-wallet'] },
    activity: 'Last week',
  },
  {
    avatar: { src: avatar6, status: 'danger' },
    user: {
      name: 'Friderik Dávid',
      new: true,
      registered: 'Jan 1, 2023',
    },
    country: { name: 'Poland', flag: ['fas', 'flag-poland'] },
    usage: {
      value: 43,
      period: 'Jun 11, 2023 - Jul 10, 2023',
      color: 'success',
    },
    payment: { name: 'Amex', icon: ['fab', 'cc-amex'] },
    activity: 'Last week',
  },
]

const activityLogs = ref([])
const formatTime = (datetime) => dayjs(datetime).format('DD MMM YYYY [pukul] HH:mm')

const fetchActivityLogs = async () => {
  const token = sessionStorage.getItem('token')
  try {
    const res = await axios.get('/api/activity-log', {
      headers: { Authorization: `Bearer ${token}` }
    })
    activityLogs.value = res.data.data || res.data // Handle both paginated and non-paginated responses
  } catch (error) {
    console.error('Error fetching activity logs:', error)
  }
}


const jumlahUser = ref(0)
const totalIncome = ref(0)
const totalExpense = ref(0)
const totalExpenseRemaining = ref(0)
const totalPPN = ref(0)
const totalPPhFinal = ref(0)
const totalPPhNonFinal = ref(0)
const grandTotalExpense = ref(0)
const jumlahMetodePembayaran = ref(0)
const jumlahTermin = ref(0)
const jumlahPiutang = ref(0)
const jumlahKasbon = ref(0)
const jumlahInvoice = ref(0)
const chartUsers = ref(Array(12).fill(0))
const chartIncome = ref(Array(12).fill(0))
const chartExpense = ref(Array(12).fill(0))
const netIncome = ref(0)

onMounted(async () => {
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/dashboard/summary', {
    headers: { Authorization: `Bearer ${token}` }
  })
  jumlahUser.value = res.data.user_count
  totalIncome.value = res.data.total_income
  totalExpense.value = res.data.total_expense
  totalExpenseRemaining.value = res.data.total_expense_remaining || 0
  totalPPN.value = res.data.total_ppn || 0
  totalPPhFinal.value = res.data.total_pph_final || 0
  totalPPhNonFinal.value = res.data.total_pph_non_final || 0
  grandTotalExpense.value = res.data.grand_total_expense || 0
  jumlahMetodePembayaran.value = res.data.payment_method_count
  jumlahTermin.value = res.data.termin_count
  jumlahPiutang.value = res.data.piutang_count
  jumlahKasbon.value = res.data.kasbon_count
  jumlahInvoice.value = res.data.invoice_count
    netIncome.value = res.data.net_income

  // Jika backend sudah mengembalikan data bulanan, gunakan ini:
  chartUsers.value = res.data.users
  chartIncome.value = res.data.income
  chartExpense.value = res.data.expense
  fetchActivityLogs()
})
</script>

<template>
  <div>
    <!-- <CRow class="mb-4">
      <CCol :sm="4">
        <CCard class="mb-2 bg-danger text-white">
          <CCardBody>
            <div class="fs-4 fw-bold">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalExpense) }}</div>
            <div class="text-white-50">Total Pengeluaran (Tanpa Pajak)</div>
          </CCardBody>
        </CCard>
      </CCol>
      <CCol :sm="4">
        <CCard class="mb-2 bg-info text-dark">
          <CCardBody>
            <div class="fs-4 fw-bold">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(grandTotalExpense) }}</div>
            <div class="text-dark-50">Grand Total Pengeluaran (Termasuk Pajak)</div>
            <ul class="mb-0 mt-2 small">
              <li>PPN: <b>{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalPPN) }}</b></li>
              <li>PPh Final: <b>{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalPPhFinal) }}</b></li>
              <li>PPh Non Final: <b>{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalPPhNonFinal) }}</b></li>
            </ul>
          </CCardBody>
        </CCard>
      </CCol>
          <CCol :sm="6">
      <CCard class="mb-2 bg-success text-white">
        <CCardBody>
          <div class="fs-4 fw-bold">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalIncome) }}</div>
          <div class="text-white-50">Total Pemasukan</div>
        </CCardBody>
      </CCard>
    </CCol>
    <CCol :sm="6">
      <CCard class="mb-2 bg-primary text-white">
        <CCardBody>
          <div class="fs-4 fw-bold">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(netIncome) }}</div>
          <div class="text-white-50">Saldo Bersih (Pemasukan - Pengeluaran)</div>
        </CCardBody>
      </CCard>
    </CCol>
      <CCol :sm="4">
        <CCard class="mb-2 bg-warning text-dark">
          <CCardBody>
            <div class="fs-4 fw-bold">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(totalExpenseRemaining) }}</div>
            <div class="text-dark-50">Sisa Pengeluaran (Belum Tertutup)</div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow> -->
    <WidgetsStatsA class="mb-4" />
    <CRow>
      <CCol :md="12">
        <CCard class="mb-4">
          <CCardHeader>
            <FontAwesomeIcon :icon="['fas', 'history']" /> Activity Log
          </CCardHeader>
          <CCardBody>
            <div class="table-responsive">
              <table class="table table-striped align-middle">
                <thead>
                  <tr>
                    <th style="width: 50px;">No</th>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Waktu</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(log, idx) in activityLogs" :key="log.id">
                    <td>{{ idx + 1 }}</td>
                    <td>
                      <span v-if="log.user && log.user.name">{{ log.user.name }}</span>
                      <span v-else class="text-secondary">System</span>
                    </td>
                    <td>
                      <span v-if="log.action">
                        <b>{{ log.user?.name || 'System' }}</b>
                        melakukan <b>{{ log.action }}</b>
                        pada <b>{{ log.model_type?.split('\\').pop() }}</b>
                        ID <b>{{ log.model_id }}</b>
                      </span>
                      <span v-else>{{ log.activity }}</span>
                    </td>
                    <td>{{ formatTime(log.created_at) }}</td>
                  </tr>
                  <tr v-if="activityLogs.length === 0">
                    <td colspan="4" class="text-center text-secondary">Belum ada aktivitas.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
    <WidgetsStatsD class="mb-4" />
    <CRow>
      <CCol :md="12">
        <CCard class="mb-4">
          <CCardHeader> Traffic &amp; Sales </CCardHeader>
          <CCardBody>
            <CRow>
              <CCol :sm="12" :lg="6">
                <CRow>
                  <CCol :xs="6">
                    <div class="border-start border-start-4 border-start-info py-1 px-3 mb-3">
                      <div class="text-body-secondary small">New Clients</div>
                      <div class="fs-5 fw-semibold">9,123</div>
                    </div>
                  </CCol>
                  <CCol :xs="6">
                    <div class="border-start border-start-4 border-start-danger py-1 px-3 mb-3">
                      <div class="text-body-secondary small">Recurring Clients</div>
                      <div class="fs-5 fw-semibold">22,643</div>
                    </div>
                  </CCol>
                </CRow>
                <hr class="mt-0" />
                <div
                  v-for="item in progressGroupExample1"
                  :key="item.title"
                  class="progress-group mb-4"
                >
                  <div class="progress-group-prepend">
                    <span class="text-body-secondary small">{{ item.title }}</span>
                  </div>
                  <div class="progress-group-bars">
                    <CProgress thin color="info" :value="item.value1" />
                    <CProgress thin color="danger" :value="item.value2" />
                  </div>
                </div>
              </CCol>
              <CCol :sm="12" :lg="6">
                <CRow>
                  <CCol :xs="6">
                    <div class="border-start border-start-4 border-start-warning py-1 px-3 mb-3">
                      <div class="text-body-secondary small">Pageviews</div>
                      <div class="fs-5 fw-semibold">78,623</div>
                    </div>
                  </CCol>
                  <CCol :xs="6">
                    <div class="border-start border-start-4 border-start-success py-1 px-3 mb-3">
                      <div class="text-body-secondary small">Organic</div>
                      <div class="fs-5 fw-semibold">49,123</div>
                    </div>
                  </CCol>
                </CRow>
                <hr class="mt-0" />
                <div v-for="item in progressGroupExample2" :key="item.title" class="progress-group">
                  <div class="progress-group-header">
                    <FontAwesomeIcon :icon="item.icon" class="me-2" size="lg" />
                    <span class="title">{{ item.title }}</span>
                    <span class="ms-auto fw-semibold">{{ item.value }}%</span>
                  </div>
                  <div class="progress-group-bars">
                    <CProgress thin :value="item.value" color="warning" />
                  </div>
                </div>

                <div class="mb-5"></div>

                <div v-for="item in progressGroupExample3" :key="item.title" class="progress-group">
                  <div class="progress-group-header">
                    <FontAwesomeIcon :icon="item.icon" class="me-2" size="lg" />
                    <span class="title">{{ item.title }}</span>
                    <span class="ms-auto fw-semibold">
                      {{ item.value }}
                      <span class="text-body-secondary small">({{ item.percent }}%)</span>
                    </span>
                  </div>
                  <div class="progress-group-bars">
                    <CProgress thin :value="item.percent" color="success" />
                  </div>
                </div>
              </CCol>
            </CRow>
            <br />
            <CTable align="middle" class="mb-0 border" hover responsive>
              <CTableHead class="text-nowrap">
                <CTableRow>
                  <CTableHeaderCell class="bg-body-secondary text-center">
                    <FontAwesomeIcon :icon="['fas', 'users']" />
                  </CTableHeaderCell>
                  <CTableHeaderCell class="bg-body-secondary"> User </CTableHeaderCell>
                  <CTableHeaderCell class="bg-body-secondary text-center">
                    Country
                  </CTableHeaderCell>
                  <CTableHeaderCell class="bg-body-secondary"> Usage </CTableHeaderCell>
                  <CTableHeaderCell class="bg-body-secondary text-center">
                    Payment Method
                  </CTableHeaderCell>
                  <CTableHeaderCell class="bg-body-secondary"> Activity </CTableHeaderCell>
                </CTableRow>
              </CTableHead>
              <CTableBody>
                <CTableRow v-for="item in tableExample" :key="item.name">
                  <CTableDataCell class="text-center">
                    <CAvatar size="md" :src="item.avatar.src" :status="item.avatar.status" />
                  </CTableDataCell>
                  <CTableDataCell>
                    <div>{{ item.user.name }}</div>
                    <div class="small text-body-secondary text-nowrap">
                      <span>{{ item.user.new ? 'New' : 'Recurring' }}</span> |
                      {{ item.user.registered }}
                    </div>
                  </CTableDataCell>
                  <CTableDataCell class="text-center">
                    <FontAwesomeIcon :icon="item.country.flag" size="xl" :title="item.country.name" />
                  </CTableDataCell>
                  <CTableDataCell>
                    <div class="d-flex justify-content-between align-items-baseline">
                      <div class="fw-semibold">{{ item.usage.value }}%</div>
                      <div class="text-nowrap text-body-secondary small ms-3">
                        {{ item.usage.period }}
                      </div>
                    </div>
                    <CProgress thin :color="item.usage.color" :value="item.usage.value" />
                  </CTableDataCell>
                  <CTableDataCell class="text-center">
                    <FontAwesomeIcon :icon="item.payment.icon" size="xl" />
                  </CTableDataCell>
                  <CTableDataCell>
                    <div class="small text-body-secondary">Last login</div>
                    <div class="fw-semibold text-nowrap">
                      {{ item.activity }}
                    </div>
                  </CTableDataCell>
                </CTableRow>
              </CTableBody>
            </CTable>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </div>
</template>
