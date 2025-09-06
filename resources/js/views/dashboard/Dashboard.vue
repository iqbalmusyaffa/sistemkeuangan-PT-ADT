<script setup>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import avatar1 from '@/assets/images/avatars/1.jpg'
import avatar2 from '@/assets/images/avatars/2.jpg'
import avatar3 from '@/assets/images/avatars/3.jpg'
import avatar4 from '@/assets/images/avatars/4.jpg'
import avatar5 from '@/assets/images/avatars/5.jpg'
import avatar6 from '@/assets/images/avatars/6.jpg'
import DashboardChart from './DashboardChart.vue'
import WidgetsStatsA from './../widgets/WidgetsStatsTypeA.vue'
import WidgetsStatsD from './../widgets/WidgetsStatsTypeD.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import 'bootstrap/dist/css/bootstrap.min.css'

const selectedStatus = ref('Lunas,approved,DP Dibayar')
const selectedProyekId = ref('')
const selectedKategori = ref('semua')
const proyekList = ref([])

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
const jumlahInvoice = ref(0)
const jumlahPiutang = ref(0)
const jumlahKasbon = ref(0)
const netIncome = ref(0)

const chartUsers = ref(Array(12).fill(0))
const chartIncome = ref(Array(12).fill(0))
const chartExpense = ref(Array(12).fill(0))
const activityLogs = ref([])

const bulanLabel = [
  'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
  'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
]

const formatTime = (datetime) => dayjs(datetime).format('DD MMM YYYY [pukul] HH:mm')

const fetchSummaryData = async () => {
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/dashboard/summary', {
    headers: { Authorization: `Bearer ${token}` },
    params: {
      status: selectedStatus.value,
      proyek_id: selectedProyekId.value,
      kategori: selectedKategori.value,
    }
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
  jumlahInvoice.value = res.data.invoice_count
  netIncome.value = res.data.net_income
  chartUsers.value = res.data.users
  chartIncome.value = res.data.income
  chartExpense.value = res.data.expense
}

const fetchActivityLogs = async () => {
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/activity-log', {
    headers: { Authorization: `Bearer ${token}` },
    params: {
      status: selectedStatus.value,
      proyek_id: selectedProyekId.value,
      kategori: selectedKategori.value,
    }
  })
  activityLogs.value = res.data.data || res.data
}

const fetchProyekList = async () => {
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/proyeks', {
    headers: { Authorization: `Bearer ${token}` }
  })
  proyekList.value = res.data?.data || res.data
}

const actionColor = (action) => {
  if (!action) return 'secondary'
  switch (action.toLowerCase()) {
    case 'create': return 'success'
    case 'update': return 'warning'
    case 'delete': return 'danger'
    default: return 'secondary'
  }
}

const modelName = (modelType) => modelType?.split('\\').pop() || '-'

// HANYA panggil ini satu kali saat mount
onMounted(async () => {
  await fetchProyekList()
  await fetchSummaryData()
  await fetchActivityLogs()
})

// AKTIF untuk filter realtime
watch([selectedStatus, selectedProyekId, selectedKategori], async () => {
  await fetchSummaryData()
  await fetchActivityLogs()
})
</script>

<template>
  <div>
    <WidgetsStatsA class="mb-4" />
    <CRow>
      <CCol :md="12">
        <CCard class="mb-4">
          <CCardHeader>
            <FontAwesomeIcon :icon="['fas', 'history']" /> Activity Log
          </CCardHeader>
          <CCardBody>
            <div class="table-responsive">
  <table class="table table-hover align-middle w-100" style="min-width: max-content;">
    <thead class="table-light">
      <tr>
        <th style="width: 40px;">#</th>
        <th><font-awesome-icon icon="user" class="me-1" /> User</th>
        <th><font-awesome-icon icon="clipboard-list" class="me-1" /> Aktivitas</th>
        <th><font-awesome-icon icon="clock" class="me-1" /> Waktu</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="(log, idx) in activityLogs" :key="log.id">
        <td>{{ idx + 1 }}</td>
        <td>
          <span class="fw-semibold">
            {{ log.user?.name || 'System' }}
          </span>
        </td>
        <td>
          <CBadge :color="actionColor(log.action)" class="me-2 text-uppercase">
            {{ log.action }}
          </CBadge>
          <span v-if="log.model_type">
            <b>{{ modelName(log.model_type) }}</b> ID <b>{{ log.model_id }}</b>
          </span>
        </td>
        <td>
          <font-awesome-icon icon="clock" class="me-1 text-muted" />
          {{ formatTime(log.created_at) }}
        </td>
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
    <CCard class="mb-4">
  <CCardHeader>
    <font-awesome-icon icon="chart-bar" class="me-2" />
    Grafik Income & Expense
  </CCardHeader>
             <CRow class="mb-3">
  <CCol :md="4">
    <label class="form-label fw-semibold">Filter Status</label>
    <CFormSelect v-model="selectedStatus">
      <option value="Lunas,approved,DP Dibayar">Lunas + Approved + DP Dibayar</option>
      <option value="Lunas">Lunas</option>
      <option value="DP Dibayar">DP Dibayar</option>
    </CFormSelect>
  </CCol>
  <CCol :md="4">
    <label class="form-label fw-semibold">Filter Proyek</label>
    <CFormSelect v-model="selectedProyekId">
      <option value="">Semua Proyek</option>
      <option v-for="proyek in proyekList" :key="proyek.id" :value="proyek.id">
        {{ proyek.nama_proyek }}
      </option>
    </CFormSelect>
  </CCol>
  <CCol :md="4">
    <label class="form-label fw-semibold">Kategori Data</label>
    <CFormSelect v-model="selectedKategori">
      <option value="semua">Semua</option>
      <option value="income">Income</option>
      <option value="invoice">Invoice</option>
      <option value="expense">Pengeluaran</option>
    </CFormSelect>
  </CCol>
</CRow>
  <CCardBody>
    <DashboardChart
      :labels="bulanLabel"
      :income-data="chartIncome"
      :expense-data="chartExpense"
    />
  </CCardBody>
</CCard>

    <WidgetsStatsD class="mb-4" />
  </div>
</template>
