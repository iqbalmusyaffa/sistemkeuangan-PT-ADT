<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-briefcase" /> Detail Project
          <CButton color="secondary" @click="$router.push('/base/project/project')" class="float-end">
            Kembali
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>

          <!-- Project Details -->
          <div class="project-details mb-4">
            <h4>Informasi Project</h4>
            <div class="row">
              <div class="col-md-6">
                <table class="table">
                  <tr>
                    <th>ID Project</th>
                    <td>{{ formatProjectId(project.created_at, project.id) }}</td>
                  </tr>
                  <tr>
                    <th>Nama Customer</th>
                    <td>{{ project.nama_customer }}</td>
                  </tr>
                  <tr>
                    <th>Nama Project</th>
                    <td>{{ project.nama_project }}</td>
                  </tr>
                  <tr>
                    <th>Lokasi</th>
                    <td>{{ project.lokasi || '-' }}</td>
                  </tr>
                </table>
              </div>
              <div class="col-md-6">
                <table class="table">
                  <tr>
                    <th>Tanggal Mulai</th>
                    <td>{{ formatDate(project.tanggal_mulai) }}</td>
                  </tr>
                  <tr>
                    <th>Tanggal Selesai</th>
                    <td>{{ formatDate(project.tanggal_selesai) }}</td>
                  </tr>
                  <tr>
                    <th>Nilai Kontrak</th>
                    <td>Rp {{ formatCurrency(project.anggaran_kontrak) }}</td>
                  </tr>
                  <tr>
                    <th>Status</th>
                    <td>
                      <CBadge :color="getStatusColor(project.status_project)">
                        {{ project.status_project }}
                      </CBadge>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-12">
                <h6>Deskripsi:</h6>
                <p>{{ project.deskripsi || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- Purchase Materials -->
          <div class="purchase-materials">
            <h4>Daftar Pembelian Material</h4>
            <div class="table-responsive">
              <table ref="purchaseTableRef" class="display nowrap w-100"></table>
            </div>

            <!-- Summary Section -->
            <div class="summary-section mt-4">
              <div class="card">
                <div class="card-body">
                  <h5>Ringkasan Biaya:</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-sm">
                        <tr>
                          <td>Total Material</td>
                          <td class="text-end">Rp {{ formatCurrency(totalMaterial) }}</td>
                        </tr>
                        <tr>
                          <td>Total Jasa</td>
                          <td class="text-end">Rp {{ formatCurrency(totalJasa) }}</td>
                        </tr>
                        <tr>
                          <td>Total Jasa Lain-lain</td>
                          <td class="text-end">Rp {{ formatCurrency(totalJasaLain) }}</td>
                        </tr>
                        <tr class="fw-bold">
                          <td>Total Belanja (Invoice)</td>
                          <td class="text-end">Rp {{ formatCurrency(totalKeseluruhan) }}</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import $ from 'jquery'
import 'datatables.net-dt/css/dataTables.dataTables.min.css'
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'
import 'datatables.net-responsive-dt'

const route = useRoute()
const project = ref({})
const purchases = ref([])
const error = ref('')
const loading = ref(false)
const purchaseTableRef = ref(null)

// Format project ID: YY.MM.XXX where XXX is padded ID
const formatProjectId = (createdAt, id) => {
  if (!createdAt || !id) return '-'
  const date = new Date(createdAt)
  const year = date.getFullYear().toString().substr(-2)
  const month = (date.getMonth() + 1).toString().padStart(2, '0')
  const paddedId = id.toString().padStart(3, '0')
  return `${year}.${month}.${paddedId}`
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

const formatCurrency = (value) => {
  if (!value) return '0'
  return new Intl.NumberFormat('id-ID').format(value)
}

const getStatusColor = (status) => {
  const colors = {
    'Berjalan': 'warning',
    'Selesai': 'success',
    'Batal': 'danger'
  }
  return colors[status] || 'secondary'
}

// Computed properties for totals
const totalMaterial = computed(() => {
  return purchases.value
    .filter(p => p.category?.nama_kategori?.toLowerCase().includes('material'))
    .reduce((sum, p) => sum + Number(p.total_harga), 0)
})

const totalJasa = computed(() => {
  return purchases.value
    .filter(p => p.category?.nama_kategori?.toLowerCase().includes('jasa') && !p.category?.nama_kategori?.toLowerCase().includes('lain'))
    .reduce((sum, p) => sum + Number(p.total_harga), 0)
})

const totalJasaLain = computed(() => {
  return purchases.value
    .filter(p => p.category?.nama_kategori?.toLowerCase().includes('jasa') && p.category?.nama_kategori?.toLowerCase().includes('lain'))
    .reduce((sum, p) => sum + Number(p.total_harga), 0)
})

const totalKeseluruhan = computed(() => {
  return purchases.value.reduce((sum, p) => sum + Number(p.total_harga), 0)
})

const initPurchaseTable = () => {
  if ($.fn.DataTable.isDataTable(purchaseTableRef.value)) {
    $(purchaseTableRef.value).DataTable().destroy()
  }

  $(purchaseTableRef.value).DataTable({
    data: purchases.value,
    columns: [
      { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
      { title: "Item", data: "item" },
      {
        title: "Merek",
        data: "merek",
        render: (data) => data ? data.name : "-"
      },
      { title: "Tipe", data: "type" },
      {
        title: "Unit",
        data: "unit",
        render: (data) => data ? data.unit_name : "-"
      },
      {
        title: "Kategori",
        data: "category",
        render: (data) => data ? data.nama_kategori : "-"
      },
      {
        title: "Jumlah",
        data: "qty",
        render: (data) => data.toLocaleString()
      },
      {
        title: "Harga",
        data: "harga",
        render: (data) => `Rp${formatCurrency(data)}`
      },
      {
        title: "Total",
        data: "total_harga",
        render: (data) => `Rp${formatCurrency(data)}`
      }
    ],
    responsive: true,
    scrollX: true,
    destroy: true
  })
}

const fetchProjectDetails = async () => {
  loading.value = true
  error.value = ''

  try {
    const token = sessionStorage.getItem('token')
    const headers = { Authorization: `Bearer ${token}` }
    
    // Fetch project details
    const projectResponse = await axios.get(`/api/projects/${route.params.id}`, { headers })
    project.value = projectResponse.data

    // Fetch related purchases
    const purchasesResponse = await axios.get(`/api/purchasematerials?project_id=${route.params.id}`, { headers })
    purchases.value = purchasesResponse.data

    nextTick(() => {
      initPurchaseTable()
    })
  } catch (err) {
    error.value = 'Failed to load project details.'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProjectDetails()
})
</script>

<style scoped>
.project-details table th {
  width: 150px;
}
.purchase-materials {
  margin-top: 2rem;
}
</style> 