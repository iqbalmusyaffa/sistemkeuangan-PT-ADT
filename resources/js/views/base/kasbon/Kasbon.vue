<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-money" /> Manajemen Kasbon
          <CButton color="primary" @click="showCreateModal = true" class="float-end">
            Buat Kasbon Baru
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>
          <div class="w-100">
            <table ref="kasbonTableRef" class="display nowrap"></table>
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Create Kasbon Modal -->
    <CModal :visible="showCreateModal" @close="showCreateModal = false" size="lg">
      <CModalHeader>
        <CModalTitle>Buat Kasbon Baru</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="createKasbon">
          <CFormSelect v-model="form.proyek_id" label="Proyek" class="mb-3">
            <option value="">Pilih Proyek</option>
            <option v-for="proyek in proyeks" :key="proyek.id" :value="proyek.id">
              {{ proyek.name }}
            </option>
          </CFormSelect>

          <CFormInput
            v-model="form.amount"
            type="number"
            label="Jumlah"
            required
            class="mb-3"
          />

          <CFormTextarea
            v-model="form.description"
            label="Deskripsi"
            placeholder="Masukkan deskripsi kasbon"
            class="mb-3"
          />

          <CRow>
            <CCol md="6">
              <CFormInput
                v-model="form.kasbon_date"
                type="date"
                label="Tanggal Kasbon"
                required
                class="mb-3"
              />
            </CCol>
            <CCol md="6">
              <CFormInput
                v-model="form.due_date"
                type="date"
                label="Jatuh Tempo"
                required
                class="mb-3"
              />
            </CCol>
          </CRow>

          <CFormSelect
            v-model="form.payment_method"
            label="Metode Pembayaran"
            :options="[
              { value: 'cash', label: 'Tunai' },
              { value: 'transfer', label: 'Transfer' }
            ]"
            required
            class="mb-3"
          />

          <CFormInput
            v-if="form.payment_method === 'transfer'"
            v-model="form.bank_info"
            label="Informasi Bank"
            placeholder="Masukkan informasi bank"
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
        <CButton color="secondary" @click="showCreateModal = false">Batal</CButton>
        <CButton color="primary" @click="createKasbon">Simpan</CButton>
      </CModalFooter>
    </CModal>

    <!-- Kasbon Details Modal -->
    <CModal :visible="!!selectedKasbon" @close="selectedKasbon = null" size="lg">
      <CModalHeader>
        <CModalTitle>Detail Kasbon</CModalTitle>
      </CModalHeader>
      <CModalBody v-if="selectedKasbon">
        <CRow>
          <CCol md="6">
            <p><strong>Proyek:</strong> {{ selectedKasbon.project?.name }}</p>
            <p><strong>Jumlah:</strong> {{ formatCurrency(selectedKasbon.amount) }}</p>
            <p><strong>Deskripsi:</strong> {{ selectedKasbon.description }}</p>
          </CCol>
          <CCol md="6">
            <p><strong>Tanggal Kasbon:</strong> {{ formatDate(selectedKasbon.kasbon_date) }}</p>
            <p><strong>Jatuh Tempo:</strong> {{ formatDate(selectedKasbon.due_date) }}</p>
            <p><strong>Status:</strong> 
              <CBadge :color="getStatusColor(selectedKasbon.status)">
                {{ getStatusText(selectedKasbon.status) }}
              </CBadge>
            </p>
          </CCol>
        </CRow>

        <h6 class="mt-4">Lampiran</h6>
        <div v-if="selectedKasbon.attachments?.length">
          <CButton
            v-for="attachment in selectedKasbon.attachments"
            :key="attachment.id"
            color="info"
            size="sm"
            class="me-2"
            @click="downloadAttachment(attachment)"
          >
            <CIcon icon="cil-download" /> {{ attachment.name }}
          </CButton>
        </div>
        <p v-else>Tidak ada lampiran</p>

        <h6 class="mt-4">Riwayat Pembayaran</h6>
        <CTable hover responsive>
          <CTableHead>
            <CTableRow>
              <CTableHeaderCell>Tanggal</CTableHeaderCell>
              <CTableHeaderCell>Jumlah</CTableHeaderCell>
              <CTableHeaderCell>Metode</CTableHeaderCell>
              <CTableHeaderCell>Referensi</CTableHeaderCell>
              <CTableHeaderCell>Catatan</CTableHeaderCell>
            </CTableRow>
          </CTableHead>
          <CTableBody>
            <CTableRow v-for="payment in selectedKasbon.payments" :key="payment.id">
              <CTableDataCell>{{ formatDate(payment.payment_date) }}</CTableDataCell>
              <CTableDataCell>{{ formatCurrency(payment.amount) }}</CTableDataCell>
              <CTableDataCell>{{ payment.payment_method }}</CTableDataCell>
              <CTableDataCell>{{ payment.reference_number }}</CTableDataCell>
              <CTableDataCell>{{ payment.notes }}</CTableDataCell>
            </CTableRow>
          </CTableBody>
        </CTable>

        <div v-if="selectedKasbon.status === 'pending'" class="mt-3">
          <CButton color="success" @click="approveKasbon(selectedKasbon)">
            Setujui
          </CButton>
          <CButton color="danger" class="ms-2" @click="showRejectModal = true">
            Tolak
          </CButton>
        </div>
      </CModalBody>
    </CModal>

    <!-- Add Payment Modal -->
    <CModal :visible="showPaymentModal" @close="showPaymentModal = false">
      <CModalHeader>
        <CModalTitle>Tambah Pembayaran</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="addPayment">
          <CFormInput
            v-model="paymentForm.amount"
            type="number"
            label="Jumlah"
            required
            class="mb-3"
          />

          <CFormInput
            v-model="paymentForm.payment_date"
            type="date"
            label="Tanggal Pembayaran"
            required
            class="mb-3"
          />

          <CFormSelect
            v-model="paymentForm.payment_method"
            label="Metode Pembayaran"
            :options="[
              { value: 'cash', label: 'Tunai' },
              { value: 'transfer', label: 'Transfer' }
            ]"
            required
            class="mb-3"
          />

          <CFormInput
            v-if="paymentForm.payment_method === 'transfer'"
            v-model="paymentForm.reference_number"
            label="Nomor Referensi"
            placeholder="Masukkan nomor referensi"
            class="mb-3"
          />

          <CFormTextarea
            v-model="paymentForm.notes"
            label="Catatan"
            placeholder="Masukkan catatan pembayaran"
            class="mb-3"
          />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showPaymentModal = false">Batal</CButton>
        <CButton color="primary" @click="addPayment">Simpan</CButton>
      </CModalFooter>
    </CModal>

    <!-- Reject Modal -->
    <CModal :visible="showRejectModal" @close="showRejectModal = false">
      <CModalHeader>
        <CModalTitle>Tolak Kasbon</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="rejectKasbon">
          <CFormTextarea
            v-model="rejectReason"
            label="Alasan Penolakan"
            placeholder="Masukkan alasan penolakan"
            required
            class="mb-3"
          />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="showRejectModal = false">Batal</CButton>
        <CButton color="danger" @click="rejectKasbon">Tolak</CButton>
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

const kasbonTableRef = ref(null)
const kasbons = ref([])
const proyeks = ref([])
const selectedKasbon = ref(null)
const showCreateModal = ref(false)
const showPaymentModal = ref(false)
const showRejectModal = ref(false)
const error = ref('')
const loading = ref(false)
const form = ref({
  proyek_id: '',
  amount: '',
  description: '',
  kasbon_date: '',
  due_date: '',
  payment_method: 'cash',
  bank_info: '',
  attachments: []
})
const paymentForm = ref({
  kasbon_id: '',
  amount: '',
  payment_date: '',
  payment_method: 'cash',
  reference_number: '',
  notes: ''
})
const rejectReason = ref('')

const fetchKasbons = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get('/api/kasbons')
    if (response.data && response.data.status === 'success') {
      kasbons.value = response.data.data
    } else {
      kasbons.value = []
      error.value = 'Data tidak valid'
    }

    nextTick(() => {
      initDataTable()
    })
  } catch (err) {
    console.error('Error fetching kasbons:', err)
    error.value = 'Gagal memuat data kasbon: ' + (err.response?.data?.message || err.message)
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
  if ($.fn.DataTable.isDataTable(kasbonTableRef.value)) {
    $(kasbonTableRef.value).DataTable().destroy()
  }

  if (!kasbons.value || kasbons.value.length === 0) {
    return
  }

  $(kasbonTableRef.value).DataTable({
    data: kasbons.value,
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
        title: 'Pemohon',
        data: 'applicant.name'
      },
      {
        title: 'Jumlah',
        data: 'amount',
        render: (data) => formatCurrency(data)
      },
      {
        title: 'Tanggal',
        data: 'kasbon_date',
        render: (data) => formatDate(data)
      },
      {
        title: 'Jatuh Tempo',
        data: 'due_date',
        render: (data) => formatDate(data)
      },
      {
        title: 'Status',
        data: 'status',
        render: (data) => {
          const colors = {
            pending: 'warning',
            approved: 'success',
            rejected: 'danger',
            paid: 'info'
          }
          const texts = {
            pending: 'Menunggu',
            approved: 'Disetujui',
            rejected: 'Ditolak',
            paid: 'Lunas'
          }
          return `<span class="badge bg-${colors[data]}">${texts[data]}</span>`
        }
      },
      {
        title: 'Aksi',
        data: null,
        render: (data, type, row) => {
          let buttons = `
            <button class="btn btn-sm btn-info detail-btn" data-id="${row.id}">
              <i class="cil-list"></i> Detail
            </button>
          `
          if (row.status === 'approved') {
            buttons += `
              <button class="btn btn-sm btn-primary payment-btn" data-id="${row.id}">
                <i class="cil-plus"></i> Bayar
              </button>
            `
          }
          return buttons
        }
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

  $(kasbonTableRef.value).off('click', '.detail-btn').on('click', '.detail-btn', function () {
    const id = $(this).data('id')
    const kasbon = kasbons.value.find((k) => k.id == id)
    if (kasbon) viewDetails(kasbon)
  })

  $(kasbonTableRef.value).off('click', '.payment-btn').on('click', '.payment-btn', function () {
    const id = $(this).data('id')
    const kasbon = kasbons.value.find((k) => k.id == id)
    if (kasbon) addPayment(kasbon)
  })
}

const createKasbon = async () => {
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (key !== 'attachments') {
        formData.append(key, form.value[key])
      }
    })
    form.value.attachments.forEach(file => {
      formData.append('attachments[]', file)
    })

    await axios.post('/api/kasbons', formData)
    showCreateModal.value = false
    resetForm()
    fetchKasbons()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Kasbon berhasil dibuat'
    })
  } catch (error) {
    console.error('Error creating kasbon:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal membuat kasbon'
    })
  }
}

const viewDetails = (kasbon) => {
  selectedKasbon.value = kasbon
}

const addPayment = (kasbon) => {
  selectedKasbon.value = kasbon
  paymentForm.value = {
    kasbon_id: kasbon.id,
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    reference_number: '',
    notes: ''
  }
  showPaymentModal.value = true
}

const submitPayment = async () => {
  try {
    await axios.post('/api/kasbon-payments', paymentForm.value)
    showPaymentModal.value = false
    resetPaymentForm()
    fetchKasbons()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Pembayaran berhasil ditambahkan'
    })
  } catch (error) {
    console.error('Error adding payment:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal menambahkan pembayaran'
    })
  }
}

const approveKasbon = async (kasbon) => {
  try {
    await axios.post(`/api/kasbons/${kasbon.id}/approve`)
    fetchKasbons()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Kasbon berhasil disetujui'
    })
  } catch (error) {
    console.error('Error approving kasbon:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal menyetujui kasbon'
    })
  }
}

const rejectKasbon = async () => {
  try {
    await axios.post(`/api/kasbons/${selectedKasbon.value.id}/reject`, {
      reason: rejectReason.value
    })
    showRejectModal.value = false
    rejectReason.value = ''
    fetchKasbons()
    Swal.fire({
      icon: 'success',
      title: 'Sukses',
      text: 'Kasbon berhasil ditolak'
    })
  } catch (error) {
    console.error('Error rejecting kasbon:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal menolak kasbon'
    })
  }
}

const downloadAttachment = async (attachment) => {
  try {
    const response = await axios.get(`/api/kasbon-attachments/${attachment.id}/download`, {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', attachment.name)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    console.error('Error downloading attachment:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal mengunduh lampiran'
    })
  }
}

const handleFileUpload = (event) => {
  form.value.attachments = Array.from(event.target.files)
}

const resetForm = () => {
  form.value = {
    proyek_id: '',
    amount: '',
    description: '',
    kasbon_date: '',
    due_date: '',
    payment_method: 'cash',
    bank_info: '',
    attachments: []
  }
}

const resetPaymentForm = () => {
  paymentForm.value = {
    kasbon_id: '',
    amount: '',
    payment_date: '',
    payment_method: 'cash',
    reference_number: '',
    notes: ''
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
    rejected: 'danger',
    paid: 'info'
  }
  return colors[status] || 'secondary'
}

const getStatusText = (status) => {
  const texts = {
    pending: 'Menunggu',
    approved: 'Disetujui',
    rejected: 'Ditolak',
    paid: 'Lunas'
  }
  return texts[status] || status
}

onMounted(() => {
  fetchKasbons()
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