<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-dollar" class="me-2" />
            Pemasukan
          </CCardHeader>
          <CCardBody>
            <!-- Filter Section -->
            <CRow class="mb-3">
              <CCol md="3">
                <CFormSelect
                  v-model="selectedProyekId"
                  :options="[
                    { label: 'Semua Proyek', value: '' },
                    ...proyeks.map(p => ({ label: p.nama_proyek, value: p.id }))
                  ]"
                  @change="handleProyekChange"
                />
              </CCol>
              <CCol md="3">
                <CFormSelect
                  v-model="selectedStatus"
                  :options="[
                    { label: 'Semua Status', value: '' },
                    { label: 'Pending', value: 'Pending' },
                    { label: 'Diterima', value: 'Diterima' },
                    { label: 'Ditolak', value: 'Ditolak' }
                  ]"
                  @change="handleStatusChange"
                />
              </CCol>
              <CCol md="3">
                <CFormSelect
                  v-model="selectedType"
                  :options="[
                    { label: 'Semua Tipe', value: '' },
                    { label: 'DP', value: 'dp' },
                    { label: 'Pelunasan', value: 'pelunasan' }
                  ]"
                  @change="handleTypeChange"
                />
              </CCol>
              <CCol md="3">
                <CButton color="primary" @click="fetchData">
                  <CIcon icon="cil-sync" /> Refresh
                </CButton>
              </CCol>
            </CRow>

            <!-- Error Alert -->
            <CAlert v-if="error" color="danger" dismissible>
              {{ error }}
            </CAlert>

            <!-- Data Table -->
            <div class="w-100">
              <table ref="dataTableRef" class="display nowrap"></table>
            </div>

            <!-- Add Button -->
            <div class="mt-3">
              <CButton color="primary" @click="openModal('tambah')">
                <CIcon icon="cil-plus" /> Tambah Pemasukan
              </CButton>
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
                <CFormLabel>Kategori</CFormLabel>
                <select v-model="selectedKategoriId" class="form-control" required>
                  <option value="">Pilih Kategori</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.nama_kategori }}
                  </option>
                </select>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Metode Pembayaran</CFormLabel>
                <select v-model="selectedPaymentMethodId" class="form-control" required>
                  <option value="">Pilih Metode Pembayaran</option>
                  <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                    {{ method.nama_metode }}
                  </option>
                </select>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Proyek</CFormLabel>
                <select v-model="selectedProyekId" class="form-control" @change="handleProyekSelect">
                  <option value="">Pilih Proyek</option>
                  <option v-for="proyek in proyeks" :key="proyek.id" :value="proyek.id">
                    {{ proyek.nama_proyek }}
                  </option>
                </select>
              </CCol>
            </CRow>

            <CRow class="mb-3" v-if="selectedProyekId">
              <CCol>
                <CFormLabel>Termin</CFormLabel>
                <select v-model="selectedTerminId" class="form-control" @change="handleTerminSelect">
                  <option value="">Pilih Termin</option>
                  <option v-for="termin in availableTermins" :key="termin.id" :value="termin.id">
                    {{ termin.nama_termin }} ({{ termin.status_termin }})
                  </option>
                </select>
              </CCol>
            </CRow>

            <CRow class="mb-3" v-if="selectedTerminId">
              <CCol>
                <CFormLabel>Tipe Pembayaran</CFormLabel>
                <select v-model="type" class="form-control" required>
                  <option value="dp">DP</option>
                  <option value="pelunasan">Pelunasan</option>
                </select>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Jumlah</CFormLabel>
                <CFormLabel>Tanggal</CFormLabel>
                <CFormInput v-model="tanggal" type="date" required />
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Status</CFormLabel>
                <select v-model="status" class="form-control" required>
                  <option value="Pending">Pending</option>
                  <option value="Diterima">Diterima</option>
                  <option value="Ditolak">Ditolak</option>
                </select>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Bukti Pembayaran</CFormLabel>
                <CFormInput type="file" @change="handleFileUpload" accept="image/*,.pdf" />
                <small class="text-muted">Format: JPEG, PNG, PDF (Max 2MB)</small>
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
  import { useRouter } from 'vue-router'
  import axios from 'axios'
  import $ from 'jquery'
  import Swal from 'sweetalert2'
  import 'datatables.net-dt/css/dataTables.dataTables.min.css'
  import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'
  import 'datatables.net-responsive-dt'

  const router = useRouter()
  const dataTableRef = ref(null)

  const categories = ref([])
  const paymentMethods = ref([])
  const proyeks = ref([])
  const incomes = ref([])

  const error = ref('')
  const loading = ref(false)

  const showModal = ref(false)
  const modalTitle = ref('Tambah Pemasukan')
  const modalButtonText = ref('Simpan')
  const modalMode = ref('tambah')
  const editingId = ref(null)

  const selectedKategoriId = ref('')
  const selectedPaymentMethodId = ref('')
  const selectedProyekId = ref('')
  const jumlah = ref('')
  const deskripsi = ref('')
  const tanggal = ref('')
  const status = ref('Pending')
  const buktiPembayaran = ref(null)

  // Filter states
  const selectedStatus = ref('')
  const selectedType = ref('')

  const fetchData = async () => {
    loading.value = true
    error.value = ''
    const token = sessionStorage.getItem('token')
    if (!token) {
      error.value = 'Token tidak ditemukan. Silakan login ulang.'
      loading.value = false
      return
    }
    try {
      // Build query parameters
      const params = new URLSearchParams()
      if (selectedProyekId.value) params.append('proyek_id', selectedProyekId.value)
      if (selectedStatus.value) params.append('status', selectedStatus.value)
      if (selectedType.value) params.append('type', selectedType.value)

      const [incomeRes, categoryRes, paymentMethodRes, proyekRes] = await Promise.all([
        axios.get(`/api/incomes?${params.toString()}`, { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/kategori', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/payment-methods', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/proyeks', { headers: { Authorization: `Bearer ${token}` } }),
      ])
      
      console.log('categoryRes.data', categoryRes.data)
      categories.value = (categoryRes.data.data || categoryRes.data).filter(cat => cat.jenis === 'pemasukan')
      paymentMethods.value = paymentMethodRes.data.data || paymentMethodRes.data
      proyeks.value = proyekRes.data.data || proyekRes.data
      incomes.value = incomeRes.data.data || incomeRes.data

      nextTick(() => initDataTable())
    } catch (e) {
      if (e.response) {
        error.value = `Gagal memuat data: ${e.response.status} - ${e.response.data.message || e.message}`
      } else {
        error.value = `Gagal memuat data: ${e.message}`
      }
    } finally {
      loading.value = false
    }
  }

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().destroy()
    }

    $(dataTableRef.value).DataTable({
      data: incomes.value,
      columns: [
        { title: 'No', data: null, render: (data, type, row, meta) => meta.row + 1 },
        { title: 'Kode Transaksi', data: 'kode_transaksi' },
        { title: 'Tanggal', data: 'tanggal', render: (data) => new Date(data).toLocaleDateString('id-ID') },
        { title: 'Kategori', data: 'kategori.nama_kategori' },
        { title: 'Metode Pembayaran', data: 'payment_method.nama_metode' },
        { title: 'Jumlah', data: 'jumlah', render: (data) => `Rp ${parseFloat(data).toLocaleString('id-ID')}` },
        { title: 'Status', data: 'status' },
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
      const income = incomes.value.find(i => i.id === id)
      if (income) openModal('edit', income)
    })

    $(dataTableRef.value).on('click', '.delete-btn', function () {
      const id = $(this).data('id')
      handleDelete(id)
    })
  }

  const openModal = (mode, income = null) => {
    modalMode.value = mode
    if (mode === 'edit' && income) {
      selectedKategoriId.value = income.kategori_id
      selectedPaymentMethodId.value = income.payment_method_id
      selectedProyekId.value = income.proyek_id
      jumlah.value = income.jumlah
      deskripsi.value = income.deskripsi
      tanggal.value = income.tanggal
      status.value = income.status
      editingId.value = income.id
      modalTitle.value = 'Edit Pemasukan'
      modalButtonText.value = 'Update'
    } else {
      selectedKategoriId.value = ''
      selectedPaymentMethodId.value = ''
      selectedProyekId.value = ''
      jumlah.value = ''
      deskripsi.value = ''
      tanggal.value = ''
      status.value = 'Pending'
      editingId.value = null
      modalTitle.value = 'Tambah Pemasukan'
      modalButtonText.value = 'Simpan'
    }
    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
  }

  const handleFileUpload = (event) => {
    buktiPembayaran.value = event.target.files[0]
  }

  const handleSubmit = async () => {
    try {
      const token = sessionStorage.getItem('token')
      const formData = new FormData()
      
      formData.append('kategori_id', selectedKategoriId.value)
      formData.append('payment_method_id', selectedPaymentMethodId.value)
      formData.append('proyek_id', selectedProyekId.value)
      formData.append('jumlah', jumlah.value)
      formData.append('deskripsi', deskripsi.value)
      formData.append('tanggal', tanggal.value)
      formData.append('status', status.value)
      
      if (buktiPembayaran.value) {
        formData.append('bukti_pembayaran', buktiPembayaran.value)
      }

      if (modalMode.value === 'edit') {
        await axios.put(`/api/incomes/${editingId.value}`, formData, {
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          }
        })
        Swal.fire('Berhasil', 'Data diperbarui', 'success')
      } else {
        await axios.post('/api/incomes', formData, {
          headers: { 
            Authorization: `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          }
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
        await axios.delete(`/api/incomes/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        })
        Swal.fire('Berhasil', 'Data berhasil dihapus', 'success')
        await fetchData()
      } catch (err) {
        Swal.fire('Gagal', 'Tidak dapat menghapus data', 'error')
      }
    }
  }

  // Filter handlers
  const handleProyekChange = () => {
    fetchData()
  }

  const handleStatusChange = () => {
    fetchData()
  }

  const handleTypeChange = () => {
    fetchData()
  }

  onMounted(() => {
    fetchData()
  })
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
