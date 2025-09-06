<script setup>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import { computed, watch } from 'vue'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale)

const props = defineProps({
  labels: Array,
  incomeData: Array,
  expenseData: Array,
})

const chartData = computed(() => ({
  labels: props.labels,
  datasets: [
    {
      label: 'Pemasukan (Income)',
      backgroundColor: '#198754', // green
      data: props.incomeData,
    },
    {
      label: 'Pengeluaran (Expense)',
      backgroundColor: '#dc3545', // red
      data: props.expenseData,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
    },
  },
}
</script>

<template>
  <div style="height: 350px">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>
