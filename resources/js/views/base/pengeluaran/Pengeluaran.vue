<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-dollar" /> Data Pengeluaran
            <CButton color="primary" class="float-end" @click="openModal('tambah')" :disabled="!selectedProject">
              Tambah Pengeluaran
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
                  @change="handleProjectChange"
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
              <!-- Ganti Project Button -->
              <CCol md="6" class="text-end">
                <CButton
                  color="secondary"
                  v-if="selectedProject"
                  @click="changeProject"
                  class="mt-2"
                >
                  Ganti Proyek
                </CButton>
              </CCol>
            </CRow>

            <!-- Alert when no project selected -->
            <div v-if="!selectedProject" class="alert alert-info">
              Silakan pilih proyek terlebih dahulu untuk melihat data pengeluaran
            </div>

            <!-- DataTable -->
            <div v-if="selectedProject" class="w-100">
              <table ref="dataTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Tambah/Edit -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Proyek</CFormLabel>
                <select v-model="form.proyek_id" class="form-select" required>
                  <option value="">Pilih Proyek</option>
                  <optgroup v-for="(projectGroup, customer) in groupedProjects"
                           :key="customer"
                           :label="customer">
                    <option v-for="project in projectGroup"
                            :key="project.id"
                            :value="project.id">
                      {{ project.nama_proyek }}
                    </option>
                  </optgroup>
                </select>
              </CCol>
              <CCol md="6">
                <CFormLabel>Jenis Kategori</CFormLabel>
                <CFormSelect v-model="form.category_type" class="mb-3" required>
                  <option value="">Pilih Jenis Kategori</option>
                  <option value="material">Material</option>
                  <option value="service">Jasa</option>
                </CFormSelect>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Kategori</CFormLabel>
                <CFormSelect
                  v-model="selectedCategory"
                  required
                >
                  <option value="">Pilih Kategori</option>
                  <template v-if="form.category_type === 'material'">
                    <option v-for="category in categories"
                            :key="category.id"
                            :value="category.id">
                      {{ category.nama_kategori }}
                    </option>
                  </template>
                  <template v-else>
                    <option v-for="category in serviceCategories"
                            :key="category.id"
                            :value="category.id">
                      {{ category.nama_kategori }}
                    </option>
                  </template>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel>Tanggal Transaksi</CFormLabel>
                <CFormInput
                  type="date"
                  v-model="form.transaction_date"
                  required
                />
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Jumlah</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    v-model="form.displayAmount"
                    @input="handleAmountInput"
                    required
                    placeholder="0"
                  />
                </div>
              </CCol>
              <CCol md="6">
                <CFormLabel>Status</CFormLabel>
                <CFormSelect v-model="form.status" required :disabled="true">
                  <option value="Pending">Pending</option>
                  <option value="Approved">Approved</option>
                  <option value="Rejected">Rejected</option>
                  <option value="Lunas">Lunas</option>
                  <option value="Partial">Partial</option>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel>Bukti Pembayaran</CFormLabel>
                <CFormInput
                  type="file"
                  accept="image/png,image/jpeg,application/pdf"
                  @change="handleBuktiChange"
                  :disabled="true"
                />
                <div v-if="form.buktiName" class="mt-1 text-success">
                  File terpilih: {{ form.buktiName }}
                </div>
                <div v-if="form.buktiUrl" class="mt-2">
                  <a :href="form.buktiUrl" target="_blank">Lihat Bukti Pembayaran</a>
                </div>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Metode Pembayaran</CFormLabel>
                <CFormSelect v-model="form.payment_method" required>
                  <option value="">Pilih Metode Pembayaran</option>
                  <option value="Cash">Cash</option>
                  <option value="Transfer">Transfer Bank</option>
                  <option value="Debit">Kartu Debit</option>
                  <option value="Credit">Kartu Kredit</option>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel>Dana Persiapan</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    v-model="form.displayPreparedFund"
                    @input="handlePreparedFundInput"
                    placeholder="0"
                  />
                </div>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel>Deskripsi</CFormLabel>
                <CFormTextarea
                  v-model="form.description"
                  rows="3"
                  required
                  placeholder="Masukkan deskripsi pengeluaran..."
                />
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="12">
                <CFormSwitch
                  v-model="form.isManualKode"
                  label="Input kode transaksi manual?"
                />
              </CCol>
            </CRow>

            <CRow v-if="form.isManualKode" class="mb-3">
              <CCol md="6">
                <CFormLabel>Kode Transaksi</CFormLabel>
                <CFormInput
                  v-model="form.kode_transaksi"
                  placeholder="Contoh: EXP/2024/001"
                  :required="form.isManualKode"
                />
              </CCol>
            </CRow>

            <div class="d-flex justify-content-end gap-2">
              <CButton color="secondary" @click="closeModal">
                Batal
              </CButton>
              <CButton type="submit" color="primary">
                {{ modalButtonText }}
              </CButton>
            </div>

          </CForm>
        </CModalBody>
      </CModal>



    <!-- Modal Update Status -->
    <CModal :visible="showStatusModal" @close="closeStatusModal" title="Update Status Pengeluaran">
      <CModalBody>
        <div class="mb-2">
          <strong>Status Saat Ini:</strong>
          <span class="badge bg-info ms-2">{{ statusForm.status }}</span>
        </div>
        <CForm @submit.prevent="handleStatusSubmit">
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel>Status Baru</CFormLabel>
              <CFormSelect v-model="statusForm.status" required>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Lunas">Lunas</option>
                <option value="Partial">Partial</option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel>Bukti Pembayaran</CFormLabel>
              <CFormInput
                type="file"
                accept="image/png,image/jpeg,application/pdf"
                @change="handleStatusBuktiChange"
              />
              <div v-if="statusForm.buktiName" class="mt-1 text-success">
                File terpilih: {{ statusForm.buktiName }}
              </div>
              <div v-if="statusForm.buktiUrl" class="mt-2">
                <a :href="statusForm.buktiUrl" target="_blank">Lihat Bukti Pembayaran</a>
              </div>
            </CCol>
          </CRow>
          <div class="d-flex justify-content-end gap-2">
            <CButton color="secondary" @click="closeStatusModal">
              Batal
            </CButton>
            <CButton type="submit" color="primary">
              Update Status
            </CButton>
          </div>
        </CForm>
      </CModalBody>
    </CModal>
    <CModal :visible="showUploadModal" @close="showUploadModal = false" title="Upload Bukti Pembayaran">
  <CModalBody>
    <CForm @submit.prevent="submitUploadBukti">
      <CFormLabel for="upload_bukti">Pilih File (PDF/JPG/PNG)</CFormLabel>
      <CFormInput
        id="upload_bukti"
        type="file"
        accept="image/png,image/jpeg,application/pdf"
        @change="handleUploadBuktiChange"
        required
      />
      <div class="text-muted mt-2">Ukuran maksimal 2MB</div>
      <div class="mt-3 d-flex justify-content-end">
        <CButton color="secondary" @click="showUploadModal = false">Batal</CButton>
        <CButton type="submit" color="primary">Upload</CButton>
      </div>
    </CForm>
  </CModalBody>
</CModal>

<!-- Modal Preview Bukti -->
<CModal :visible="showPreviewModal" @close="showPreviewModal = false" title="Preview Bukti Pembayaran" size="xl">
  <CModalBody>
    <div v-if="previewFileUrl">
      <template v-if="isImageFile(previewFileUrl)">
        <img :src="previewFileUrl" alt="Bukti Pembayaran" class="img-fluid w-100" />
      </template>
      <template v-else-if="isPdfFile(previewFileUrl)">
        <iframe :src="previewFileUrl" width="100%" height="600px" frameborder="0"></iframe>
      </template>
      <template v-else>
        <p>Format file tidak bisa ditampilkan. <a :href="previewFileUrl" target="_blank">Klik di sini untuk membuka file</a>.</p>
      </template>
    </div>
  </CModalBody>
</CModal>

  </CRow>
</template>

  <script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import $ from 'jquery'
import Swal from 'sweetalert2'

import "datatables.net-dt/css/dataTables.dataTables.min.css"
import "datatables.net-responsive-dt/css/responsive.dataTables.min.css"
import "datatables.net-responsive-dt"

  const router = useRouter()
  const dataTableRef = ref(null)
const showPreviewModal = ref(false);
const previewFileUrl = ref('');

const isImageFile = (url) => /\.(jpg|jpeg|png)$/i.test(url);
const isPdfFile = (url) => /\.pdf$/i.test(url);

const openPreviewModal = (filePath) => {
  previewFileUrl.value = `/storage/${filePath}`;
  showPreviewModal.value = true;
};

// --- Status Modal State and Logic ---
const showStatusModal = ref(false)
const statusForm = ref({
  status: '',
  bukti: null,
  buktiName: '',
  buktiUrl: '',
  purchase_material_id: ''
})
const updatingStatusId = ref(null)
const showUploadModal = ref(false);
const selectedExpenseIdForUpload = ref(null);
const selectedUploadFile = ref(null);


const openStatusModal = async (expenseOrId) => {
  // Accept either an expense object or just an ID
  const id = typeof expenseOrId === 'object' ? expenseOrId.id : expenseOrId;
  updatingStatusId.value = id;
  try {
    const token = sessionStorage.getItem('token');
    const res = await axios.get(`/api/expenses/${id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    const expense = res.data.data;
    statusForm.value = {
      status: expense.status || '',
      bukti: null,
      buktiName: '',
      buktiUrl: expense.bukti ? `/storage/${expense.bukti}` : '',
      purchase_material_id: expense.purchase_material_id || ''
    };
    showStatusModal.value = true;
  } catch (err) {
    Swal.fire('Error', 'Gagal mengambil data pengeluaran terbaru', 'error');
  }
}

const closeStatusModal = () => {
  showStatusModal.value = false
}
const handleUploadBuktiChange = (e) => {
  selectedUploadFile.value = e.target.files[0];
};
const submitUploadBukti = async () => {
  if (!selectedExpenseIdForUpload.value || !selectedUploadFile.value) {
    return Swal.fire('Error', 'ID atau file tidak valid', 'error');
  }

  const formData = new FormData();
  formData.append('bukti_pembayaran', selectedUploadFile.value);

  try {
    const token = sessionStorage.getItem('token');
    await axios.post(`/api/expenses/${selectedExpenseIdForUpload.value}/upload-bukti`, formData, {
      headers: { Authorization: `Bearer ${token}` }
    });

    Swal.fire('Sukses', 'Bukti pembayaran berhasil diupload', 'success');
    showUploadModal.value = false;
    await fetchData();
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || 'Upload gagal', 'error');
  }
};

const handleStatusBuktiChange = (e) => {
  const file = e.target.files[0]
  statusForm.value.bukti = file
  statusForm.value.buktiName = file ? file.name : ''
}

const handleStatusSubmit = async () => {
  if (["Lunas", "Partial"].includes(statusForm.value.status) && !statusForm.value.bukti && !statusForm.value.buktiUrl) {
    return Swal.fire('Peringatan', 'Bukti pembayaran wajib diupload jika status Lunas/Partial', 'warning')
  }
  try {
    const token = sessionStorage.getItem('token')
    const fd = new FormData()
    fd.append('status', statusForm.value.status)
    if (statusForm.value.bukti) {
      fd.append('bukti', statusForm.value.bukti)
    }
    // Use plain POST for update-status (avoid 405 error)
    await axios.post(`/api/expenses/${updatingStatusId.value}/update-status`, fd, {
      headers: { Authorization: `Bearer ${token}` }
    })
    Swal.fire('Berhasil', 'Status pengeluaran berhasil diperbarui', 'success')
    showStatusModal.value = false
    await fetchData()
  } catch (err) {
    Swal.fire('Error', 'Gagal memperbarui status', 'error')
  }
}

  const projects = ref([])
  const categories = ref([])
  const expenses = ref([])

  const error = ref('')
  const loading = ref(false)

  const showModal = ref(false)
  const modalTitle = ref('Tambah Pengeluaran')
  const modalButtonText = ref('Simpan')
  const modalMode = ref('tambah')
  const editingId = ref(null)

  const form = ref({
    proyek_id: '',
    category_type: '',
    amount: 0,
    displayAmount: '',
    description: '',
    transaction_date: '',
    status: 'Pending',
    payment_method: '',
    prepared_fund: 0,
    displayPreparedFund: '',
    kode_transaksi: '',
    isManualKode: false,
    bukti: null,
    buktiName: '',
    buktiUrl: ''
  })

  const handleBuktiChange = (e) => {
    const file = e.target.files[0];
    form.value.bukti = file;
    form.value.buktiName = file ? file.name : '';
  }

  const serviceCategories = ref([])

  const groupedProjects = computed(() => {
    const grouped = {};
    projects.value.forEach(project => {
      if (!grouped[project.nama_customer]) {
        grouped[project.nama_customer] = [];
      }
      grouped[project.nama_customer].push(project);
    });
    return grouped;
  })

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value);
  }

  const unformatCurrency = (value) => {
    return Number(value.replace(/[^\d,-]/g, ''));
  }

  const handleAmountInput = (event) => {
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.amount = unformattedValue;
    form.value.displayAmount = formatCurrency(unformattedValue);
  }

  const handlePreparedFundInput = (event) => {
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.prepared_fund = unformattedValue;
    form.value.displayPreparedFund = formatCurrency(unformattedValue);
  }

  const selectedCategory = computed({
    get: () => {
      return form.value.category_type === 'material'
        ? form.value.category_id
        : form.value.service_category_id;
    },
    set: (value) => {
      if (form.value.category_type === 'material') {
        form.value.category_id = value;
        form.value.service_category_id = null;
      } else {
        form.value.service_category_id = value;
        form.value.category_id = null;
      }
    }
  });

  const selectedProject = ref('');
  const selectedProjectDetails = ref(null);

  const fetchData = async () => {
    loading.value = true;
    try {
      const token = sessionStorage.getItem('token');

      const [projectRes, categoryRes, serviceCategoryRes] = await Promise.all([
        axios.get('/api/proyeks', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/categories', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/service-categories', { headers: { Authorization: `Bearer ${token}` } })
      ]);

      projects.value = projectRes.data.data || projectRes.data;

      categories.value = categoryRes.data.data || categoryRes.data;
      serviceCategories.value = serviceCategoryRes.data.data || serviceCategoryRes.data;

      if (selectedProject.value) {

        await filterByProject();
      } else {

        expenses.value = [];
        if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
          $(dataTableRef.value).DataTable().destroy();
          $(dataTableRef.value).empty();
        }
      }
    } catch (e) {
      error.value = 'Gagal memuat data';
    } finally {
      loading.value = false;
    }
  };

  // Server-side DataTables with scroll
  const filterByProject = async () => {
    if (!selectedProject.value) {
      expenses.value = [];
      if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
        $(dataTableRef.value).DataTable().destroy();
        $(dataTableRef.value).empty();
      }
      return;
    }

    try {
      loading.value = true;
      const token = sessionStorage.getItem('token');

      // Get project details only (not expenses)
      const projectDetailsRes = await axios.get(`/api/proyeks/${selectedProject.value}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      selectedProjectDetails.value = projectDetailsRes.data;

      // Pastikan DataTable diinisialisasi ulang
      await nextTick();
      initDataTableServerSide();
    } catch (e) {
      error.value = 'Gagal memuat data pengeluaran proyek';
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Gagal memuat data pengeluaran. Silakan coba lagi.',
        confirmButtonText: 'OK'
      });
    } finally {
      loading.value = false;
    }
  };

  const changeProject = () => {
    selectedProject.value = '';
    selectedProjectDetails.value = null;
    expenses.value = [];
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().clear().destroy();
      $(dataTableRef.value).empty();
    }
  };

  // Inisialisasi DataTable server-side dengan scroll
  const initDataTableServerSide = () => {
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().clear().destroy();
      $(dataTableRef.value).empty();
    }

    $(dataTableRef.value).DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '/api/expenses/datatables',
        type: 'GET',
        headers: { Authorization: `Bearer ${sessionStorage.getItem('token')}` },
        data: function (d) {
          d.proyek_id = selectedProject.value;
        },
        dataSrc: function (json) {
          console.log('Data diterima dari server:', json);
          return json.data;
        }
      },
      columns: [
        {
          title: '<span class="fw-bold">No</span>',
          data: null,
          className: 'text-center align-middle',
          orderable: false,
          render: (data, type, row, meta) => meta.row + 1 + meta.settings._iDisplayStart
        },
        {
          title: '<span class="fw-bold">Tanggal</span>',
          data: 'transaction_date',
          className: 'text-center align-middle',
          render: (data) => {
            if (!data) return '-';
            const date = new Date(data);
            return date.toLocaleDateString('id-ID', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric'
            }).split('/').join('.');
          }
        },
        {
          title: '<span class="fw-bold">Kode</span>',
          data: 'kode_transaksi',
          className: 'text-center align-middle',
          render: (data) => data || '-'
        },
        {
          title: '<span class="fw-bold">Proyek</span>',
          data: 'proyek.nama_proyek',
          className: 'align-middle',
          render: (data, type, row) => row.proyek?.nama_proyek || 'N/A'
        },
        {
          title: '<span class="fw-bold">Kategori</span>',
          data: null,
          className: 'align-middle',
          render: (data, type, row) => {
    // Cek jika expense berasal dari purchase material (jasa)
    if (row.purchase_material && row.purchase_material.service_category) {
      return `<span class="badge bg-info text-dark me-1">Jasa</span>${row.purchase_material.service_category.nama_kategori}`;
    }
    // Pengecekan sebelumnya untuk service dan material
    if (row.is_service && row.service_category) {
      return `<span class="badge bg-info text-dark me-1">Jasa</span>${row.service_category.nama_kategori}`;
    }
    if (row.category_id || row.category) {
      return `<span class="badge bg-primary me-1">Material</span>${row.category?.nama_kategori || 'Material'}`;
    }
    return '<span class="text-muted">Unknown Category</span>';
  }

        },
        {
          title: '<span class="fw-bold">Jumlah</span>',
          data: 'amount',
          className: 'text-end align-middle',
          render: (data) => `Rp ${formatCurrency(Number(data) || 0)}`
        },
        {
          title: '<span class="fw-bold">Status</span>',
          data: 'status',
          className: 'text-center align-middle',
          render: (data) => {
            const statusClass = {
              'Pending': 'badge bg-secondary text-capitalize',
              'Approved': 'badge bg-success text-capitalize',
              'Rejected': 'badge bg-danger text-capitalize',
              'Lunas': 'badge bg-primary text-capitalize',
              'Partial': 'badge bg-warning text-dark text-capitalize'
            }[data] || 'badge bg-light text-dark';
            return `<span class="${statusClass}">${data ? data.charAt(0).toUpperCase() + data.slice(1).toLowerCase() : '-'}</span>`;
          }
        },
      {
  title: '<span class="fw-bold">Aksi</span>',
  data: null,
  className: 'text-center align-middle',
  orderable: false,
  render: (data, type, row) => {
    const buktiIcon = row.bukti_pembayaran ? 'bi-check-circle text-success' : 'bi-upload';
    const buktiLabel = row.bukti_pembayaran ? 'Ganti Bukti' : 'Upload Bukti';
    const previewBtn = row.bukti
      ? `<button class="btn btn-sm btn-info preview-bukti-btn me-1" data-bs-toggle="tooltip" title="Lihat Bukti" data-file="${row.bukti}">
          <i class="bi bi-eye-fill"></i>
        </button>`
      : '';

    const downloadBtn = row.bukti
      ? `<a href="/storage/${row.bukti}" download target="_blank" class="btn btn-sm btn-success me-1" data-bs-toggle="tooltip" title="Download Bukti">
          <i class="bi bi-download"></i>
        </a>`
      : '';
   return `
  <div class="btn-group">
    <button class="btn btn-sm btn-warning edit-btn me-1" data-bs-toggle="tooltip" title="Edit" data-id="${row.id}">
      <i class="bi bi-pencil"></i> Edit
    </button>
    <button class="btn btn-sm btn-danger delete-btn me-1" data-bs-toggle="tooltip" title="Hapus" data-id="${row.id}">
      <i class="bi bi-trash"></i> Hapus
    </button>
    <button class="btn btn-sm btn-secondary view-btn me-1" data-bs-toggle="tooltip" title="Detail" data-id="${row.id}">
      <i class="bi bi-eye"></i> Detail
    </button>
    <button class="btn btn-sm btn-secondary upload-bukti-btn me-1" data-bs-toggle="tooltip" title="${buktiLabel}" data-id="${row.id}">
      <i class="bi ${buktiIcon}"></i> ${buktiLabel}
    </button>
    ${previewBtn}
    ${downloadBtn}
  </div>
`;

  }

        },
      ],
      order: [[1, 'desc']],
       responsive: false,
      scrollX: true, 
      autoWidth: false,
      language: {
        "emptyTable": "Tidak ada data yang tersedia",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
        "infoFiltered": "(difilter dari _MAX_ total data)",
        "lengthMenu": "Tampilkan _MENU_ data",
        "loadingRecords": "Memuat...",
        "processing": "Memproses...",
        "search": "Cari:",
        "zeroRecords": "Tidak ditemukan data yang sesuai",
        // "paginate": {
        //   "first": "Pertama",
        //   "last": "Terakhir",
        //   "next": "Selanjutnya",
        //   "previous": "Sebelumnya"
        // }
      },
    //   dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex"f>>rtip',
      drawCallback: function() {
        // Attach event listeners for action buttons
        
        $(dataTableRef.value).off('click', '.edit-btn');
        $(dataTableRef.value).off('click', '.delete-btn');
        $(dataTableRef.value).off('click', '.view-btn');
        $(dataTableRef.value).off('click', '.status-btn');

        $(dataTableRef.value).on('click', '.edit-btn', async function () {
          const id = $(this).data('id');
          try {
            const token = sessionStorage.getItem('token');
            const res = await axios.get(`/api/expenses/${id}`, {
              headers: { Authorization: `Bearer ${token}` }
            });
            openModal('edit', res.data.data);
          } catch (err) {
            Swal.fire('Error', 'Gagal mengambil data pengeluaran', 'error');
          }
        });

        $(dataTableRef.value).on('click', '.delete-btn', function () {
          const id = $(this).data('id');
          handleDelete(id);
        });

        $(dataTableRef.value).on('click', '.view-btn', function () {
          const id = $(this).data('id');
          router.push(`/base/pengeluaran/${id}`);
        });

        $(dataTableRef.value).on('click', '.status-btn', function () {
          const id = $(this).data('id');
          openStatusModal(id);
        });
$(dataTableRef.value).on('click', '.upload-bukti-btn', function () {
  const id = $(this).data('id');
  selectedExpenseIdForUpload.value = id;
  selectedUploadFile.value = null;
  showUploadModal.value = true;
});
$(dataTableRef.value).on('click', '.preview-bukti-btn', function () {
  const file = $(this).data('file');
  if (file) openPreviewModal(file);
});

        // Enable Bootstrap tooltip if available
        if (window.bootstrap && window.bootstrap.Tooltip) {
          $(dataTableRef.value).find('[data-bs-toggle="tooltip"]').each(function() {
            new window.bootstrap.Tooltip(this);
          });
        }
      },
      stripeClasses: ["table-striped", "table-hover"]
    });
  }

  const openModal = (mode, expense = null) => {
    if (!selectedProject.value && mode === 'tambah') {
      Swal.fire({
        icon: 'warning',
        title: 'Perhatian',
        text: 'Silakan pilih proyek terlebih dahulu'
      });
      return;
    }

    modalMode.value = mode;
    if (mode === 'edit' && expense) {
      form.value = {
        proyek_id: expense.proyek_id,
        category_type: expense.service_category_id ? 'service' : 'material',
        amount: expense.amount,
        displayAmount: formatCurrency(expense.amount),
        description: expense.description,
        transaction_date: expense.transaction_date,
        status: expense.status,
        payment_method: expense.payment_method || '',
        prepared_fund: expense.prepared_fund || 0,
        displayPreparedFund: formatCurrency(expense.prepared_fund || 0),
        kode_transaksi: expense.kode_transaksi || '',
        isManualKode: !!expense.kode_transaksi,
        bukti: null,
        buktiName: '',
        buktiUrl: expense.bukti ? `/storage/${expense.bukti}` : ''
      };
      editingId.value = expense.id;
      modalTitle.value = 'Edit Pengeluaran';
      modalButtonText.value = 'Update';
    } else {
      form.value = {
        proyek_id: selectedProject.value,
        category_type: '',
        amount: 0,
        displayAmount: '',
        description: '',
        transaction_date: new Date().toISOString().split('T')[0],
        status: 'Pending',
        payment_method: '',
        prepared_fund: 0,
        displayPreparedFund: '',
        kode_transaksi: '',
        isManualKode: false,
        bukti: null,
        buktiName: '',
        buktiUrl: ''
      };
      editingId.value = null;
      modalTitle.value = 'Tambah Pengeluaran';
      modalButtonText.value = 'Simpan';
    }
    showModal.value = true;
  }
// --- Status Modal Handlers (deduped) ---
// (already declared above, do not redeclare)
  const closeModal = () => {
    showModal.value = false;
  }

// --- Status Modal close handler (deduped) ---
// (already declared above, do not redeclare)

  const handleSubmit = async () => {
    try {
        if (form.value.isManualKode && !form.value.kode_transaksi.trim()) {
            return Swal.fire('Peringatan', 'Kode transaksi harus diisi jika menggunakan kode manual', 'warning');
        }

        // Validasi anggaran sebelum submit
        const sisaAnggaran = selectedProjectDetails.value?.anggaran_kontrak - (selectedProjectDetails.value?.total_expenses || 0);
        if (form.value.amount > sisaAnggaran) {
            await Swal.fire({
                icon: 'warning',
                title: 'Anggaran Melebihi Batas!',
                text: 'Jumlah pengeluaran melebihi sisa anggaran proyek. Silakan cek kembali nilai pengeluaran.',
            });
            return;
        }

        // Validasi wajib bukti jika status Lunas/Partial
        if (["Lunas", "Partial"].includes(form.value.status)) {
            if (!form.value.bukti && !form.value.buktiUrl) {
                return Swal.fire('Peringatan', 'Bukti pembayaran wajib diupload jika status Lunas/Partial', 'warning');
            }
        }

        const token = sessionStorage.getItem('token');
        const payload = {
            proyek_id: form.value.proyek_id,
            amount: form.value.amount,
            description: form.value.description,
            transaction_date: form.value.transaction_date,
            status: form.value.status,
            payment_method: form.value.payment_method,
            prepared_fund: form.value.prepared_fund,
            kode_transaksi: form.value.isManualKode ? form.value.kode_transaksi : null
        };

        if (modalMode.value === 'edit') {
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => {
                if (v !== null && v !== undefined) fd.append(k, v);
            });
            if (form.value.bukti) {
                fd.append('bukti', form.value.bukti);
            }
            await axios.post(`/api/expenses/${editingId.value}?_method=PUT`, fd, {
                headers: { Authorization: `Bearer ${token}` },
            });
            Swal.fire('Berhasil', 'Data pengeluaran berhasil diperbarui', 'success');
        } else {
            await axios.post('/api/expenses', payload, {
                headers: { Authorization: `Bearer ${token}` },
            });
            Swal.fire('Berhasil', 'Data pengeluaran berhasil ditambahkan', 'success');
        }

        showModal.value = false;
        await fetchData();
    } catch (err) {
        Swal.fire('Error', 'Terjadi kesalahan saat menyimpan data', 'error');
    }
  }

  const handleDelete = async (id) => {
    const konfirmasi = await Swal.fire({
      title: 'Yakin ingin menghapus?',
      text: 'Data yang dihapus tidak bisa dikembalikan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Hapus',
      cancelButtonText: 'Batal'
    });

    if (konfirmasi.isConfirmed) {
      try {
        const token = sessionStorage.getItem('token');
        await axios.delete(`/api/expenses/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        Swal.fire('Berhasil', 'Data berhasil dihapus', 'success');
        await fetchData();
      } catch (err) {
        Swal.fire('Gagal', 'Tidak dapat menghapus data', 'error');
      }
    }
  }

  const handleProjectChange = (event) => {
    // console.log('Project change event:', event);
    // console.log('Selected value:', event.target.value);
    selectedProject.value = event.target.value;
    filterByProject();
  };
+
onMounted(() => {
  if (typeof $ === 'function' && typeof $.fn.DataTable === 'function') {
    fetchData();
  } else {
    error.value = 'jQuery/DataTables belum ter-load';
  }
})

// Tambahkan fungsi untuk mengambil data purchase material dengan service category
const fetchPurchaseMaterialWithServiceCategory = async (id) => {
  try {
    const token = sessionStorage.getItem('token'); // Ambil token dari sessionStorage
    if (!token) {
      throw new Error('Token tidak ditemukan di sessionStorage');
    }

    const response = await axios.get(`/api/purchase-materials/${id}/service-category`, {
      headers: { Authorization: `Bearer ${token}` } // Tambahkan header Authorization
    });

    console.log('Respons API:', response.data); // Tambahkan log untuk memeriksa data
    return response.data;
  } catch (error) {
    console.error('Error saat mengambil data service category:', error);
    return null;
  }
};

// Contoh penggunaan fungsi di mounted atau event handler
onMounted(async () => {
  const purchaseMaterialId = 1; // Ganti dengan ID yang sesuai
  const data = await fetchPurchaseMaterialWithServiceCategory(purchaseMaterialId);
  if (data) {
    form.value.service_category_id = data.service_category_id;
  }
});
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
  .scroll-wrapper {
  overflow-x: auto;
  width: 100%;
}
table.dataTable {
  white-space: nowrap;
  width: 100% !important;
}

  /* Membatasi lebar kolom agar tabel tetap ramping */
  /* .badge, .btn { font-size: 0.75rem; padding: 0.25em 0.5em; } */
  </style>
