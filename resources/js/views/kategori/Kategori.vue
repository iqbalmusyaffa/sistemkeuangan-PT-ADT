<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Kategori
            <CButton color="primary" @click="showModal = true" class="float-end">Tambah Kategori</CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="mt-4">
              <DataTable
                :data="categories"
                :columns="columns"
                :options="dataTableOptions"
                :key="dataTableKey"
              />
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal for Adding Category -->
      <CModal :visible="showModal" @close="showModal = false" title="Tambah Kategori" destroyOnClose>
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="kategori">Nama Kategori</CFormLabel>
                <CFormInput id="kategori" v-model="kategori" placeholder="Masukkan kategori..." required />
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
        </CModalBody>
      </CModal>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue';
  import axios from 'axios';
  import DataTable from 'datatables.net-vue3';
  import DataTablesCore from 'datatables.net-dt';

  DataTable.use(DataTablesCore);

  const kategori = ref('');
  const jenis = ref('pemasukan');
  const deskripsi = ref('');
  const categories = ref([]);
  const error = ref('');
  const loading = ref(false);
  const showModal = ref(false);
  const dataTableKey = ref(0);

  const columns = [
    { title: 'Nama Kategori', data: 'nama_kategori' },
    { title: 'Jenis', data: 'jenis' },
    { title: 'Deskripsi', data: 'deskripsi' }
  ];

  const dataTableOptions = {
    paging: true,
    searching: true,
    ordering: true,
    info: true,
    autoWidth: false,
    responsive: true
  };

  const fetchCategories = async () => {
    loading.value = true;
    error.value = '';
    try {
      const response = await axios.get('/api/categories');
      categories.value = response.data;
    } catch (err) {
      console.error('Error fetching categories:', err);
      error.value = 'Gagal mengambil data kategori. Silakan coba lagi nanti.';
    } finally {
      loading.value = false;
    }
  };
  const handleSubmit = async () => {
    loading.value = true;
    error.value = '';

    if (!kategori.value.trim()) {
        error.value = 'Nama kategori tidak boleh kosong.';
        loading.value = false;
        return;
    }

    try {
        const token = localStorage.getItem('token'); // Ambil token dari localStorage
        const response = await axios.post('/api/categories', {
            nama_kategori: kategori.value,
            jenis: jenis.value,
            deskripsi: deskripsi.value
        }, {
            headers: {
                Authorization: `Bearer ${token}`
            }
        });

        categories.value.push(response.data);
        resetForm();
        dataTableKey.value++;
        showModal.value = false;
        alert('Kategori berhasil ditambahkan.');
    } catch (err) {
        console.error('Error adding category:', err.response ? err.response.data : err);
        error.value = 'Gagal menambahkan kategori. Silakan coba lagi nanti.';
    } finally {
        loading.value = false;
    }
};

  const resetForm = () => {
    kategori.value = '';
    jenis.value = 'pemasukan';
    deskripsi.value = '';
  };

  onMounted(fetchCategories);
  </script>

  <style scoped>
  </style>
