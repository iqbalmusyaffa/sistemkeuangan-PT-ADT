<template>
  <div>
    <h3>Laporan Laba Rugi</h3>
    <div style="margin-bottom: 1rem;">
      <label>Pilih Bulan: <input type="month" v-model="month" @change="fetchSummary" /></label>
      <button @click="fetchSummary">Tampilkan</button>
    </div>
    <div v-if="summary">
      <p>Pendapatan: <b>Rp {{ format(summary.total_income) }}</b></p>
      <p>Pengeluaran: <b>Rp {{ format(summary.total_expense) }}</b></p>
      <p><b>Laba/Rugi: Rp {{ format(summary.profit) }}</b></p>
    </div>
    <h4>Rekap Bulanan</h4>
    <table border="1" cellpadding="6" style="border-collapse:collapse;">
      <thead>
        <tr>
          <th>Bulan</th>
          <th>Pendapatan</th>
          <th>Pengeluaran</th>
          <th>Laba/Rugi</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in recap" :key="row.month">
          <td>{{ namaBulan(row.month) }}</td>
          <td>{{ format(row.total_income) }}</td>
          <td>{{ format(row.total_expense) }}</td>
          <td>{{ format(row.profit) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import axios from 'axios'

const month = ref(new Date().toISOString().slice(0,7))
const summary = ref(null)
const recap = ref([])

const format = (val) => new Intl.NumberFormat('id-ID').format(val || 0)
const namaBulan = (m) => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'][m-1] || m

const fetchSummary = async () => {
  const [year, mon] = month.value.split('-')
  const start = `${year}-${mon}-01`
  const end = new Date(year, mon, 0).toISOString().slice(0,10)
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/profit-loss', {
    params: { start_date: start, end_date: end },
    headers: { Authorization: `Bearer ${token}` }
  })
  summary.value = res.data
  fetchRecap(year)
}

const fetchRecap = async (year) => {
  const token = sessionStorage.getItem('token')
  const res = await axios.get('/api/profit-loss/recap', {
    params: { type: 'monthly', year },
    headers: { Authorization: `Bearer ${token}` }
  })
  recap.value = res.data
}

fetchSummary()
</script> 