<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-money" /> Manajemen Anggaran
          <CButton color="primary" @click="showCreateModal = true" class="float-end">
            Buat Anggaran Baru
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>
          <div class="w-100">
            <table ref="budgetTableRef" class="display nowrap"></table>
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Create Budget Modal -->
    <CModal :visible="showCreateModal" @close="showCreateModal = false" size="lg">
      <CModalHeader>
        <CModalTitle>Buat Anggaran Baru</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="createBudget">
          <CFormSelect v-model="form.proyek_id" label="Proyek" class="mb-3">
            <option value="">Pilih Proyek</option>
            <option v-for="proyek in proyeks" :key="proyek.id" :value="proyek.id">
              {{ proyek.name }}
            </option>
          </CFormSelect>

          <CFormInput
            v-model="form.name"
            label="Nama Anggaran"
            placeholder="Masukkan nama anggaran"
            required
            class="mb-3"
          />

          <CFormTextarea
            v-model="form.description"
            label="Deskripsi"
            placeholder="Masukkan deskripsi anggaran"
            class="mb-3"
          />

          <CFormInput
            v-model="form.total_amount"
            type="number"
            label="Total Anggaran"
            required
            class="mb-3"
          />

          <CRow>
            <CCol md="6">
              <CFormInput
                v-model="form.start_date"
                type="date"
                label="Tanggal Mulai"
                required
                class="mb-3"
              />
            </CCol>
            <CCol md="6">
              <CFormInput
                v-model="form.end_date"
                type="date"
                label="Tanggal Selesai"
                required
                class="mb-3"
              />
            </CCol>
          </CRow>

          <h5 class="mb-3">Kategori Anggaran</h5>
          <CCard v-for="(category, index) in form.categories" :key="index" class="mb-3">
            <CCardBody>
              <CRow>
                <CCol md="4">
                  <CFormInput
                    v-model="category.name"
                    label="Nama Kategori"
                    required
                    class="mb-3"
                  />
                </CCol>
                <CCol md="4">
                  <CFormInput
                    v-model="category.allocated_amount"
                    type="number"
                    label="Jumlah"
                    required
                    class="mb-3"
                  />
                </CCol>
                <CCol md="4">
                  <CFormInput
                    v-model="category.description"
                    label="Deskripsi"
                    class="mb-3"
                  />
                </CCol>
              </CRow>
              <CButton color="danger" size="sm" @click="removeCategory(index)">
                Hapus
              </CButton>
            </CCardBody>
          </CCard>

          <CButton color="success" class="mb-3" @click="addCategory">
            Tambah Kategori
          </CButton>
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showCreateModal = false">Batal</CButton>
        <CButton color="primary" @click="createBudget">Simpan</CButton>
      </CModalFooter>
    </CModal>

    <!-- Budget Details Modal -->
    <CModal :visible="!!selectedBudget" @close="selectedBudget = null" size="lg">
      <CModalHeader>
        <CModalTitle>Detail Anggaran</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="selectedBudget">
        <h6>Kategori Anggaran</h6>
        <CTable hover responsive>
          <CTableHead>
            <CTableRow>
              <CTableHeaderCell>Nama Kategori</CTableHeaderCell>
              <CTableHeaderCell>Jumlah</CTableHeaderCell>
              <CTableHeaderCell>Terpakai</CTableHeaderCell>
              <CTableHeaderCell>Sisa</CTableHeaderCell>
            </CTableRow>
          </CTableHead>
          <CTableBody>
            <CTableRow v-for="category in selectedBudget.categories" :key="category.id">
              <CTableDataCell>{{ category.name }}</CTableDataCell>
              <CTableDataCell>{{ formatCurrency(category.allocated_amount) }}</CTableDataCell>
              <CTableDataCell>{{ formatCurrency(category.used_amount) }}</CTableDataCell>
              <CTableDataCell :class="{'text-danger': category.remaining_amount < 0}">
                {{ formatCurrency(category.remaining_amount) }}
              </CTableDataCell>
            </CTableRow>
          </CTableBody>
        </CTable>

        <h6 class="mt-4">Transaksi</h6>
        <CTable hover responsive>
          <CTableHead>
            <CTableRow>
              <CTableHeaderCell>Tanggal</CTableHeaderCell>
              <CTableHeaderCell>Kategori</CTableHeaderCell>
              <CTableHeaderCell>Tipe</CTableHeaderCell>
              <CTableHeaderCell>Jumlah</CTableHeaderCell>
              <CTableHeaderCell>Deskripsi</CTableHeaderCell>
              <CTableHeaderCell>Status</CTableHeaderCell>
              <CTableHeaderCell>Aksi</CTableHeaderCell>
            </CTableRow>
          </CTableHead>
          <CTableBody>
            <CTableRow v-for="transaction in selectedBudget.transactions" :key="transaction.id">
              <CTableDataCell>{{ formatDate(transaction.transaction_date) }}</CTableDataCell>
              <CTableDataCell>{{ transaction.category.name }}</CTableDataCell>
              <CTableDataCell>{{ transaction.transaction_type }}</CTableDataCell>
              <CTableDataCell>{{ formatCurrency(transaction.amount) }}</CTableDataCell>
              <CTableDataCell>{{ transaction.description }}</CTableDataCell>
              <CTableDataCell>
                <CBadge :color="getStatusColor(transaction.status)">
                  {{ getStatusText(transaction.status) }}
                </CBadge>
              </CTableDataCell>
              <CTableDataCell>
                <CButton
                  v-if="transaction.status === 'pending'"
                  color="success"
                  size="sm"
                  @click="approveTransaction(transaction)"
                >
                  Setuju
                </CButton>
                <CButton
                  v-if="transaction.status === 'pending'"
                  color="danger"
                  size="sm"
                  class="ms-1"
                  @click="rejectTransaction(transaction)"
                >
                  Tolak
                </CButton>
              </CTableDataCell>
            </CTableRow>
          </CTableBody>
        </CTable>
      </CModalBody>
    </CModal>

    <!-- Add Transaction Modal -->
    <CModal :visible="showTransactionModal" @close="showTransactionModal = false">
      <CModalHeader>
        <CModalTitle>Tambah Transaksi</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="submitTransaction">
          <CFormSelect
            v-model="transactionForm.category_id"
            label="Kategori"
            :options="selectedBudget?.categories.map(cat => ({ value: cat.id, label: cat.name }))"
            required
            class="mb-3"
          />

          <CFormSelect
            v-model="transactionForm.transaction_type"
            label="Tipe Transaksi"
            :options="[
              { value: 'income', label: 'Pendapatan' },
              { value: 'expense', label: 'Pengeluaran' }
            ]"
            required
            class="mb-3"
          />

          <CFormInput
            v-model="transactionForm.amount"
            type="number"
            label="Jumlah"
            required
            class="mb-3"
          />

          <CFormInput
            v-model="transactionForm.description"
            label="Deskripsi"
            required
            class="mb-3"
          />

          <CFormInput
            v-model="transactionForm.date"
            type="date"
            label="Tanggal"
            required
            class="mb-3"
          />

          <CFormInput
            type="file"
            multiple
            @change="handleFileUpload"
            label="Lampiran"
            class="mb-3"
          />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showTransactionModal = false">Batal</CButton>
        <CButton color="primary" @click="submitTransaction">Simpan</CButton>
      </CModalFooter>
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
  CFormTextarea,
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
  CBadge,
  CIcon
} from '@coreui/vue'

const budgetTableRef = ref(null)
const budgets = ref([])
const proyeks = ref([])
const selectedBudget = ref(null)
const showCreateModal = ref(false)
const showTransactionModal = ref(false)
const error = ref('')
const loading = ref(false)
const form = ref({
  proyek_id: '',
  name: '',
  description: '',
  total_amount: 0,
  start_date: '',
  end_date: '',
  categories: []
})
const transactionForm = ref({
  budget_id: '',
  category_id: '',
  amount: '',
  description: '',
  date: '',
  attachments: []
})

const fetchBudgets = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get('/api/budgets')
    if (response.data && response.data.status === 'success') {
      budgets.value = response.data.data
    } else {
      budgets.value = []
      error.value = 'Data tidak valid'
    }

    nextTick(() => {
      initDataTable()
    })
  } catch (err) {
    console.error('Error fetching budgets:', err)
    error.value = 'Gagal memuat data anggaran: ' + (err.response?.data?.message || err.message)
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
  if ($.fn.DataTable.isDataTable(budgetTableRef.value)) {
    $(budgetTableRef.value).DataTable().destroy()
  }

  if (!budgets.value || budgets.value.length === 0) {
    return
  }

  $(budgetTableRef.value).DataTable({
    data: budgets.value,
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
        title: 'Nama Anggaran',
        data: 'name'
      },
      {
        title: 'Total Anggaran',
        data: 'total_amount',
        render: (data) => formatCurrency(data)
      },
      {
        title: 'Terpakai',
        data: 'used_amount',
        render: (data) => formatCurrency(data)
      },
      {
        title: 'Sisa',
        data: 'remaining_amount',
        render: (data) => {
          const formatted = formatCurrency(data)
          return `<span class="${data < 0 ? 'text-danger' : ''}">${formatted}</span>`
        }
      },
      {
        title: 'Periode',
        data: null,
        render: (data) => `${formatDate(data.start_date)} - ${formatDate(data.end_date)}`
      },
      {
        title: 'Status',
        data: 'status',
        render: (data) => {
          const colors = {
            active: 'success',
            completed: 'info',
            cancelled: 'danger'
          }
          const texts = {
            active: 'Aktif',
            completed: 'Selesai',
            cancelled: 'Dibatalkan'
          }
          return `<span class="badge bg-${colors[data]}">${texts[data]}</span>`
        }
      },
      {
        title: 'Aksi',
        data: null,
        render: (data, type, row) => `
          <button class="btn btn-sm btn-info detail-btn" data-id="${row.id}">
            <i class="cil-list"></i> Detail
          </button>
          <button class="btn btn-sm btn-primary transaction-btn" data-id="${row.id}">
            <i class="cil-plus"></i> Transaksi
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

  $(budgetTableRef.value).off('click', '.detail-btn').on('click', '.detail-btn', function () {
    const id = $(this).data('id')
    const budget = budgets.value.find((b) => b.id == id)
    if (budget) viewDetails(budget)
  })

  $(budgetTableRef.value).off('click', '.transaction-btn').on('click', '.transaction-btn', function () {
    const id = $(this).data('id')
    const budget = budgets.value.find((b) => b.id == id)
    if (budget) addTransaction(budget)
  })
}

const createBudget = async () => {
  try {
    await axios.post('/api/budgets', form.value)
    showCreateModal.value = false
    resetForm()
    fetchBudgets()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Budget berhasil dibuat'
    })
  } catch (error) {
    console.error('Error creating budget:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal membuat budget'
    })
  }
}

const viewDetails = (budget) => {
  selectedBudget.value = budget
}

const addTransaction = (budget) => {
  selectedBudget.value = budget
  transactionForm.value = {
    budget_id: budget.id,
    category_id: '',
    amount: '',
    description: '',
    date: new Date().toISOString().split('T')[0],
    attachments: []
  }
  showTransactionModal.value = true
}

const submitTransaction = async () => {
  try {
    const formData = new FormData()
    Object.keys(transactionForm.value).forEach(key => {
      if (key !== 'attachments') {
        formData.append(key, transactionForm.value[key])
      }
    })
    transactionForm.value.attachments.forEach(file => {
      formData.append('attachments[]', file)
    })

    await axios.post('/api/budget-transactions', formData)
    showTransactionModal.value = false
    resetTransactionForm()
    fetchBudgets()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Transaksi berhasil ditambahkan'
    })
  } catch (error) {
    console.error('Error adding transaction:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal menambahkan transaksi'
    })
  }
}

const approveTransaction = async (transaction) => {
  try {
    await axios.post(`/api/budget-transactions/${transaction.id}/approve`)
    fetchBudgets()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Transaksi berhasil disetujui'
    })
  } catch (error) {
    console.error('Error approving transaction:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal menyetujui transaksi'
    })
  }
}

const rejectTransaction = async (transaction) => {
  try {
    await axios.post(`/api/budget-transactions/${transaction.id}/reject`)
    fetchBudgets()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Transaksi berhasil ditolak'
    })
  } catch (error) {
    console.error('Error rejecting transaction:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal menolak transaksi'
    })
  }
}

const handleFileUpload = (event) => {
  transactionForm.value.attachments = Array.from(event.target.files)
}

const resetForm = () => {
  form.value = {
    proyek_id: '',
    name: '',
    description: '',
    total_amount: 0,
    start_date: '',
    end_date: '',
    categories: []
  }
}

const resetTransactionForm = () => {
  transactionForm.value = {
    budget_id: '',
    category_id: '',
    amount: '',
    description: '',
    date: '',
    attachments: []
  }
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

const getStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger'
  }
  return colors[status] || 'secondary'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak'
  }
  return texts[status] || status
}

const addCategory = () => {
  form.value.categories.push({
    name: '',
    description: '',
    allocated_amount: 0
  })
}

const removeCategory = (index) => {
  form.value.categories.splice(index, 1)
}

onMounted(() => {
  fetchBudgets()
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