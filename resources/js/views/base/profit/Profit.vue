<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-chart-line" /> Laporan Laba Rugi
          <CButton color="primary" @click="showGenerateModal = true" class="float-end">
            Generate Laporan
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>
          <div class="w-100">
            <table ref="profitTableRef" class="display nowrap"></table>
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Generate Report Modal -->
    <CModal :visible="showGenerateModal" @close="showGenerateModal = false">
      <CModalHeader>
        <CModalTitle>Generate Laporan Laba Rugi</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="generateReport">
          <CFormSelect v-model="form.proyek_id" label="Proyek" class="mb-3">
            <option value="">Semua Proyek</option>
            <option v-for="proyek in proyeks" :key="proyek.id" :value="proyek.id">
              {{ proyek.name }}
            </option>
          </CFormSelect>

          <CFormSelect
            v-model="form.period_type"
            label="Jenis Periode"
            :options="[
              { value: 'weekly', label: 'Mingguan' },
              { value: 'monthly', label: 'Bulanan' },
              { value: 'yearly', label: 'Tahunan' }
            ]"
            required
            class="mb-3"
          />

          <CFormInput
            v-model="form.start_date"
            type="date"
            label="Tanggal Mulai"
            required
            class="mb-3"
          />

          <CFormInput
            v-model="form.end_date"
            type="date"
            label="Tanggal Selesai"
            required
            class="mb-3"
          />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showGenerateModal = false">Batal</CButton>
        <CButton color="primary" @click="generateReport">Generate</CButton>
      </CModalFooter>
    </CModal>

    <!-- Report Details Modal -->
    <CModal :visible="!!selectedReport" @close="selectedReport = null" size="lg">
      <CModalHeader>
        <CModalTitle>Detail Laporan Laba Rugi</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="selectedReport">
        <h6>Pendapatan</h6>
        <CTable hover responsive>
          <CTableHead>
            <CTableRow>
              <CTableHeaderCell>Deskripsi</CTableHeaderCell>
              <CTableHeaderCell>Jumlah</CTableHeaderCell>
              <CTableHeaderCell>Tanggal</CTableHeaderCell>
            </CTableRow>
          </CTableHead>
          <CTableBody>
            <CTableRow v-for="income in selectedReport.income_details" :key="income.id">
              <CTableDataCell>{{ income.description }}</CTableDataCell>
              <CTableDataCell>{{ formatCurrency(income.amount) }}</CTableDataCell>
              <CTableDataCell>{{ formatDate(income.date) }}</CTableDataCell>
            </CTableRow>
          </CTableBody>
        </CTable>

        <h6 class="mt-4">Pengeluaran</h6>
        <CTable hover responsive>
          <CTableHead>
            <CTableRow>
              <CTableHeaderCell>Deskripsi</CTableHeaderCell>
              <CTableHeaderCell>Jumlah</CTableHeaderCell>
              <CTableHeaderCell>Tanggal</CTableHeaderCell>
            </CTableRow>
          </CTableHead>
          <CTableBody>
            <CTableRow v-for="expense in selectedReport.expense_details" :key="expense.id">
              <CTableDataCell>{{ expense.description }}</CTableDataCell>
              <CTableDataCell>{{ formatCurrency(expense.amount) }}</CTableDataCell>
              <CTableDataCell>{{ formatDate(expense.date) }}</CTableDataCell>
            </CTableRow>
          </CTableBody>
        </CTable>
      </CModalBody>
    </CModal>
  </CRow>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2'
import 'datatables.net-dt/css/dataTables.dataTables.min.css'
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'
import 'datatables.net'
import 'datatables.net-responsive'
import { useRouter } from 'vue-router'
import {
  CCard,
  CCardHeader,
  CCardBody,
  CButton,
  CForm,
  CFormInput,
  CFormSelect,
  CTable,
  CTableHead,
  CTableBody,
  CTableRow,
  CTableHeaderCell,
  CTableDataCell,
  CModal,
  CModalHeader,
  CModalTitle,
  CModalBody,
  CModalFooter,
  CRow,
  CCol,
  CIcon
} from '@coreui/vue'

const profitTableRef = ref(null)
const reports = ref([])
const proyeks = ref([])
const selectedReport = ref(null)
const showGenerateModal = ref(false)
const error = ref('')
const loading = ref(false)
const form = ref({
  proyek_id: '',
  period_type: 'monthly',
  start_date: '',
  end_date: ''
})

const fetchReports = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get('/api/profit-loss-reports')
    if (response.data && response.data.status === 'success') {
      reports.value = response.data.data
    } else {
      reports.value = []
      error.value = 'Data tidak valid'
    }

    nextTick(() => {
      initDataTable()
    })
  } catch (err) {
    console.error('Error fetching reports:', err)
    error.value = 'Gagal memuat data laporan: ' + (err.response?.data?.message || err.message)
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: error.value
    })
  } finally {
    loading.value = false
  }
}

const fetchProyeks = async () => {
  try {
    const response = await axios.get('/api/proyeks')
    proyeks.value = response.data
  } catch (error) {
    console.error('Error fetching proyeks:', error)
  }
}

const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(profitTableRef.value)) {
    $(profitTableRef.value).DataTable().destroy()
  }

  if (!reports.value || reports.value.length === 0) {
    return
  }

  $(profitTableRef.value).DataTable({
    data: reports.value,
    columns: [
      {
        title: 'No',
        data: null,
        render: (data, type, row, meta) => meta.row + 1
      },
      {
        title: 'Proyek',
        data: 'project.name'
      },
      {
        title: 'Periode',
        data: 'period_type',
        render: (data) => formatPeriodType(data)
      },
      {
        title: 'Tanggal Mulai',
        data: 'start_date',
        render: (data) => formatDate(data)
      },
      {
        title: 'Tanggal Selesai',
        data: 'end_date',
        render: (data) => formatDate(data)
      },
      {
        title: 'Total Pendapatan',
        data: 'total_income',
        render: (data) => formatCurrency(data)
      },
      {
        title: 'Total Pengeluaran',
        data: 'total_expense',
        render: (data) => formatCurrency(data)
      },
      {
        title: 'Laba/Rugi',
        data: 'net_profit',
        render: (data) => {
          const formatted = formatCurrency(data)
          return `<span class="${data > 0 ? 'text-success' : 'text-danger'}">${formatted}</span>`
        }
      },
      {
        title: 'Aksi',
        data: null,
        render: (data, type, row) => `
          <button class="btn btn-sm btn-info detail-btn" data-id="${row.id}">
            <i class="cil-list"></i> Detail
          </button>
        `
      }
    ],
    scrollX: true,
    scrollCollapse: true,
    fixedColumns: {
      left: 1,
      right: 1
    },
    dom: '<"top"lf>rt<"bottom"ip><"clear">',
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']]
  })

  $(profitTableRef.value).off('click', '.detail-btn').on('click', '.detail-btn', function () {
    const id = $(this).data('id')
    const report = reports.value.find((r) => r.id == id)
    if (report) viewDetails(report)
  })
}

const generateReport = async () => {
  try {
    await axios.post('/api/profit-loss-reports/generate', form.value)
    showGenerateModal.value = false
    fetchReports()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Laporan berhasil digenerate'
    })
  } catch (error) {
    console.error('Error generating report:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal generate laporan'
    })
  }
}

const viewDetails = (report) => {
  selectedReport.value = report
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR'
  }).format(value)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatPeriodType = (type) => {
  const types = {
    weekly: 'Mingguan',
    monthly: 'Bulanan',
    yearly: 'Tahunan'
  }
  return types[type] || type
}

onMounted(() => {
  fetchReports()
  fetchProyeks()
})
</script>

<style scoped>
.w-100 {
  width: 100%;
  overflow-x: auto;
}

.dataTables_wrapper {
  overflow-x: auto;
  position: relative;
}

table.display {
  width: 100% !important;
  min-width: 1000px;
}

/* Fixed columns styles */
.dataTables_scroll {
  position: relative;
  clear: both;
  width: 100%;
}

.dataTables_scrollBody {
  overflow-x: auto;
  overflow-y: auto;
  max-height: none;
}

/* Fixed column styles */
.fixed-columns {
  position: sticky;
  background: white;
  z-index: 1;
}

.fixed-columns-left {
  left: 0;
  box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.fixed-columns-right {
  right: 0;
  box-shadow: -2px 0 5px rgba(0,0,0,0.1);
}

/* Table cell styles */
table.dataTable tbody td {
  white-space: nowrap;
  padding: 8px;
}

/* Button styles */
.btn {
  margin: 0 2px;
}

/* Status badge styles */
.badge {
  padding: 0.5em 0.75em;
  font-size: 0.875em;
}
</style>
