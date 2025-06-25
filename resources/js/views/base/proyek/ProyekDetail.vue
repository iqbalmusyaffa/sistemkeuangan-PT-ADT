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
                  <CTable striped>
                    <CTableBody>
                      <CTableRow>
                        <CTableHeaderCell>Nama Customer</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.nama_customer || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Nama Proyek</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.nama_proyek || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Nama Perusahaan</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.nama_perusahaan || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Alamat</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.alamat || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>No. Telepon</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.no_telp || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Email</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.email || '-' }}</CTableDataCell>
                      </CTableRow>
                    </CTableBody>
                  </CTable>
                </CCol>
                <CCol md="6">
                  <CTable striped>
                    <CTableBody>
                      <CTableRow>
                        <CTableHeaderCell>Lokasi Proyek</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.lokasi || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Anggaran Kontrak</CTableHeaderCell>
                        <CTableDataCell>Rp {{ formatCurrency(proyek.anggaran_kontrak) }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Tanggal Mulai</CTableHeaderCell>
                        <CTableDataCell>{{ formatDate(proyek.tanggal_mulai) }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Tanggal Selesai</CTableHeaderCell>
                        <CTableDataCell>{{ formatDate(proyek.tanggal_selesai) }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Status Proyek</CTableHeaderCell>
                        <CTableDataCell>
                          <CBadge :color="getStatusColor(proyek.status_project)">
                            {{ proyek.status_project }}
                          </CBadge>
                        </CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Deskripsi</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.deskripsi || '-' }}</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Target Progress</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.target_progress || '-' }}%</CTableDataCell>
                      </CTableRow>
                      <CTableRow>
                        <CTableHeaderCell>Progress Aktual</CTableHeaderCell>
                        <CTableDataCell>{{ proyek.progress || '-' }}%</CTableDataCell>
                      </CTableRow>
                    </CTableBody>
                  </CTable>
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
              <div class="table-responsive">
                <CTable hover>
                  <CTableHead>
                    <CTableRow>
                      <CTableHeaderCell class="text-center" style="width: 5%">No</CTableHeaderCell>
                      <CTableHeaderCell style="width: 20%">Item</CTableHeaderCell>
                      <CTableHeaderCell style="width: 10%">Merek</CTableHeaderCell>
                      <CTableHeaderCell style="width: 10%">Tipe</CTableHeaderCell>
                      <CTableHeaderCell style="width: 10%">Unit</CTableHeaderCell>
                      <CTableHeaderCell style="width: 15%">Kategori</CTableHeaderCell>
                      <CTableHeaderCell class="text-center" style="width: 10%">Jumlah</CTableHeaderCell>
                      <CTableHeaderCell class="text-end" style="width: 10%">Harga</CTableHeaderCell>
                      <CTableHeaderCell class="text-end" style="width: 10%">Total</CTableHeaderCell>
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
                      <CTableDataCell class="text-center">{{ index + 1 }}</CTableDataCell>
                      <CTableDataCell>{{ purchase.item }}</CTableDataCell>
                      <CTableDataCell>{{ purchase.is_service ? '-' : (purchase.merek?.name || '-') }}</CTableDataCell>
                      <CTableDataCell>{{ purchase.type || '-' }}</CTableDataCell>
                      <CTableDataCell>{{ purchase.unit?.unit_name || '-' }}</CTableDataCell>
                      <CTableDataCell>{{ purchase.is_service ? (purchase.service_category?.nama_kategori || '-') : (purchase.category?.nama_kategori || '-') }}</CTableDataCell>
                      <CTableDataCell class="text-center">{{ purchase.qty }}</CTableDataCell>
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
              </div>
            </CCardBody>
          </CCard>

          <!-- Termins Section -->
          <CCard class="mb-4">
            <CCardHeader>
              <strong>Daftar Termin</strong>
            </CCardHeader>
            <CCardBody>
              <div class="table-responsive">
                <CTable hover>
                  <CTableHead>
                    <CTableRow>
                      <CTableHeaderCell class="text-center" style="width: 5%">No</CTableHeaderCell>
                      <CTableHeaderCell style="width: 15%">Nama Termin</CTableHeaderCell>
                      <CTableHeaderCell class="text-end" style="width: 15%">Nilai Termin</CTableHeaderCell>
                      <CTableHeaderCell class="text-center" style="width: 10%">DP (%)</CTableHeaderCell>
                      <CTableHeaderCell class="text-end" style="width: 15%">Nilai DP</CTableHeaderCell>
                      <CTableHeaderCell class="text-end" style="width: 15%">Nilai Pelunasan</CTableHeaderCell>
                      <CTableHeaderCell class="text-center" style="width: 10%">Status</CTableHeaderCell>
                      <CTableHeaderCell class="text-center" style="width: 10%">Tanggal DP</CTableHeaderCell>
                      <CTableHeaderCell class="text-center" style="width: 10%">Tanggal Pelunasan</CTableHeaderCell>
                    </CTableRow>
                  </CTableHead>
                  <CTableBody>
                    <CTableRow v-for="(termin, index) in termins" :key="termin.id">
                      <CTableDataCell class="text-center">{{ index + 1 }}</CTableDataCell>
                      <CTableDataCell>{{ termin.nama_termin }}</CTableDataCell>
                      <CTableDataCell class="text-end">Rp {{ formatCurrency(termin.nilai_termin) }}</CTableDataCell>
                      <CTableDataCell class="text-center">{{ termin.dp_percentage }}%</CTableDataCell>
                      <CTableDataCell class="text-end">Rp {{ formatCurrency(termin.nilai_dp) }}</CTableDataCell>
                      <CTableDataCell class="text-end">Rp {{ formatCurrency(termin.nilai_pelunasan) }}</CTableDataCell>
                      <CTableDataCell class="text-center">
                        <CBadge :color="getTerminStatusColor(termin.status_termin)">
                          {{ termin.status_termin }}
                        </CBadge>
                      </CTableDataCell>
                      <CTableDataCell class="text-center">{{ formatDate(termin.tanggal_dp) }}</CTableDataCell>
                      <CTableDataCell class="text-center">{{ formatDate(termin.tanggal_pelunasan) }}</CTableDataCell>
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
              </div>
            </CCardBody>
          </CCard>

          <!-- Invoice Section -->
          <CCard class="mb-4">
            <CCardHeader>
              <strong>Daftar Invoice</strong>
            </CCardHeader>
            <CCardBody>
              <div class="table-responsive">
                <CTable hover>
                  <CTableHead>
                    <CTableRow>
                      <CTableHeaderCell class="text-center" style="width: 5%">No</CTableHeaderCell>
                      <CTableHeaderCell style="width: 20%">Nomor Invoice</CTableHeaderCell>
                      <CTableHeaderCell style="width: 15%">Tanggal</CTableHeaderCell>
                      <CTableHeaderCell class="text-end" style="width: 15%">Total</CTableHeaderCell>
                      <CTableHeaderCell class="text-center" style="width: 15%">Status</CTableHeaderCell>
                    </CTableRow>
                  </CTableHead>
                  <CTableBody>
                    <CTableRow v-for="(invoice, index) in invoices" :key="invoice.id">
                      <CTableDataCell class="text-center">{{ index + 1 }}</CTableDataCell>
                      <CTableDataCell>{{ invoice.invoice_number }}</CTableDataCell>
                      <CTableDataCell>{{ formatDate(invoice.invoice_date) }}</CTableDataCell>
                      <CTableDataCell class="text-end">Rp {{ formatCurrency(invoice.total) }}</CTableDataCell>
                      <CTableDataCell class="text-center">
                        <CBadge :color="getInvoiceStatusColor(invoice.status)">
                          {{ invoice.status }}
                        </CBadge>
                      </CTableDataCell>
                    </CTableRow>
                  </CTableBody>
                  <CTableFoot>
                    <CTableRow>
                      <CTableDataCell colspan="3" class="text-end fw-bold">Total Invoice</CTableDataCell>
                      <CTableDataCell class="text-end fw-bold">Rp {{ formatCurrency(totalInvoices) }}</CTableDataCell>
                    </CTableRow>
                  </CTableFoot>
                </CTable>
              </div>
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
const invoices = ref([]);
const loading = ref(true);
const error = ref('');

// Fetch project details
const fetchProyek = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get(`/api/proyeks/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.data && response.data.data) {
      proyek.value = response.data.data;
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
    console.log('Fetching purchases for project:', route.params.id);
    const response = await axios.get('/api/purchasematerials', {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: route.params.id }
    });

    console.log('Purchases response:', response.data);

    if (response.data && Array.isArray(response.data.data)) {
      purchases.value = response.data.data;
      console.log('Set purchases to:', purchases.value);
    } else {
      console.log('No purchase data found');
      purchases.value = [];
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
    console.log('Fetching termins for project:', route.params.id);
    const response = await axios.get(`/api/proyeks/${route.params.id}/termins`, {
      headers: { Authorization: `Bearer ${token}` }
    });

    if (response.data && response.data.data) {
      // Urutkan berdasarkan angka dalam nama termin (misal: "ini termin 1", "ini termin 2", dst)
      termins.value = response.data.data.sort((a, b) => {
        const numA = parseInt(a.nama_termin.match(/\d+/)) || 0;
        const numB = parseInt(b.nama_termin.match(/\d+/)) || 0;
        return numA - numB; // Ascending: Termin 1, Termin 2, ...
      });

      console.log('Set sorted termins to:', termins.value);
    } else {
      console.log('No termin data found');
      termins.value = [];
    }
  } catch (err) {
    console.error('Error fetching termins:', err);
    error.value = 'Gagal memuat data termin';
  }
};


// Fetch invoices
const fetchInvoices = async () => {
  try {
    const token = sessionStorage.getItem('token');
    console.log('Fetching invoices for project:', route.params.id);
    const response = await axios.get(`/api/proyeks/${route.params.id}/invoices`, {
      headers: { Authorization: `Bearer ${token}` }
    });

    console.log('Invoices response:', response.data);

    if (Array.isArray(response.data)) {
      invoices.value = response.data.map(invoice => ({
        ...invoice,
        total: Number(invoice.total_amount) || 0 // Use total_amount for invoice total
      }));
      console.log('Set invoices to:', invoices.value);
    } else {
      console.log('No invoice data found');
      invoices.value = [];
    }
  } catch (err) {
    console.error('Error fetching invoices:', err);
    error.value = 'Gagal memuat data invoice';
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

const totalInvoices = computed(() => {
  return invoices.value.reduce((sum, invoice) => sum + Number(invoice.total), 0);
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

const getInvoiceStatusColor = (status) => {
  const colors = {
    'Belum Dibayar': 'secondary',
    'Dibayar': 'success',
    'Tertunda': 'warning'
  };
  return colors[status] || 'secondary';
};

// Initial data fetching
onMounted(async () => {
  try {
    console.log('Initializing data fetch for project details');
    await Promise.all([
      fetchProyek(),
      fetchPurchases(),
      fetchTermins(),
      fetchInvoices()
    ]);
    console.log('Data fetch completed');
  } catch (err) {
    console.error('Error loading data:', err);
    error.value = 'Gagal memuat data';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.table-responsive {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.text-end {
  text-align: right !important;
}

.text-center {
  text-align: center !important;
}

.fw-bold {
  font-weight: bold !important;
}

/* Purchase row styles */
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

/* Table header styles */
.table thead th {
  vertical-align: middle;
  border-bottom: 2px solid #dee2e6;
  white-space: nowrap;
}

/* Table cell padding */
.table td, .table th {
  padding: 0.75rem;
  vertical-align: middle;
}

/* Badge styles */
.badge {
  padding: 0.5em 0.75em;
  font-size: 0.875em;
  font-weight: 600;
  border-radius: 0.25rem;
}

/* Card styles */
.card {
  margin-bottom: 1.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  padding: 0.75rem 1.25rem;
}

.card-body {
  padding: 1.25rem;
}
</style>
