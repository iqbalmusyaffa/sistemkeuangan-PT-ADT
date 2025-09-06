<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-chart-line" /> Laporan Laba Rugi
          <div class="float-end">
            <CButton color="success" @click="exportReport('excel')">Export Excel</CButton>
            <CButton color="danger" @click="exportReport('pdf')">Export PDF</CButton>
          </div>
        </CCardHeader>
        <CCardBody>
<CForm class="mb-4 row" @submit.prevent="fetchReports">
  <CCol :md="3">
    <CFormSelect v-model="filter.proyek_id" label="Proyek">
      <option value="">Semua</option>
      <option v-for="p in proyeks" :key="p.id" :value="p.id">{{ p.nama_proyek }}</option>
    </CFormSelect>
  </CCol>
  <CCol :md="3">
    <CFormInput v-model="filter.start_date" type="date" label="Dari Tanggal" />
  </CCol>
  <CCol :md="3">
    <CFormInput v-model="filter.end_date" type="date" label="Sampai Tanggal" />
  </CCol>
  <CCol :md="3">
    <CFormSelect v-model="filter.period_type" label="Jenis Periode">
      <option disabled value="">Pilih Periode</option>
      <option value="weekly">Mingguan</option>
      <option value="monthly">Bulanan</option>
      <option value="yearly">Tahunan</option>
    </CFormSelect>
  </CCol>
  <CCol :md="12" class="d-flex justify-content-end mt-2">
    <CButton type="submit" color="info" class="me-2">Filter</CButton>
    <CButton color="primary" @click="generateReport">Generate</CButton>
  </CCol>
</CForm>


          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div class="w-100">
            <table ref="profitTableRef" class="display nowrap"></table>
          </div>

          <div class="text-end mt-3">
            <strong>Total Laba/Rugi: {{ formatCurrency(totalProfit) }}</strong>
          </div>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import axios from 'axios';
import $ from 'jquery'
import Swal from 'sweetalert2'
import 'datatables.net-dt/css/dataTables.dataTables.min.css'
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'
import 'datatables.net'
import 'datatables.net-responsive'

const reports = ref([])
const proyeks = ref([])
const profitTableRef = ref(null)
const error = ref('')
const loading = ref(false);
const filter = ref({
  proyek_id: sessionStorage.getItem('profit_proyek_id') || '',
  start_date: sessionStorage.getItem('profit_start_date') || '',
  end_date: sessionStorage.getItem('profit_end_date') || '',
  period_type: sessionStorage.getItem('profit_period_type') || '',
})

watch(filter, (newVal) => {
  sessionStorage.setItem('profit_proyek_id', newVal.proyek_id)
  sessionStorage.setItem('profit_start_date', newVal.start_date)
  sessionStorage.setItem('profit_end_date', newVal.end_date)
  sessionStorage.setItem('profit_period_type', newVal.period_type)
}, { deep: true })


const totalProfit = computed(() => {
  return reports.value.reduce((sum, r) => sum + parseFloat(r.net_profit), 0)
})

const fetchReports = async () => {
  error.value = ''
  try {
    const token = sessionStorage.getItem('token');
    const { data } = await axios.get('/api/profit-loss-reports', {
      params: filter.value,
      headers: { Authorization: `Bearer ${token}` },
    });
    if (data.status === 'success') {
      reports.value = data.data.data
      nextTick(() => initDataTable())
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat data laporan'
  }
}

const fetchProyeks = async () => {
  loading.value = true;
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/proyeks", {
      headers: { Authorization: `Bearer ${token}` },
    });

    if (response.data && response.data.status === 'success') {
      proyeks.value = response.data.data;
    } else {
      proyeks.value = [];
      error.value = "Data tidak valid";
    }
  } catch (err) {
    console.error('Error fetching projects:', err);
    error.value = "Gagal memuat data proyek: " + (err.response?.data?.message || err.message);
    Swal.fire({
      icon: "error",
      title: "Oops...",
      text: error.value
    });
  } finally {
    loading.value = false;
  }
};

const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(profitTableRef.value)) {
    $(profitTableRef.value).DataTable().destroy()
  }

  $(profitTableRef.value).DataTable({
    data: reports.value,
    columns: [
      { title: 'No', data: null, render: (d, t, r, m) => m.row + 1 },
      { title: 'Proyek', data: null, render: (data, type, row) => row.proyek?.nama_proyek || 'N/A' },
      { title: 'Periode', data: 'period_type' },
      { title: 'Mulai', data: 'start_date' },
      { title: 'Selesai', data: 'end_date' },
      {
        title: 'Pendapatan',
        data: 'total_income',
        render: d => formatCurrency(d)
      },
      {
        title: 'Pengeluaran',
        data: 'total_expense',
        render: d => formatCurrency(d)
      },
      {
        title: 'Laba/Rugi',
        data: 'net_profit',
        render: d => `<span class="${d >= 0 ? 'text-success' : 'text-danger'}">${formatCurrency(d)}</span>`
      }
    ],
    scrollX: true,
    pageLength: 10
  })
}
const exportReport = async (format) => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get(`/api/profit-loss-reports/export/${format}`, {
      params: {
        proyek_id: filter.value.proyek_id,
        start_date: filter.value.start_date,
        end_date: filter.value.end_date
      },
      headers: {
        Authorization: `Bearer ${token}`
      },
      responseType: 'blob'
    });

    const blob = new Blob([response.data], {
      type: format === 'pdf'
        ? 'application/pdf'
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });

    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    const ext = format === 'pdf' ? 'pdf' : 'xlsx'; // FIX
    link.href = url;
    link.setAttribute('download', `laporan_laba_rugi.${ext}`); // FIX
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);

  } catch (err) {
    console.error('Export error:', err);
    Swal.fire('Gagal', `Gagal mengekspor laporan ke ${format.toUpperCase()}`, 'error');
  }
};


const generateReport = async () => {
  if (!filter.value.period_type || !filter.value.start_date || !filter.value.end_date) {
    Swal.fire('Gagal', 'Silakan lengkapi Jenis Periode dan Tanggal terlebih dahulu.', 'warning');
    return;
  }

  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.post('/api/profit-loss-reports/generate', filter.value, {
      headers: { Authorization: `Bearer ${token}` },
    });

    if (response.data.status === 'success' || response.data.report) {
      Swal.fire('Berhasil', 'Laporan berhasil dibuat.', 'success');
      fetchReports(); // refresh setelah generate
    } else {
      Swal.fire('Gagal', response.data.message || 'Gagal membuat laporan.', 'error');
    }
  } catch (err) {
    console.error('Error generating report:', err);
    Swal.fire('Gagal', err.response?.data?.message || 'Terjadi kesalahan saat membuat laporan.', 'error');
  }
};


const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR'
  }).format(val)
}

onMounted(() => {
  const token = sessionStorage.getItem('token');
  if (token) {
    fetchProyeks();
    fetchReports();
  } else {
    Swal.fire('Perhatian', 'Silakan login untuk mengakses data laporan.', 'warning');
  }
});
</script>

<style scoped>
.w-100 {
  width: 100%;
  overflow-x: auto;
}
</style>
