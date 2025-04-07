<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-dollar" /> Data Pemasukan
            <CButton color="primary" class="float-end" @click="openModal('tambah')">
              Tambah Pemasukan
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
                <CFormLabel>Perusahaan</CFormLabel>
                <select v-model="selectedCompanyId" class="form-control" required>
                  <option value="">Pilih Perusahaan</option>
                  <option v-for="company in companies" :key="company.id" :value="company.id">
                    {{ company.nama_lengkap }}
                  </option>
                </select>
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Kategori</CFormLabel>
                <select v-model="selectedCategoryId" class="form-control" required>
                  <option value="">Pilih Kategori</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.nama_kategori }}
                  </option>
                </select>
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Jumlah</CFormLabel>
                <CFormInput v-model="amount" type="number" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Deskripsi</CFormLabel>
                <CFormTextarea v-model="description" rows="3" />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Tanggal Transaksi</CFormLabel>
                <CFormInput v-model="transactionDate" type="date" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol>
                <CFormLabel>Status</CFormLabel>
                <select v-model="status" class="form-control" required>
                  <option :value="false">Pending</option>
                  <option :value="true">Lunas</option>
                </select>
              </CCol>
            </CRow>
            <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
          </CForm>
        </CModalBody>
      </CModal>

      <!-- Modal Detail -->
      <CModal :visible="showDetailModal" @close="closeDetailModal" title="Detail Pemasukan">
        <CModalBody>
          <ul class="list-group">
            <li><strong>Perusahaan:</strong> {{ detailData.company_nama }}</li>
            <li><strong>Kategori:</strong> {{ detailData.category?.nama_kategori ?? 'N/A' }}</li>
            <li><strong>Jumlah:</strong> Rp {{ parseFloat(detailData.amount).toLocaleString('id-ID') }}</li>
            <li><strong>Tanggal Transaksi:</strong> {{ detailData.transaction_date }}</li>
            <li><strong>Status:</strong> {{ detailData.status }}</li>
            <li><strong>Deskripsi:</strong> {{ detailData.description || '-' }}</li>
            <li><strong>Kode Transaksi:</strong> {{ detailData.kode_transaksi }}</li>
          </ul>
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
  const companies = ref([])
  const categories = ref([])
  const incomes = ref([])
  const error = ref('')
  const loading = ref(false)
  const showModal = ref(false)
  const showDetailModal = ref(false)
  const modalTitle = ref('Tambah Pemasukan')
  const modalButtonText = ref('Simpan')
  const modalMode = ref('tambah')
  const editingId = ref(null)
  const detailData = ref({})

  const selectedCompanyId = ref('')
  const selectedCategoryId = ref('')
  const amount = ref('')
  const description = ref('')
  const transactionDate = ref('')
  const status = ref(false)

  const fetchData = async () => {
    loading.value = true
    try {
      const token = localStorage.getItem('token')
      const [incomeRes, companyRes, categoryRes] = await Promise.all([
        axios.get('/api/incomes', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/companies', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/categories', { headers: { Authorization: `Bearer ${token}` } }),
      ])
      companies.value = companyRes.data.data || companyRes.data
      categories.value = categoryRes.data.data || categoryRes.data
      const incomesData = incomeRes.data.data || incomeRes.data

      incomes.value = incomesData.map(income => ({
        ...income,
        company_nama: companies.value.find(c => c.id === income.company_id)?.nama_lengkap || 'Unknown',
        category: categories.value.find(c => c.id === income.category_id) || {},
      }))
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
      data: incomes.value,
      columns: [
        { title: 'No', data: null, render: (data, type, row, meta) => meta.row + 1 },
        {
          title: 'Nama Perusahaan',
          data: null,
          render: (data, type, row) =>
            `<button class="btn btn-link text-primary p-0 company-detail-btn" data-id="${row.id}">
              ${row.company_nama}
            </button>`
        },
        {
          title: 'Jumlah',
          data: 'amount',
          render: (data) => `Rp ${parseFloat(data).toLocaleString('id-ID')}`
        },
        {
          title: 'Aksi',
          data: null,
          render: (data, type, row) =>
            `<button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">Edit</button>`
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

    $(dataTableRef.value).on('click', '.company-detail-btn', function () {
      const id = $(this).data('id')
      const income = incomes.value.find(i => i.id === id)
      if (income) openDetailModal(income)
    })
  }

  const openModal = (mode, income = null) => {
    modalMode.value = mode
    if (mode === 'edit' && income) {
      selectedCompanyId.value = income.company_id
      selectedCategoryId.value = income.category_id
      amount.value = income.amount
      description.value = income.description
      transactionDate.value = income.transaction_date
      status.value = income.status
      editingId.value = income.id
      modalTitle.value = 'Edit Pemasukan'
      modalButtonText.value = 'Update'
    } else {
      selectedCompanyId.value = ''
      selectedCategoryId.value = ''
      amount.value = ''
      description.value = ''
      transactionDate.value = ''
      status.value = false
      editingId.value = null
      modalTitle.value = 'Tambah Pemasukan'
      modalButtonText.value = 'Simpan'
    }
    showModal.value = true
  }

  const closeModal = () => {
    showModal.value = false
  }

  const openDetailModal = (income) => {
    detailData.value = income
    showDetailModal.value = true
  }

  const closeDetailModal = () => {
    showDetailModal.value = false
  }

  const handleSubmit = async () => {
    try {
      const token = localStorage.getItem('token')
      const payload = {
        company_id: selectedCompanyId.value,
        category_id: selectedCategoryId.value,
        amount: amount.value,
        description: description.value,
        transaction_date: transactionDate.value,
        status: status.value,
      }

      if (modalMode.value === 'edit') {
        await axios.put(`/api/incomes/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        })
        Swal.fire('Berhasil', 'Data diperbarui', 'success')
      } else {
        await axios.post('/api/incomes', payload, {
          headers: { Authorization: `Bearer ${token}` },
        })
        Swal.fire('Berhasil', 'Data ditambahkan', 'success')
      }

      showModal.value = false
      await fetchData()
    } catch (err) {
      Swal.fire('Gagal', 'Periksa kembali data yang dimasukkan', 'error')
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
