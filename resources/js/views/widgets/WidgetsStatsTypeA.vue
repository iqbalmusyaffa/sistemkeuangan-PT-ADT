<script setup>
import { onMounted, ref } from 'vue'
import { CChart } from '@coreui/vue-chartjs'
import { getStyle } from '@coreui/utils'
import axios from 'axios'

const userCount = ref(0)
const totalIncome = ref(0)
const totalExpense = ref(0)

const userChartData = ref([0,0,0,0,0,0,0,0,0,0,0,0])
const incomeChartData = ref([0,0,0,0,0,0,0,0,0,0,0,0])
const expenseChartData = ref([0,0,0,0,0,0,0,0,0,0,0,0])

const widgetChartRef1 = ref()
const widgetChartRef2 = ref()
const widgetChartRef3 = ref()

const format = (val) => new Intl.NumberFormat('id-ID').format(val || 0)

onMounted(async () => {
  const token = sessionStorage.getItem('token')
  // Fetch summary data
  const res = await axios.get('/api/dashboard/summary', {
    headers: { Authorization: `Bearer ${token}` }
  })
  userCount.value = res.data.user_count
  totalIncome.value = res.data.total_income
  totalExpense.value = res.data.total_expense

  // Fetch chart data
  const chartRes = await axios.get('/api/dashboard/chart-summary', {
    headers: { Authorization: `Bearer ${token}` }
  })
  userChartData.value = chartRes.data.users
  incomeChartData.value = chartRes.data.income
  expenseChartData.value = chartRes.data.expense

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
  <CRow :xs="{ gutter: 4 }">
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="primary">
        <template #value>
          {{ format(userCount) }}
          <span class="fs-6 fw-normal"> Users </span>
        </template>
        <template #title>Users</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
        <template #chart>
          <CChart
            type="line"
            class="mt-3 mx-3"
            style="height: 70px"
            ref="widgetChartRef1"
            :data="{
              labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
              datasets: [
                {
                  label: 'Users',
                  backgroundColor: 'transparent',
                  borderColor: 'rgba(255,255,255,.55)',
                  pointBackgroundColor: getStyle('--cui-primary'),
                  data: userChartData,
                },
              ],
            }"
            :options="{
              plugins: { legend: { display: false } },
              maintainAspectRatio: false,
              scales: {
                x: { border: { display: false }, grid: { display: false }, ticks: { display: false } },
                y: { min: 0, max: Math.max(...userChartData)+10, display: false, grid: { display: false }, ticks: { display: false } },
              },
              elements: { line: { borderWidth: 1, tension: 0.4 }, point: { radius: 4, hitRadius: 10, hoverRadius: 4 } },
            }"
          />
        </template>
      </CWidgetStatsA>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="info">
        <template #value>
          Rp {{ format(totalIncome) }}
          <span class="fs-6 fw-normal"> Income </span>
        </template>
        <template #title>Income</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
        <template #chart>
          <CChart
            type="line"
            class="mt-3 mx-3"
            style="height: 70px"
            ref="widgetChartRef2"
            :data="{
              labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
              datasets: [
                {
                  label: 'Income',
                  backgroundColor: 'transparent',
                  borderColor: 'rgba(255,255,255,.55)',
                  pointBackgroundColor: getStyle('--cui-info'),
                  data: incomeChartData,
                },
              ],
            }"
            :options="{
              plugins: { legend: { display: false } },
              maintainAspectRatio: false,
              scales: {
                x: { border: { display: false }, grid: { display: false }, ticks: { display: false } },
                y: { min: 0, max: Math.max(...incomeChartData)+10, display: false, grid: { display: false }, ticks: { display: false } },
              },
              elements: { line: { borderWidth: 1 }, point: { radius: 4, hitRadius: 10, hoverRadius: 4 } },
            }"
          />
        </template>
      </CWidgetStatsA>
    </CCol>
    <CCol :sm="6" :xl="4" :xxl="3">
      <CWidgetStatsA color="danger">
        <template #value>
          Rp {{ format(totalExpense) }}
          <span class="fs-6 fw-normal"> Pengeluaran </span>
        </template>
        <template #title>Pengeluaran</template>
        <template #action>
          <CDropdown placement="bottom-end">
            <CDropdownToggle color="transparent" class="p-0 text-white" :caret="false">
              <CIcon icon="cil-options" class="text-white" />
            </CDropdownToggle>
            <CDropdownMenu>
              <CDropdownItem href="#">Action</CDropdownItem>
              <CDropdownItem href="#">Another action</CDropdownItem>
              <CDropdownItem href="#">Something else here</CDropdownItem>
            </CDropdownMenu>
          </CDropdown>
        </template>
        <template #chart>
          <CChart
            type="line"
            class="mt-3 mx-3"
            style="height: 70px"
            ref="widgetChartRef3"
            :data="{
              labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
              datasets: [
                {
                  label: 'Pengeluaran',
                  backgroundColor: 'transparent',
                  borderColor: 'rgba(255,255,255,.55)',
                  pointBackgroundColor: getStyle('--cui-danger'),
                  data: expenseChartData,
                },
              ],
            }"
            :options="{
              plugins: { legend: { display: false } },
              maintainAspectRatio: false,
              scales: {
                x: { border: { display: false }, grid: { display: false }, ticks: { display: false } },
                y: { min: 0, max: Math.max(...expenseChartData)+10, display: false, grid: { display: false }, ticks: { display: false } },
              },
              elements: { line: { borderWidth: 1 }, point: { radius: 4, hitRadius: 10, hoverRadius: 4 } },
            }"
          />
        </template>
      </CWidgetStatsA>
    </CCol>
  </CRow>
</template>
