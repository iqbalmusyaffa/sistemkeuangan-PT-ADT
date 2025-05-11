<script setup>
import { ref } from 'vue'
import { CWidgetStatsA, CRow, CCol } from '@coreui/vue'

const jumlahUser = ref(3)
const totalIncome = ref(0)
const totalExpense = ref(10002500)
const jumlahMetodePembayaran = ref(5)
const jumlahTermin = ref(2)
const jumlahPiutang = ref(1)
const jumlahKasbon = ref(2)
const jumlahInvoice = ref(4)

const chartUsers = ref([0,0,0,1,2,3,2,2,2,2,2,2])
const chartIncome = ref([0,0,0,0,0,0,0,0,0,0,0,0])
const chartExpense = ref([0,0,0,0,0,10,0,0,0,0,0,0])
</script>

<template>
  <CRow :xs="{ gutter: 4 }" class="mb-4">
    <CCol :sm="4">
      <CWidgetStatsA color="primary" :value="jumlahUser + ' Users'" title="Users" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: chartUsers }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="info" :value="'Rp ' + totalIncome + ' Income'" title="Income" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: chartIncome }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="danger" :value="'Rp ' + totalExpense + ' Pengeluaran'" title="Pengeluaran" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: chartExpense }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="success" :value="jumlahMetodePembayaran + ' Metode'" title="Metode Pembayaran" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: Array(12).fill(jumlahMetodePembayaran) }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="warning" :value="jumlahTermin + ' Termin'" title="Termin" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: Array(12).fill(jumlahTermin) }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="secondary" :value="jumlahPiutang + ' Piutang'" title="Piutang" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: Array(12).fill(jumlahPiutang) }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="dark" :value="jumlahKasbon + ' Kasbon'" title="Kasbon" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: Array(12).fill(jumlahKasbon) }] }" />
    </CCol>
    <CCol :sm="4">
      <CWidgetStatsA color="primary" :value="jumlahInvoice + ' Invoice'" title="Invoice" :chart="{ labels: Array(12).fill(''), datasets: [{ backgroundColor: 'rgba(255,255,255,.2)', borderColor: 'rgba(255,255,255,.55)', data: Array(12).fill(jumlahInvoice) }] }" />
    </CCol>
  </CRow>
</template>
