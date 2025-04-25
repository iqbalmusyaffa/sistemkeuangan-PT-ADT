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
                <CButton color="secondary" class="me-2" @click="router.push('/base/pengeluaran')" :disabled="loading">
                  <CIcon icon="cil-arrow-left" /> Kembali
                </CButton>
                <CButton v-if="!expense?.source_type" color="warning" class="me-2" @click="handleEdit" :disabled="loading">
                  <CIcon icon="cil-pencil" /> Edit
                </CButton>
                <CButton v-if="!expense?.source_type" color="danger" @click="handleDelete" :disabled="loading">
                  <CIcon icon="cil-trash" /> Hapus
                </CButton>
              </div>
            </div>
          </CCardHeader>
          <CCardBody>
            <div v-if="loading" class="text-center py-5">
              <CSpinner color="primary" />
              <p class="mt-2">Memuat data...</p>
            </div>
            <div v-else-if="error" class="alert alert-danger">
              <CIcon icon="cil-warning" class="me-2" />
              {{ error }}
              <div class="mt-2">
                <CButton color="secondary" size="sm" @click="router.push('/base/pengeluaran')">
                  Kembali ke Daftar Pengeluaran
                </CButton>
              </div>
            </div>
            <div v-else-if="!expense" class="alert alert-warning">
              <CIcon icon="cil-warning" class="me-2" />
              Data pengeluaran tidak ditemukan
              <div class="mt-2">
                <CButton color="secondary" size="sm" @click="router.push('/base/pengeluaran')">
                  Kembali ke Daftar Pengeluaran
                </CButton>
              </div>
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
                          {{ expense.kode_transaksi || '-' }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Tanggal:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ formatDate(expense.transaction_date) }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Status:</strong>
                        </CCol>
                        <CCol sm="8">
                          <CBadge :color="getStatusColor(expense.status)">
                            {{ expense.status }}
                          </CBadge>
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Jumlah:</strong>
                        </CCol>
                        <CCol sm="8">
                          Rp {{ formatCurrency(expense.amount) }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Sumber Dana:</strong>
                        </CCol>
                        <CCol sm="8">
                          <CBadge :color="getSourceColor(expense.source_type)">
                            {{ getSourceName(expense.source_type) }}
                          </CBadge>
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
                          {{ expense.proyek?.nama_proyek || '-' }}
                        </CCol>
                      </CRow>
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Customer:</strong>
                        </CCol>
                        <CCol sm="8">
                          {{ expense.proyek?.nama_customer || '-' }}
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
                      <CRow class="mb-3">
                        <CCol sm="4">
                          <strong>Anggaran Proyek:</strong>
                        </CCol>
                        <CCol sm="8">
                          Rp {{ formatCurrency(expense.proyek?.anggaran_kontrak) }}
                        </CCol>
                      </CRow>
                    </CCardBody>
                  </CCard>
                </CCol>
              </CRow>

              <!-- Detail Sumber Dana -->
              <CCard v-if="expense.source_type" class="mb-4">
                <CCardHeader>
                  <strong>Detail {{ getSourceName(expense.source_type) }}</strong>
                </CCardHeader>
                <CCardBody>
                  <!-- Detail Termin -->
                  <div v-if="expense.source_type === 'termin' && expense.source">
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Nama Termin:</strong>
                      </CCol>
                      <CCol sm="9">
                        {{ expense.source.nama_termin }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Jumlah Pembayaran:</strong>
                      </CCol>
                      <CCol sm="9">
                        Rp {{ formatCurrency(expense.source.jumlah_pembayaran) }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Tanggal Pembayaran:</strong>
                      </CCol>
                      <CCol sm="9">
                        {{ formatDate(expense.source.tanggal_pembayaran) }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Status Pembayaran:</strong>
                      </CCol>
                      <CCol sm="9">
                        <CBadge :color="getStatusColor(expense.source.status_pembayaran)">
                          {{ expense.source.status_pembayaran }}
                        </CBadge>
                      </CCol>
                    </CRow>
                  </div>

                  <!-- Detail Purchase -->
                  <div v-if="expense.source_type === 'purchase' && expense.source">
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Item:</strong>
                      </CCol>
                      <CCol sm="9">
                        {{ expense.source.item }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Jumlah:</strong>
                      </CCol>
                      <CCol sm="9">
                        {{ expense.source.jumlah }} {{ expense.source.unit?.nama_unit || '-' }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Harga Satuan:</strong>
                      </CCol>
                      <CCol sm="9">
                        Rp {{ formatCurrency(expense.source.harga_satuan) }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Total Harga:</strong>
                      </CCol>
                      <CCol sm="9">
                        Rp {{ formatCurrency(expense.source.total_harga) }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Merek:</strong>
                      </CCol>
                      <CCol sm="9">
                        {{ expense.source.merek?.nama_merek || '-' }}
                      </CCol>
                    </CRow>
                    <CRow class="mb-3">
                      <CCol sm="3">
                        <strong>Kategori:</strong>
                      </CCol>
                      <CCol sm="9">
                        {{ expense.source.is_service ? 
                           expense.source.serviceCategory?.nama_kategori : 
                           expense.source.category?.nama_kategori || '-' }}
                      </CCol>
                    </CRow>
                  </div>
                </CCardBody>
              </CCard>

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
                      {{ expense.description || '-' }}
                    </CCol>
                  </CRow>
                  <CRow class="mb-3">
                    <CCol sm="2">
                      <strong>Metode Pembayaran:</strong>
                    </CCol>
                    <CCol sm="10">
                      {{ expense.payment_method || '-' }}
                    </CCol>
                  </CRow>
                  <CRow class="mb-3">
                    <CCol sm="2">
                      <strong>Dana Persiapan:</strong>
                    </CCol>
                    <CCol sm="10">
                      Rp {{ formatCurrency(expense.prepared_fund) }}
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
    switch (status?.toLowerCase()) {
      case 'pending':
        return 'warning'
      case 'approved':
        return 'success'
      case 'rejected':
        return 'danger'
      default:
        return 'secondary'
    }
  }

  const getSourceColor = (sourceType) => {
    switch (sourceType) {
      case 'termin':
        return 'info'
      case 'purchase':
        return 'primary'
      default:
        return 'secondary'
    }
  }

  const getSourceName = (sourceType) => {
    switch (sourceType) {
      case 'termin':
        return 'Termin'
      case 'purchase':
        return 'Pembelian'
      default:
        return 'Pengeluaran Langsung'
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
        loading.value = true
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
        console.error('Error deleting expense:', err)
        Swal.fire({
          title: 'Error!',
          text: err.response?.data?.message || 'Gagal menghapus data pengeluaran.',
          icon: 'error'
        })
      } finally {
        loading.value = false
      }
    }
  }

  const fetchExpenseDetails = async () => {
    try {
      loading.value = true
      error.value = ''
      const token = sessionStorage.getItem('token')
      
      const res = await axios.get(`/api/expenses/${route.params.id}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      
      if (res.data.status === 'error') {
        throw new Error(res.data.message)
      }
      
      if (!res.data.data) {
        throw new Error('Invalid response format from API')
      }
      
      expense.value = res.data.data
      console.log('Expense details:', expense.value)
    } catch (err) {
      console.error('Error fetching expense details:', err)
      error.value = err.response?.data?.message || err.message || 'Gagal memuat data pengeluaran'
    } finally {
      loading.value = false
    }
  }

  onMounted(() => {
    fetchExpenseDetails()
  })
  </script>

  <style scoped>
  .w-100 {
    width: 100%;
    overflow-x: auto;
  }
  </style>
