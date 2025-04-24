<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-briefcase" /> Detail Proyek
          <CButton color="secondary" @click="$router.back()" class="float-end">
            <CIcon icon="cil-arrow-left" /> Kembali
          </CButton>
        </CCardHeader>
        <CCardBody>
          <CSpinner v-if="loading" color="primary" />
          <CAlert v-if="error" color="danger">{{ error }}</CAlert>

          <!-- Project Details -->
          <CCard v-if="proyek" class="mb-4">
            <CCardHeader>
              <strong>Informasi Proyek</strong>
            </CCardHeader>
            <CCardBody>
              <CRow>
                <CCol md="6">
                  <CListGroup>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Nama Customer</strong>
                        <span>{{ proyek.nama_customer }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Nama Proyek</strong>
                        <span>{{ proyek.nama_proyek }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Nama Perusahaan</strong>
                        <span>{{ proyek.nama_perusahaan }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Alamat</strong>
                        <span>{{ proyek.alamat }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>No. Telepon</strong>
                        <span>{{ proyek.no_telp }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Email</strong>
                        <span>{{ proyek.email }}</span>
                      </div>
                    </CListGroupItem>
                  </CListGroup>
                </CCol>
              <CCol md="6">
                  <CListGroup>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Lokasi Proyek</strong>
                        <span>{{ proyek.lokasi }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Anggaran Kontrak</strong>
                        <span>Rp {{ formatCurrency(proyek.anggaran_kontrak) }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Tanggal Mulai</strong>
                        <span>{{ formatDate(proyek.tanggal_mulai) }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Tanggal Selesai</strong>
                        <span>{{ formatDate(proyek.tanggal_selesai) }}</span>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Status Proyek</strong>
                        <CBadge :color="getStatusColor(proyek.status_project)">
                        {{ proyek.status_project }}
                        </CBadge>
                      </div>
                    </CListGroupItem>
                    <CListGroupItem>
                      <div class="d-flex justify-content-between align-items-center">
                        <strong>Deskripsi</strong>
                        <span>{{ proyek.deskripsi }}</span>
                      </div>
                    </CListGroupItem>
                  </CListGroup>
              </CCol>
            </CRow>
            </CCardBody>
          </CCard>

          <!-- Purchases Section -->
          <CCard class="mb-4">
            <CCardHeader>
              <strong>Daftar Pembelian</strong>
            </CCardHeader>
            <CCardBody>
              <CTable hover responsive>
                <CTableHead color="light">
                  <CTableRow>
                    <CTableHeaderCell scope="col">No</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Item</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Merek</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Tipe</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Unit</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Kategori</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Jumlah</CTableHeaderCell>
                    <CTableHeaderCell scope="col" class="text-end">Harga</CTableHeaderCell>
                    <CTableHeaderCell scope="col" class="text-end">Total</CTableHeaderCell>
                  </CTableRow>
                </CTableHead>
                <CTableBody>
                  <CTableRow 
                    v-for="(purchase, index) in purchases" 
                    :key="purchase.id"
                    :class="{
                      'service-row': purchase.is_service && purchase.unit?.unit_name?.toLowerCase() === 'jasa',
                      'service-other-row': purchase.is_service && ['set', 'transaksi'].includes(purchase.unit?.unit_name?.toLowerCase()),
                      'material-row': !purchase.is_service
                    }"
                  >
                    <CTableDataCell>{{ index + 1 }}</CTableDataCell>
                    <CTableDataCell>{{ purchase.item }}</CTableDataCell>
                    <CTableDataCell>{{ purchase.is_service ? '-' : (purchase.merek?.name || '-') }}</CTableDataCell>
                    <CTableDataCell>{{ purchase.type || '-' }}</CTableDataCell>
                    <CTableDataCell>{{ purchase.unit?.unit_name || '-' }}</CTableDataCell>
                    <CTableDataCell>{{ purchase.is_service ? (purchase.service_category?.nama_kategori || '-') : (purchase.category?.nama_kategori || '-') }}</CTableDataCell>
                    <CTableDataCell>{{ purchase.qty }}</CTableDataCell>
                    <CTableDataCell class="text-end">Rp {{ formatCurrency(purchase.harga) }}</CTableDataCell>
                    <CTableDataCell class="text-end">Rp {{ formatCurrency(purchase.total_harga) }}</CTableDataCell>
                  </CTableRow>
                </CTableBody>
                <CTableFoot>
                  <CTableRow>
                    <CTableDataCell colspan="8" class="text-end fw-bold">Total Material</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalMaterial) }}</CTableDataCell>
                  </CTableRow>
                  <CTableRow>
                    <CTableDataCell colspan="8" class="text-end fw-bold">Total Jasa</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalJasa) }}</CTableDataCell>
                  </CTableRow>
                  <CTableRow>
                    <CTableDataCell colspan="8" class="text-end fw-bold">Total Jasa Lain-lain</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalJasaLain) }}</CTableDataCell>
                  </CTableRow>
                  <CTableRow class="table-primary">
                    <CTableDataCell colspan="8" class="text-end fw-bold">Total Keseluruhan</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalPurchases) }}</CTableDataCell>
                  </CTableRow>
                </CTableFoot>
              </CTable>
            </CCardBody>
          </CCard>

          <!-- Termins Section -->
          <CCard class="mb-4">
            <CCardHeader>
              <strong>Daftar Termin</strong>
            </CCardHeader>
            <CCardBody>
              <CTable hover responsive>
                <CTableHead color="light">
                  <CTableRow>
                    <CTableHeaderCell scope="col">No</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Nama Termin</CTableHeaderCell>
                    <CTableHeaderCell scope="col" class="text-end">Nilai Termin</CTableHeaderCell>
                    <CTableHeaderCell scope="col" class="text-end">DP (%)</CTableHeaderCell>
                    <CTableHeaderCell scope="col" class="text-end">Nilai DP</CTableHeaderCell>
                    <CTableHeaderCell scope="col" class="text-end">Nilai Pelunasan</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Status</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Tanggal DP</CTableHeaderCell>
                    <CTableHeaderCell scope="col">Tanggal Pelunasan</CTableHeaderCell>
                  </CTableRow>
                </CTableHead>
                <CTableBody>
                  <CTableRow v-for="(termin, index) in termins" :key="termin.id">
                    <CTableDataCell>{{ index + 1 }}</CTableDataCell>
                    <CTableDataCell>{{ termin.nama_termin }}</CTableDataCell>
                    <CTableDataCell class="text-end">Rp {{ formatCurrency(termin.nilai_termin) }}</CTableDataCell>
                    <CTableDataCell class="text-end">{{ termin.dp_percentage }}%</CTableDataCell>
                    <CTableDataCell class="text-end">Rp {{ formatCurrency(termin.nilai_dp) }}</CTableDataCell>
                    <CTableDataCell class="text-end">Rp {{ formatCurrency(termin.nilai_pelunasan) }}</CTableDataCell>
                    <CTableDataCell>
                      <CBadge :color="getTerminStatusColor(termin.status_termin)">
                            {{ termin.status_termin }}
                      </CBadge>
                    </CTableDataCell>
                    <CTableDataCell>{{ formatDate(termin.tanggal_dp) }}</CTableDataCell>
                    <CTableDataCell>{{ formatDate(termin.tanggal_pelunasan) }}</CTableDataCell>
                  </CTableRow>
                </CTableBody>
                <CTableFoot>
                  <CTableRow>
                    <CTableDataCell colspan="2" class="text-end fw-bold">Total Termin</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalTermins) }}</CTableDataCell>
                    <CTableDataCell colspan="2" class="text-end fw-bold">Total DP</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalDP) }}</CTableDataCell>
                    <CTableDataCell colspan="2" class="text-end fw-bold">Total Pelunasan</CTableDataCell>
                    <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalPelunasan) }}</CTableDataCell>
                  </CTableRow>
                </CTableFoot>
              </CTable>
            </CCardBody>
          </CCard>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import Swal from 'sweetalert2';

const route = useRoute();
const proyek = ref(null);
const purchases = ref([]);
const termins = ref([]);
const loading = ref(true);
const error = ref('');

// Fetch project details
const fetchProyek = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get(`/api/proyeks/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.data) {
    proyek.value = response.data;
    }
  } catch (err) {
    console.error('Error fetching project:', err);
    error.value = 'Gagal memuat data proyek';
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.value
    });
  }
};

// Fetch purchases
const fetchPurchases = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/purchasematerials', {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: route.params.id }
    });

    if (response.data && response.data.purchases) {
      purchases.value = response.data.purchases;
    }
  } catch (err) {
    console.error('Error fetching purchases:', err);
    error.value = 'Gagal memuat data pembelian';
  }
};

// Fetch termins
const fetchTermins = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/termins', {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: route.params.id }
    });

    if (response.data && response.data.data) {
      termins.value = response.data.data;
    }
  } catch (err) {
    console.error('Error fetching termins:', err);
    error.value = 'Gagal memuat data termin';
  }
};

// Update computed properties for purchase totals
const totalMaterial = computed(() => {
  return purchases.value
    .filter(p => !p.is_service)
    .reduce((sum, p) => sum + Number(p.total_harga), 0);
});

const totalJasa = computed(() => {
  return purchases.value
    .filter(p => {
      if (!p.is_service) return false;
      const unitName = p.unit?.unit_name?.toLowerCase();
      return unitName === 'jasa';
    })
    .reduce((sum, p) => sum + Number(p.total_harga), 0);
});

const totalJasaLain = computed(() => {
  return purchases.value
    .filter(p => {
      if (!p.is_service) return false;
      const unitName = p.unit?.unit_name?.toLowerCase();
      return ['set', 'transaksi'].includes(unitName);
    })
    .reduce((sum, p) => sum + Number(p.total_harga), 0);
});

// Update total purchases to include all types
const totalPurchases = computed(() => {
  return totalMaterial.value + totalJasa.value + totalJasaLain.value;
});

// Computed properties for totals
const totalTermins = computed(() => {
  return termins.value.reduce((sum, termin) => sum + Number(termin.nilai_termin), 0);
});

const totalDP = computed(() => {
  return termins.value.reduce((sum, termin) => {
    if (termin.status_termin === 'Lunas') {
      return sum + Number(termin.nilai_termin);
    }
    return sum + Number(termin.nilai_dp);
  }, 0);
});

const totalPelunasan = computed(() => {
  return termins.value.reduce((sum, termin) => {
    if (termin.status_termin === 'Lunas') {
      return 0;
    }
    return sum + Number(termin.nilai_pelunasan);
  }, 0);
});

// Helper functions
const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID');
};

const getStatusColor = (status) => {
  const colors = {
    'Berjalan': 'warning',
    'Selesai': 'success',
    'Batal': 'danger'
  };
  return colors[status] || 'secondary';
};

const getTerminStatusColor = (status) => {
  const colors = {
    'Belum Dibayar': 'secondary',
    'DP Dibayar': 'warning',
    'Lunas': 'success'
  };
  return colors[status] || 'secondary';
};

// Initial data fetching
onMounted(async () => {
  try {
    await Promise.all([
      fetchProyek(),
      fetchPurchases(),
      fetchTermins()
    ]);
  } catch (err) {
    console.error('Error loading data:', err);
    error.value = 'Gagal memuat data';
  } finally {
    loading.value = false;
  }
});
</script> 

<style scoped>
.c-list-group-item {
  padding: 1rem;
}

.c-list-group-item strong {
  min-width: 150px;
}

.table-responsive {
  overflow-x: auto;
}

.text-end {
  text-align: right;
}

.fw-bold {
  font-weight: bold;
}

/* Add styles for different purchase types */
.service-row {
  background-color: #e8f4ff !important;
}

.service-other-row {
  background-color: #fff3e0 !important;
}

.material-row {
  background-color: #ffffff !important;
}

.table-primary {
  background-color: #cfe2ff !important;
}
</style> 