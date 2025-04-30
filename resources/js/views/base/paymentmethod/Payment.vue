<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-credit-card" /> Metode Pembayaran
          <CButton color="primary" class="float-end" @click="openModal('tambah')">
            Tambah Metode Pembayaran
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>
          <div class="w-100">
            <table ref="dataTableRef" class="display nowrap"></table>
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Modal Tambah/Edit -->
    <CModal :visible="showModal" @close="closeModal" :title="modalTitle">
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
          <CRow class="mb-3">
            <CCol>
              <CFormLabel>Nama Metode</CFormLabel>
              <CFormInput v-model="namaMetode" placeholder="Contoh: Transfer Bank" required />
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol>
              <CFormLabel>Deskripsi</CFormLabel>
              <CFormTextarea v-model="deskripsi" rows="3" placeholder="Deskripsi metode pembayaran" />
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol>
              <CFormSwitch
                v-model="isActive"
                label="Aktif"
                class="mb-2"
              />
            </CCol>
          </CRow>

          <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
        </CForm>
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
import 'datatables.net-responsive-dt'

const dataTableRef = ref(null)
const paymentMethods = ref([])
const error = ref('')
const loading = ref(false)

const showModal = ref(false)
const modalTitle = ref('Tambah Metode Pembayaran')
const modalButtonText = ref('Simpan')
const modalMode = ref('tambah')
const editingId = ref(null)

const namaMetode = ref('')
const deskripsi = ref('')
const isActive = ref(true)

const fetchData = async () => {
  loading.value = true
  try {
    const token = sessionStorage.getItem('token')
    const response = await axios.get('/api/payment-methods', {
      headers: { Authorization: `Bearer ${token}` }
    })
    paymentMethods.value = response.data.data || response.data
    nextTick(() => initDataTable())
  } catch (e) {
    error.value = 'Gagal memuat data'
  } finally {
    loading.value = false
  }
}

const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
    $(dataTableRef.value).DataTable().destroy()
  }

  $(dataTableRef.value).DataTable({
    data: paymentMethods.value,
    columns: [
      { title: 'No', data: null, render: (data, type, row, meta) => meta.row + 1 },
      { title: 'Nama Metode', data: 'nama_metode' },
      { title: 'Deskripsi', data: 'deskripsi' },
      { 
        title: 'Status', 
        data: 'is_active',
        render: (data) => data ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Tidak Aktif</span>'
      },
      {
        title: 'Aksi',
        data: null,
        render: (data, type, row) =>
          `<button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">Edit</button>
           <button class="btn btn-sm btn-danger ms-1 delete-btn" data-id="${row.id}">Hapus</button>`
      },
    ],
    responsive: true,
    scrollX: true,
    destroy: true,
  })

  $(dataTableRef.value).on('click', '.edit-btn', function () {
    const id = $(this).data('id')
    const method = paymentMethods.value.find(m => m.id === id)
    if (method) openModal('edit', method)
  })

  $(dataTableRef.value).on('click', '.delete-btn', function () {
    const id = $(this).data('id')
    handleDelete(id)
  })
}

const openModal = (mode, method = null) => {
  modalMode.value = mode
  if (mode === 'edit' && method) {
    namaMetode.value = method.nama_metode
    deskripsi.value = method.deskripsi
    isActive.value = method.is_active
    editingId.value = method.id
    modalTitle.value = 'Edit Metode Pembayaran'
    modalButtonText.value = 'Update'
  } else {
    namaMetode.value = ''
    deskripsi.value = ''
    isActive.value = true
    editingId.value = null
    modalTitle.value = 'Tambah Metode Pembayaran'
    modalButtonText.value = 'Simpan'
  }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

const handleSubmit = async () => {
  try {
    const token = sessionStorage.getItem('token')
    const payload = {
      nama_metode: namaMetode.value,
      deskripsi: deskripsi.value,
      is_active: isActive.value,
    }

    if (modalMode.value === 'edit') {
      await axios.put(`/api/payment-methods/${editingId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      })
      Swal.fire('Berhasil', 'Data diperbarui', 'success')
    } else {
      await axios.post('/api/payment-methods', payload, {
        headers: { Authorization: `Bearer ${token}` }
      })
      Swal.fire('Berhasil', 'Data ditambahkan', 'success')
    }

    showModal.value = false
    await fetchData()
  } catch (err) {
    Swal.fire('Gagal', 'Periksa kembali data yang dimasukkan', 'error')
  }
}

const handleDelete = async (id) => {
  const konfirmasi = await Swal.fire({
    title: 'Yakin ingin menghapus?',
    text: 'Data yang dihapus tidak bisa dikembalikan.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Hapus',
    cancelButtonText: 'Batal'
  })

  if (konfirmasi.isConfirmed) {
    try {
      const token = sessionStorage.getItem('token')
      await axios.delete(`/api/payment-methods/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      Swal.fire('Berhasil', 'Data berhasil dihapus', 'success')
      await fetchData()
    } catch (err) {
      Swal.fire('Gagal', 'Tidak dapat menghapus data', 'error')
    }
  }
}

onMounted(fetchData)
</script>

<style scoped>
.w-100 {
  width: 100%;
  overflow-x: auto;
}
.dataTables_wrapper {
  overflow-x: auto;
}
table.display {
  width: 100% !important;
}
</style>
