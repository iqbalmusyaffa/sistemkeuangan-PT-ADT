<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Tambah Kategori
          </CCardHeader>
          <CCardBody>
            <CForm @submit.prevent="handleSubmit">
              <CRow class="mb-3">
                <CCol md="6">
                  <CFormLabel for="kategori">Nama Kategori</CFormLabel>
                  <CFormInput id="kategori" v-model="kategori" placeholder="Masukkan kategori..." />
                </CCol>
                <CCol md="6">
                  <CFormLabel for="jenis">Jenis</CFormLabel>
                  <CFormSelect id="jenis" v-model="jenis">
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                  </CFormSelect>
                </CCol>
              </CRow>
              <CRow class="mb-3">
                <CCol md="12">
                  <CFormLabel for="deskripsi">Deskripsi</CFormLabel>
                  <CFormTextarea id="deskripsi" v-model="deskripsi" placeholder="Masukkan deskripsi..." />
                </CCol>
              </CRow>
              <CButton type="submit" color="primary">Simpan</CButton>
            </CForm>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="mt-4">
              <table id="kategoriTable" class="display">
                <thead>
                  <tr>
                    <th>Nama Kategori</th>
                    <th>Jenis</th>
                    <th>Deskripsi</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue'
  import axios from 'axios'
  import $ from 'jquery'
  import 'datatables.net'
  import 'datatables.net-dt/css/jquery.dataTables.css' // Correct CSS import
  import 'datatables.net-responsive'
  import 'datatables.net-responsive-dt/css/responsive.dataTables.css' // Correct CSS import

  const kategori = ref('')
  const jenis = ref('pemasukan')
  const deskripsi = ref('')
  const categories = ref([])
  const error = ref('')
  const loading = ref(false)
  let dataTable = null

  const fetchCategories = async () => {
    loading.value = true
    error.value = ''
    try {
      const response = await axios.get('/api/categories')
      categories.value = response.data
      if (!dataTable) {
        dataTable = $('#kategoriTable').DataTable({
          paging: true,
          searching: true,
          ordering: true,
          info: true,
          autoWidth: false,
          responsive: true,
          data: categories.value,
          columns: [
            { data: 'nama_kategori' },
            { data: 'jenis' },
            { data: 'deskripsi' }
          ]
        })
      } else {
        dataTable.clear().rows.add(categories.value).draw()
      }
    } catch (error) {
      console.error('Error fetching categories:', error)
      error.value = 'Gagal mengambil data kategori. Silakan coba lagi nanti.'
    } finally {
      loading.value = false
    }
  }

  const handleSubmit = async () => {
    loading.value = true
    error.value = ''
    if (!kategori.value.trim()) {
      error.value = 'Nama kategori tidak boleh kosong.'
      loading.value = false
      return
    }
    try {
      const response = await axios.post('/api/categories', {
        nama_kategori: kategori.value,
        jenis: jenis.value,
        deskripsi: deskripsi.value
      })
      categories.value.push(response.data)
      kategori.value = ''
      jenis.value = 'pemasukan'
      deskripsi.value = ''
      dataTable.row.add(response.data).draw(false) // More efficient update
      alert('Kategori berhasil ditambahkan.')
    } catch (error) {
      console.error('Error adding category:', error)
      error.value = 'Gagal menambahkan kategori. Silakan coba lagi nanti.'
    } finally {
      loading.value = false
    }
  }

  onMounted(fetchCategories)
  </script>

  <style scoped>
  /* Add any additional styles here */
  #kategoriTable {
    width: 100%;
  }
  </style>
