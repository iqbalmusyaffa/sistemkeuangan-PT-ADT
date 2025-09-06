<script setup>
import { onMounted, ref } from 'vue'
import { CWidgetStatsA, CRow, CCol, CCard, CCardBody } from '@coreui/vue'
import { getStyle } from '@coreui/utils'
import axios from 'axios'
import { CChart } from '@coreui/vue-chartjs'

const jumlahUser = ref(0)
const totalIncome = ref(0)
const totalExpense = ref(0)
const jumlahMetodePembayaran = ref(0)
const jumlahTermin = ref(0)
const jumlahPiutang = ref(0)
const jumlahKasbon = ref(0)
const jumlahInvoice = ref(0)

// Chart data bulanan, default dummy
const chartUsers = ref(Array(12).fill(0))
const chartIncome = ref(Array(12).fill(0))
const chartExpense = ref(Array(12).fill(0))
const chartMetode = ref(Array(12).fill(0))
const chartTermin = ref(Array(12).fill(0))
const chartPiutang = ref(Array(12).fill(0))
const chartKasbon = ref(Array(12).fill(0))
const chartInvoice = ref(Array(12).fill(0))

const widgetChartRef1 = ref()
const widgetChartRef2 = ref()
const widgetChartRef3 = ref()

function formatRupiah(val) {
  return 'Rp ' + (parseInt(val, 10) || 0).toLocaleString('id-ID')
}

onMounted(async () => {
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/dashboard/summary', {
    headers: { Authorization: `Bearer ${token}` }
  })
  jumlahUser.value = res.data.user_count
  totalIncome.value = res.data.total_income
  totalExpense.value = res.data.total_expense
  jumlahMetodePembayaran.value = res.data.payment_method_count
  jumlahTermin.value = res.data.termin_count
  jumlahPiutang.value = res.data.piutang_count
  jumlahKasbon.value = res.data.kasbon_count
  jumlahInvoice.value = res.data.invoice_count
  // Ambil data bulanan dari API jika ada
  if (res.data.users_per_bulan) chartUsers.value = res.data.users_per_bulan
  if (res.data.income_per_bulan) chartIncome.value = res.data.income_per_bulan
  if (res.data.expense_per_bulan) chartExpense.value = res.data.expense_per_bulan
  // chartMetode, chartTermin, chartPiutang, chartKasbon, chartInvoice tetap dummy

  document.documentElement.addEventListener('ColorSchemeChange', () => {
    if (widgetChartRef1.value) {
      widgetChartRef1.value.chart.data.datasets[0].pointBackgroundColor = getStyle('--cui-primary')
      widgetChartRef1.value.chart.update()
    }
    if (widgetChartRef2.value) {
      widgetChartRef2.value.chart.data.datasets[0].pointBackgroundColor = getStyle('--cui-info')
      widgetChartRef2.value.chart.update()
    }
    if (widgetChartRef3.value) {
      widgetChartRef3.value.chart.data.datasets[0].pointBackgroundColor = getStyle('--cui-danger')
      widgetChartRef3.value.chart.update()
    }
  })
})
</script>

<template>
  <CRow :xs="{ gutter: 4 }" class="mb-4">
    <CCol :sm="4">
      <CCard>
        <CCardBody>
          <div class="fs-2 fw-bold">{{ jumlahUser }}</div>
          <div class="text-body-secondary">Users</div>
          <CChart
            type="line"
            :data="{ labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], datasets: [{ label: 'Users', backgroundColor: 'rgba(0,123,255,0.1)', borderColor: '#321fdb', data: chartUsers, fill: true, tension: 0.4 }] }"
            :options="{ plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, elements: { point: { radius: 0 } } }"
            style="height: 60px"
          />
        </CCardBody>
      </CCard>
    </CCol>
    <CCol :sm="4">
      <CCard>
        <CCardBody>
          <div class="fs-2 fw-bold">{{ formatRupiah(totalIncome) }}</div>
          <div class="text-body-secondary">Income</div>
          <CChart
            type="line"
            :data="{ labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], datasets: [{ label: 'Income', backgroundColor: 'rgba(0,123,255,0.1)', borderColor: '#39f', data: chartIncome, fill: true, tension: 0.4 }] }"
            :options="{ plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, elements: { point: { radius: 0 } } }"
            style="height: 60px"
          />
        </CCardBody>
      </CCard>
    </CCol>
    <CCol :sm="4">
      <CCard>
        <CCardBody>
          <div class="fs-2 fw-bold">{{ formatRupiah(totalExpense) }}</div>
          <div class="text-body-secondary">Pengeluaran</div>
          <CChart
            type="line"
            :data="{ labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], datasets: [{ label: 'Pengeluaran', backgroundColor: 'rgba(255,0,0,0.1)', borderColor: '#e55353', data: chartExpense, fill: true, tension: 0.4 }] }"
            :options="{ plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, elements: { point: { radius: 0 } } }"
            style="height: 60px"
          />
        </CCardBody>
      </CCard>
    </CCol>
    <CCol :sm="4">
      <CCard>
        <CCardBody>
          <div class="fs-2 fw-bold">{{ jumlahMetodePembayaran }}</div>
          <div class="text-body-secondary">Metode Pembayaran</div>
          <CChart
            type="line"
            :data="{ labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], datasets: [{ label: 'Metode Pembayaran', backgroundColor: 'rgba(40,167,69,0.1)', borderColor: '#2eb85c', data: chartMetode, fill: true, tension: 0.4 }] }"
            :options="{ plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, elements: { point: { radius: 0 } } }"
            style="height: 60px"
          />
        </CCardBody>
      </CCard>
    </CCol>
    <CCol :sm="4">
      <CCard>
        <CCardBody>
          <div class="fs-2 fw-bold">{{ jumlahTermin }}</div>
          <div class="text-body-secondary">Termin</div>
          <CChart
            type="line"
            :data="{ labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], datasets: [{ label: 'Termin', backgroundColor: 'rgba(255,193,7,0.1)', borderColor: '#f9b115', data: chartTermin, fill: true, tension: 0.4 }] }"
            :options="{ plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, elements: { point: { radius: 0 } } }"
            style="height: 60px"
          />
        </CCardBody>
      </CCard>
    </CCol>
    <CCol :sm="4">
      <CCard>
        <CCardBody>
          <div class="fs-2 fw-bold">{{ jumlahInvoice }}</div>
          <div class="text-body-secondary">Invoice</div>
          <CChart
            type="line"
            :data="{ labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], datasets: [{ label: 'Invoice', backgroundColor: 'rgba(50,31,219,0.1)', borderColor: '#321fdb', data: chartInvoice, fill: true, tension: 0.4 }] }"
            :options="{ plugins: { legend: { display: false } }, scales: { x: { display: false }, y: { display: false } }, elements: { point: { radius: 0 } } }"
            style="height: 60px"
          />
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>
