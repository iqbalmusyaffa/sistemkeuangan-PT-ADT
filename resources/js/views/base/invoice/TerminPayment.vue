<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-money" /> Pembayaran Termin
          <CButton color="primary" @click="openModal('tambah')" class="float-end">
            Tambah Pembayaran
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>

          <!-- Project Filter -->
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="project_filter">Pilih Proyek</CFormLabel>
              <CFormSelect
                v-model="selectedProject"
                id="project_filter"
                @change="filterByProject"
              >
                <option value="">-- Pilih Proyek --</option>
                <option
                  v-for="project in projects"
                  :key="project.id"
                  :value="project.id"
                >
                  {{ project.nama_customer }} - {{ project.nama_proyek }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>

          <!-- Termin List -->
          <div v-if="selectedProject && termins.length > 0">
            <div v-for="termin in termins" :key="termin.id" class="card mb-3">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ termin.nama_termin }}</h5>
                <span :class="getStatusBadgeClass(termin.status_termin)">
                  {{ termin.status_termin }}
                </span>
              </div>
              <div class="card-body">
                <CRow>
                  <CCol md="6">
                    <div class="mb-2">
                      <strong>Nilai Termin:</strong>
                      <span class="float-end">{{ formatCurrency(termin.nilai_termin) }}</span>
                    </div>
                    <div class="mb-2">
                      <strong>DP ({{ termin.dp_percentage }}%):</strong>
                      <span class="float-end">{{ formatCurrency(termin.nilai_dp) }}</span>
                    </div>
                    <div class="mb-2">
                      <strong>Pelunasan:</strong>
                      <span class="float-end">{{ formatCurrency(termin.nilai_pelunasan) }}</span>
                    </div>
                  </CCol>
                  <CCol md="6">
                    <div class="mb-2">
                      <strong>DP Dibayar:</strong>
                      <span class="float-end">{{ formatCurrency(termin.total_dp_paid) }}</span>
                    </div>
                    <div class="mb-2">
                      <strong>Pelunasan Dibayar:</strong>
                      <span class="float-end">{{ formatCurrency(termin.total_pelunasan_paid) }}</span>
                    </div>
                    <div class="mb-2">
                      <strong>Sisa Pembayaran:</strong>
                      <span class="float-end">{{ formatCurrency(termin.remaining_total) }}</span>
                    </div>
                  </CCol>
                </CRow>

                <!-- Payment History -->
                <div class="mt-3">
                  <h6>Riwayat Pembayaran</h6>
                  <div class="table-responsive">
                    <table class="table table-sm">
                      <thead>
                        <tr>
                          <th>Tanggal</th>
                          <th>Jenis</th>
                          <th>Jumlah</th>
                          <th>Status</th>
                          <th>Bukti</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="payment in getTerminPayments(termin.id)" :key="payment.id">
                          <td>{{ formatDate(payment.tanggal) }}</td>
                          <td>{{ payment.type === 'dp' ? 'DP' : 'Pelunasan' }}</td>
                          <td>{{ formatCurrency(payment.jumlah) }}</td>
                          <td>
                            <span :class="getPaymentStatusBadgeClass(payment.status)">
                              {{ payment.status }}
                            </span>
                          </td>
                          <td>
                            <CButton
                              v-if="payment.bukti_pembayaran"
                              color="info"
                              size="sm"
                              @click="viewPaymentProof(payment)"
                            >
                              Lihat Bukti
                            </CButton>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else-if="selectedProject" class="alert alert-info">
            Tidak ada termin untuk proyek ini
          </div>
          <div v-else class="alert alert-info">
            Silakan pilih proyek terlebih dahulu
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Payment Modal -->
    <CModal
      :visible="showModal"
      @close="closeModal"
      size="lg"
      :title="modalTitle"
      backdrop="static"
    >
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="termin_id">Termin</CFormLabel>
              <CFormSelect
                id="termin_id"
                v-model="form.termin_id"
                required
              >
                <option value="">Pilih Termin</option>
                <option
                  v-for="termin in availableTermins"
                  :key="termin.id"
                  :value="termin.id"
                >
                  {{ termin.nama_termin }} ({{ formatCurrency(termin.remaining_total) }})
                </option>
              </CFormSelect>
            </CCol>
            <CCol md="6">
              <CFormLabel for="type">Jenis Pembayaran</CFormLabel>
              <CFormSelect
                id="type"
                v-model="form.type"
                required
              >
                <option value="dp">DP</option>
                <option value="pelunasan">Pelunasan</option>
              </CFormSelect>
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="jumlah">Jumlah</CFormLabel>
              <CFormInput
                type="number"
                id="jumlah"
                v-model="form.jumlah"
                required
                min="0"
                step="0.01"
              />
            </CCol>
            <CCol md="6">
              <CFormLabel for="payment_method_id">Metode Pembayaran</CFormLabel>
              <CFormSelect
                id="payment_method_id"
                v-model="form.payment_method_id"
                required
              >
                <option value="">Pilih Metode Pembayaran</option>
                <option
                  v-for="method in paymentMethods"
                  :key="method.id"
                  :value="method.id"
                >
                  {{ method.nama_metode }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="tanggal">Tanggal</CFormLabel>
              <CFormInput
                type="date"
                id="tanggal"
                v-model="form.tanggal"
                required
              />
            </CCol>
            <CCol md="6">
              <CFormLabel for="status">Status</CFormLabel>
              <CFormSelect
                id="status"
                v-model="form.status"
                required
              >
                <option value="Pending">Pending</option>
                <option value="Diterima">Diterima</option>
                <option value="Ditolak">Ditolak</option>
              </CFormSelect>
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="deskripsi">Deskripsi</CFormLabel>
              <CFormTextarea
                id="deskripsi"
                v-model="form.deskripsi"
                rows="3"
              />
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="bukti_pembayaran">Bukti Pembayaran</CFormLabel>
              <CFormInput
                type="file"
                id="bukti_pembayaran"
                @change="handleFileUpload"
                accept="image/*,.pdf"
              />
            </CCol>
          </CRow>
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeModal">
          Batal
        </CButton>
        <CButton color="primary" @click="handleSubmit">
          Simpan
        </CButton>
      </CModalFooter>
    </CModal>

    <!-- Payment Proof Modal -->
    <CModal
      :visible="showProofModal"
      @close="closeProofModal"
      size="lg"
      title="Bukti Pembayaran"
    >
      <CModalBody>
        <div v-if="selectedPayment">
          <img
            v-if="isImage(selectedPayment.bukti_pembayaran)"
            :src="getProofUrl(selectedPayment.bukti_pembayaran)"
            class="img-fluid"
            alt="Bukti Pembayaran"
          />
          <iframe
            v-else
            :src="getProofUrl(selectedPayment.bukti_pembayaran)"
            width="100%"
            height="500px"
          ></iframe>
        </div>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeProofModal">
          Tutup
        </CButton>
      </CModalFooter>
    </CModal>
  </CRow>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import moment from 'moment'
import Swal from 'sweetalert2'

export default {
  name: 'TerminPayment',
  setup() {
    const selectedProject = ref('')
    const projects = ref([])
    const termins = ref([])
    const payments = ref([])
    const showModal = ref(false)
    const showProofModal = ref(false)
    const loading = ref(false)
    const error = ref(null)
    const paymentMethods = ref([])
    const selectedPayment = ref(null)
    const modalMode = ref('tambah')

    const form = ref({
      termin_id: '',
      type: 'dp',
      jumlah: 0,
      payment_method_id: '',
      tanggal: moment().format('YYYY-MM-DD'),
      status: 'Pending',
      deskripsi: '',
      bukti_pembayaran: null
    })

    const availableTermins = computed(() => {
      if (!selectedProject.value) return []
      return termins.value.filter(termin => termin.remaining_total > 0)
    })

    const loadProjects = async () => {
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/proyeks', {
          headers: { Authorization: `Bearer ${token}` }
        })
        projects.value = response.data.data || []
      } catch (err) {
        console.error('Error loading projects:', err)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Gagal memuat data proyek'
        })
      }
    }

    const loadTermins = async () => {
      if (!selectedProject.value) return
      
      loading.value = true
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/termins', {
          params: { proyek_id: selectedProject.value },
          headers: { Authorization: `Bearer ${token}` }
        })
        termins.value = response.data.data || []
      } catch (err) {
        console.error('Error loading termins:', err)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Gagal memuat data termin'
        })
      } finally {
        loading.value = false
      }
    }

    const loadPayments = async () => {
      if (!selectedProject.value) return
      
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/incomes', {
          params: { proyek_id: selectedProject.value },
          headers: { Authorization: `Bearer ${token}` }
        })
        payments.value = response.data.data || []
      } catch (err) {
        console.error('Error loading payments:', err)
      }
    }

    const loadPaymentMethods = async () => {
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/payment-methods', {
          headers: { Authorization: `Bearer ${token}` }
        })
        paymentMethods.value = response.data.data || []
      } catch (err) {
        console.error('Error loading payment methods:', err)
      }
    }

    const filterByProject = () => {
      loadTermins()
      loadPayments()
    }

    const openModal = (mode) => {
      modalMode.value = mode
      resetForm()
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      resetForm()
    }

    const resetForm = () => {
      form.value = {
        termin_id: '',
        type: 'dp',
        jumlah: 0,
        payment_method_id: '',
        tanggal: moment().format('YYYY-MM-DD'),
        status: 'Pending',
        deskripsi: '',
        bukti_pembayaran: null
      }
    }

    const handleFileUpload = (event) => {
      form.value.bukti_pembayaran = event.target.files[0]
    }

    const handleSubmit = async () => {
      try {
        const token = sessionStorage.getItem('token')
        const formData = new FormData()
        
        Object.keys(form.value).forEach(key => {
          if (form.value[key] !== null) {
            formData.append(key, form.value[key])
          }
        })

        await axios.post('/api/incomes', formData, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'multipart/form-data'
          }
        })

        Swal.fire({
          icon: 'success',
          title: 'Sukses',
          text: 'Pembayaran berhasil ditambahkan'
        })

        closeModal()
        loadPayments()
        loadTermins()
      } catch (err) {
        console.error('Error submitting payment:', err)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.response?.data?.message || 'Gagal menambahkan pembayaran'
        })
      }
    }

    const getTerminPayments = (terminId) => {
      return payments.value.filter(payment => payment.termin_id === terminId)
    }

    const viewPaymentProof = (payment) => {
      selectedPayment.value = payment
      showProofModal.value = true
    }

    const closeProofModal = () => {
      showProofModal.value = false
      selectedPayment.value = null
    }

    const getStatusBadgeClass = (status) => {
      const classes = {
        'Lunas': 'badge bg-success',
        'DP Dibayar': 'badge bg-warning',
        'Belum Dibayar': 'badge bg-danger'
      }
      return classes[status] || 'badge bg-secondary'
    }

    const getPaymentStatusBadgeClass = (status) => {
      const classes = {
        'Diterima': 'badge bg-success',
        'Pending': 'badge bg-warning',
        'Ditolak': 'badge bg-danger'
      }
      return classes[status] || 'badge bg-secondary'
    }

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value)
    }

    const formatDate = (date) => {
      return moment(date).format('DD/MM/YYYY')
    }

    const getProofUrl = (path) => {
      return `/storage/${path}`
    }

    const isImage = (path) => {
      if (!path) return false
      const ext = path.split('.').pop().toLowerCase()
      return ['jpg', 'jpeg', 'png', 'gif'].includes(ext)
    }

    onMounted(() => {
      loadProjects()
      loadPaymentMethods()
    })

    return {
      selectedProject,
      projects,
      termins,
      showModal,
      showProofModal,
      loading,
      error,
      form,
      paymentMethods,
      selectedPayment,
      availableTermins,
      modalMode,
      filterByProject,
      openModal,
      closeModal,
      handleSubmit,
      handleFileUpload,
      getTerminPayments,
      viewPaymentProof,
      closeProofModal,
      getStatusBadgeClass,
      getPaymentStatusBadgeClass,
      formatCurrency,
      formatDate,
      getProofUrl,
      isImage
    }
  }
}
</script>

<style scoped>
.badge {
  padding: 5px 10px;
  border-radius: 3px;
}
</style> 