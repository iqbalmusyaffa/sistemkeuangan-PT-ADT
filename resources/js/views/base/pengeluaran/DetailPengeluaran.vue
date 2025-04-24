<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <CIcon icon="cil-info" class="me-2" />
                Detail Pengeluaran
              </div>
              <div>
                <CButton color="warning" class="me-2" @click="handleEdit">
                  <CIcon icon="cil-pencil" /> Edit
                </CButton>
                <CButton color="danger" @click="handleDelete">
                  <CIcon icon="cil-trash" /> Hapus
                </CButton>
              </div>
            </div>
          </CCardHeader>
          <CCardBody>
            <div v-if="loading" class="text-center">
              <CSpinner />
            </div>
            <div v-else-if="error" class="alert alert-danger">
              {{ error }}
            </div>
            <div v-else>
              <CRow class="mb-4">
                <CCol md="6">
                  <CCard>
                    <CCardHeader>
                      <strong>Informasi Pengeluaran</strong>
                    </CCardHeader>
                    <CCardBody>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Kode Transaksi:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ expense?.kode_transaksi || '-' }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Tanggal:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ formatDate(expense?.transaction_date) }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Status:</strong>
                        </CCol>
                        <CCol sm="8">
                          <CBadge :color="getStatusColor(expense?.status)">
                            {{ expense?.status }}
                          </CBadge>
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Jumlah:</strong>
                        </CCol>
                        <CCol sm="8">
                          Rp {{ formatCurrency(expense?.amount) }}
                        </CCol>
                      </CRow>
                    </CCardBody>
                  </CCard>
                </CCol>
                <CCol md="6">
                  <CCard>
                    <CCardHeader>
                      <strong>Detail Proyek</strong>
                    </CCardHeader>
                    <CCardBody>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Proyek:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ expense?.proyek?.nama_proyek || '-' }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Customer:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ expense?.proyek?.nama_customer || '-' }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Kategori:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ getCategoryName() }}
                        </CCol>
                      </CRow>
                    </CCardBody>
                  </CCard>
                </CCol>
              </CRow>

              <CCard>
                <CCardHeader>
                  <strong>Informasi Tambahan</strong>
                </CCardHeader>
                <CCardBody>
                  <CRow class="mb-3">
                    <CCol sm="2">
                      <strong>Deskripsi:</strong>
                    </CCol>
                    <CCol sm="10">
                      {{ expense?.description || '-' }}
                    </CCol>
                  </CRow>
                  <CRow class="mb-3">
                    <CCol sm="2">
                      <strong>Metode Pembayaran:</strong>
                    </CCol>
                    <CCol sm="10">
                      {{ expense?.payment_method || '-' }}
                    </CCol>
                  </CRow>
                  <CRow class="mb-3">
                    <CCol sm="2">
                      <strong>Dana Persiapan:</strong>
                    </CCol>
                    <CCol sm="10">
                      Rp {{ formatCurrency(expense?.prepared_fund) }}
                    </CCol>
                  </CRow>
                </CCardBody>
              </CCard>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import axios from 'axios'
  import Swal from 'sweetalert2'

  const route = useRoute()
  const router = useRouter()
  const expense = ref(null)
  const loading = ref(true)
  const error = ref('')

  const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  }

  const formatCurrency = (value) => {
    if (!value) return '0'
    return parseFloat(value).toLocaleString('id-ID')
  }

  const getStatusColor = (status) => {
    switch (status) {
      case 'Pending':
        return 'warning'
      case 'Approved':
        return 'success'
      case 'Rejected':
        return 'danger'
      default:
        return 'secondary'
    }
  }

  const getCategoryName = () => {
    if (expense.value?.service_category) {
      return `Jasa - ${expense.value.service_category.nama_kategori}`
    } else if (expense.value?.category) {
      return `Material - ${expense.value.category.nama_kategori}`
    }
    return '-'
  }

  const handleEdit = () => {
    router.push(`/base/pengeluaran/edit/${route.params.id}`)
  }

  const handleDelete = async () => {
    const result = await Swal.fire({
      title: 'Yakin ingin menghapus?',
      text: 'Data yang dihapus tidak bisa dikembalikan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    })

    if (result.isConfirmed) {
      try {
        const token = sessionStorage.getItem('token')
        await axios.delete(`/api/expenses/${route.params.id}`, {
          headers: { Authorization: `Bearer ${token}` }
        })
        
        Swal.fire({
          title: 'Berhasil!',
          text: 'Data pengeluaran berhasil dihapus.',
          icon: 'success'
        }).then(() => {
          router.push('/base/pengeluaran')
        })
      } catch (err) {
        Swal.fire({
          title: 'Error!',
          text: 'Gagal menghapus data pengeluaran.',
          icon: 'error'
        })
      }
    }
  }

  onMounted(async () => {
    try {
      const token = sessionStorage.getItem('token')
      console.log('Fetching expense details for ID:', route.params.id)
      
      const res = await axios.get(`/api/expenses/${route.params.id}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      
      console.log('Expense details response:', res.data)
      
      if (res.data.status === 'error') {
        throw new Error(res.data.message)
      }
      
      if (!res.data.data) {
        throw new Error('Invalid response format from API')
      }
      
      expense.value = res.data.data
    } catch (err) {
      console.error('Gagal mengambil detail pengeluaran:', err)
      error.value = err.message || 'Gagal memuat data pengeluaran'
      
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: error.value,
        confirmButtonText: 'OK'
      }).then(() => {
        router.push('/base/pengeluaran')
      })
    } finally {
      loading.value = false
    }
  })
  </script>

  <style scoped>
  .w-100 {
    width: 100%;
    overflow-x: auto;
  }
  </style>
