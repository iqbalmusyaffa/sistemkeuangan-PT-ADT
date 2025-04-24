<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-money" /> Manajemen Termin
          <CButton color="primary" @click="openModal('tambah')" class="float-end" :disabled="!selectedProject">
            Tambah Termin
          </CButton>
        </CCardHeader>
        <CCardBody>
          <CAlert v-if="error" color="danger">{{ error }}</CAlert>
          <CSpinner v-if="loading" color="primary" />

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

          <!-- Tabel Termin -->
          <div v-if="selectedProject">
            <div class="w-100">
              <table ref="terminTableRef" class="display nowrap"></table>
            </div>

            <!-- Ringkasan Biaya -->
            <CCard class="mt-3">
              <CCardHeader>
                <strong>Ringkasan Termin</strong>
              </CCardHeader>
              <CCardBody>
                <CRow>
                  <CCol md="6">
                    <CTable>
                      <CTableBody>
                        <CTableRow>
                          <CTableDataCell>Total Belanja (Invoice)</CTableDataCell>
                          <CTableDataCell class="text-end">Rp {{ formatCurrency(totalPurchases) }}</CTableDataCell>
                        </CTableRow>
                        <CTableRow>
                          <CTableDataCell>Total Nilai Termin</CTableDataCell>
                          <CTableDataCell class="text-end">Rp {{ formatCurrency(totalTermin) }}</CTableDataCell>
                        </CTableRow>
                        <CTableRow>
                          <CTableDataCell>Total DP</CTableDataCell>
                          <CTableDataCell class="text-end">Rp {{ formatCurrency(totalDP) }}</CTableDataCell>
                        </CTableRow>
                        <CTableRow>
                          <CTableDataCell>Total Pelunasan</CTableDataCell>
                          <CTableDataCell class="text-end">Rp {{ formatCurrency(totalPelunasan) }}</CTableDataCell>
                        </CTableRow>
                        <CTableRow class="fw-bold">
                          <CTableDataCell>Total Keseluruhan</CTableDataCell>
                          <CTableDataCell class="text-end">Rp {{ formatCurrency(totalKeseluruhan) }}</CTableDataCell>
                        </CTableRow>
                      </CTableBody>
                    </CTable>
                  </CCol>
                </CRow>
              </CCardBody>
            </CCard>
          </div>
          <CAlert v-else color="info">
            Silakan pilih proyek terlebih dahulu untuk melihat data termin
          </CAlert>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Modal Form -->
    <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="project_id">Proyek</CFormLabel>
              <CFormSelect v-model="form.project_id" id="project_id" required>
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
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="nama_termin">Nama Termin</CFormLabel>
              <CFormInput v-model="form.nama_termin" id="nama_termin" required />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="nilai_termin">Nilai Termin</CFormLabel>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="form.displayNilaiTermin"
                  @input="handleNilaiTerminInput"
                  id="nilai_termin"
                  required
                  :readonly="true"
                  class="bg-light"
                />
              </div>
              <small class="text-muted">
                Nilai termin diambil dari Total Belanja (Invoice)
              </small>
            </CCol>
            <CCol md="6">
              <CFormLabel for="dp_percentage">Persentase DP (%)</CFormLabel>
              <CFormInput type="number" v-model.number="form.dp_percentage" id="dp_percentage" required @input="calculateValues" min="0" max="100" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="nilai_dp">Nilai DP</CFormLabel>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="form.displayNilaiDP"
                  @input="handleNilaiDPInput"
                  id="nilai_dp"
                />
              </div>
            </CCol>
            <CCol md="6">
              <CFormLabel for="nilai_pelunasan">Nilai Pelunasan</CFormLabel>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="form.displayNilaiPelunasan"
                  id="nilai_pelunasan"
                  readonly
                  class="bg-light"
                />
              </div>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="tanggal_dp">Tanggal DP</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_dp" id="tanggal_dp" />
            </CCol>
            <CCol md="6">
              <CFormLabel for="tanggal_pelunasan">Tanggal Pelunasan</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_pelunasan" id="tanggal_pelunasan" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="status_termin">Status Termin</CFormLabel>
              <CFormSelect v-model="form.status_termin" id="status_termin" required>
                <option value="Belum Dibayar">Belum Dibayar</option>
                <option value="DP Dibayar">DP Dibayar</option>
                <option value="Lunas">Lunas</option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="keterangan">Keterangan</CFormLabel>
              <CFormTextarea v-model="form.keterangan" id="keterangan" rows="3" />
            </CCol>
          </CRow>
          <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
        </CForm>
      </CModalBody>
    </CModal>

    <!-- Status Update Modal -->
    <CModal :visible="showStatusModal" @close="closeStatusModal" title="Update Status Termin" size="sm">
      <CModalBody>
        <CForm @submit.prevent="handleStatusUpdate">
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="status_update">Status Termin</CFormLabel>
              <CFormSelect v-model="statusForm.status_termin" id="status_update" required>
                <option value="Belum Dibayar">Belum Dibayar</option>
                <option value="DP Dibayar">DP Dibayar</option>
                <option value="Lunas">Lunas</option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="tanggal_dp_update">Tanggal DP</CFormLabel>
              <CFormInput type="date" v-model="statusForm.tanggal_dp" id="tanggal_dp_update" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="tanggal_pelunasan_update">Tanggal Pelunasan</CFormLabel>
              <CFormInput type="date" v-model="statusForm.tanggal_pelunasan" id="tanggal_pelunasan_update" />
            </CCol>
          </CRow>
          <CButton type="submit" color="primary">Update Status</CButton>
        </CForm>
      </CModalBody>
    </CModal>
  </CRow>
</template>

<script setup>
import { ref, onMounted, nextTick, watch, computed } from "vue";
import axios from "axios";
import $ from "jquery";
import Swal from "sweetalert2";
import "datatables.net-dt/css/dataTables.dataTables.min.css";
import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
import "datatables.net-responsive-dt";

const terminTableRef = ref(null);
const form = ref({
  project_id: "",
  nama_termin: "",
  nilai_termin: 0,
  dp_percentage: 50,
  nilai_dp: 0,
  nilai_pelunasan: 0,
  tanggal_dp: "",
  tanggal_pelunasan: "",
  status_termin: "Belum Dibayar",
  keterangan: "",
  displayNilaiTermin: "0",
  displayNilaiDP: "0",
  displayNilaiPelunasan: "0"
});

const statusForm = ref({
  status_termin: "",
  tanggal_dp: "",
  tanggal_pelunasan: ""
});

const projects = ref([]);
const termins = ref([]);
const purchases = ref([]);
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const showStatusModal = ref(false);
const modalTitle = ref("Tambah Termin");
const modalButtonText = ref("Simpan");
const modalMode = ref("tambah");
const editingId = ref(null);
const updatingStatusId = ref(null);
const selectedProject = ref("");
const selectedProjectDetails = ref(null);

// Add groupedProjects computed property
const groupedProjects = computed(() => {
  const grouped = {};
  projects.value.forEach(project => {
    if (!grouped[project.nama_customer]) {
      grouped[project.nama_customer] = [];
    }
    grouped[project.nama_customer].push(project);
  });
  return grouped;
});

// Add handleNilaiTerminInput method
const handleNilaiTerminInput = (event) => {
  // Nilai termin is now read-only, always using total purchases
  form.value.nilai_termin = totalPurchases.value;
  form.value.displayNilaiTermin = formatCurrency(totalPurchases.value);
  calculateValues();
};

// Add handleNilaiDPInput method
const handleNilaiDPInput = (event) => {
  const value = event.target.value.replace(/[^\d]/g, '');
  form.value.nilai_dp = Number(value) || 0;
  form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);

  // Calculate percentage based on DP value
  if (form.value.nilai_termin > 0) {
    form.value.dp_percentage = Math.round((form.value.nilai_dp / form.value.nilai_termin) * 100);
    form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
    form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  }
};

// Add onMounted hook
onMounted(async () => {
  console.log("Component mounted, fetching projects...");
  await fetchProjects();
});

// Fetch Projects
const fetchProjects = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/proyeks", {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (response.data && response.data.data) {
      projects.value = response.data.data.map(project => ({
        id: project.id,
        nama_customer: project.nama_customer || '-',
        nama_proyek: project.nama_proyek || project.nama_project || '-'
      }));

      if (projects.value.length === 0) {
        error.value = "Tidak ada data proyek tersedia";
      }
    } else {
      projects.value = [];
      error.value = "Format data tidak sesuai";
    }
  } catch (err) {
    console.error("Failed to load projects:", err);
    error.value = "Gagal memuat data proyek: " + (err.response?.data?.message || err.message);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: error.value
    });
  }
};

const fetchTermins = async () => {
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");
    console.log("Fetching termins for project:", selectedProject.value);

    const response = await axios.get("/api/termins", {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: selectedProject.value }
    });

    console.log("Termins response:", response.data);

    // Handle array response directly
    if (Array.isArray(response.data)) {
      termins.value = response.data;
    }
    // Handle response with data property
    else if (response.data && Array.isArray(response.data.data)) {
      termins.value = response.data.data;
    }
    // Handle other response formats
    else if (response.data) {
      termins.value = [response.data];
    } else {
      termins.value = [];
    }

    console.log("Processed termins:", termins.value);

    // Initialize DataTable after data is loaded
    nextTick(() => {
      initDataTable();
    });

  } catch (err) {
    console.error('Error fetching termins:', err);
    error.value = "Gagal memuat data termin: " + (err.response?.data?.message || err.message);
    termins.value = [];
  }
};

// Calculate DP and Pelunasan values
const calculateValues = () => {
  if (form.value.dp_percentage) {
    form.value.nilai_dp = totalPurchases.value * (form.value.dp_percentage / 100);
    form.value.nilai_pelunasan = totalPurchases.value - form.value.nilai_dp;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
    form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  }
};

// DataTable initialization
const initDataTable = () => {
  console.log("Initializing DataTable with data:", termins.value);

  // Destroy existing DataTable if it exists
  if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
    $(terminTableRef.value).DataTable().destroy();
  }

  // Clear the table contents
  $(terminTableRef.value).empty();

  const dataTableConfig = {
    data: termins.value,
    language: {
      emptyTable: "Tidak ada data yang tersedia",
      info: "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
      infoEmpty: "Menampilkan 0 hingga 0 dari 0 entri",
      infoFiltered: "(disaring dari _MAX_ total entri)",
      lengthMenu: "Tampilkan _MENU_ entri",
      loadingRecords: "Memuat...",
      processing: "Memproses...",
      search: "Cari:",
      zeroRecords: "Tidak ditemukan data yang sesuai",
      paginate: {
        first: "Pertama",
        last: "Terakhir",
        next: "Selanjutnya",
        previous: "Sebelumnya"
      }
    },
    columns: [
      {
        title: "No",
        data: null,
        render: (data, type, row, meta) => meta.row + 1,
        className: 'text-center align-middle',
        width: '40px'
      },
      {
        title: "Nama Termin",
        data: "nama_termin",
        render: (data) => data || "-",
        className: 'text-start align-middle',
        width: '120px'
      },
      {
        title: "Nilai Termin",
        data: "nilai_termin",
        render: (data) => `Rp ${formatCurrency(data || 0)}`,
        className: 'text-end align-middle',
        width: '130px'
      },
      {
        title: "DP (%)",
        data: "dp_percentage",
        render: (data) => `${data || 0}%`,
        className: 'text-center align-middle',
        width: '70px'
      },
      {
        title: "Nilai DP",
        data: "nilai_dp",
        render: (data) => `Rp ${formatCurrency(data || 0)}`,
        className: 'text-end align-middle',
        width: '130px'
      },
      {
        title: "Nilai Pelunasan",
        data: "nilai_pelunasan",
        render: (data) => `Rp ${formatCurrency(data || 0)}`,
        className: 'text-end align-middle',
        width: '130px'
      },
      {
        title: "Tanggal DP",
        data: "tanggal_dp",
        render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-",
        className: 'text-center align-middle',
        width: '100px'
      },
      {
        title: "Tanggal Pelunasan",
        data: "tanggal_pelunasan",
        render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-",
        className: 'text-center align-middle',
        width: '100px'
      },
      {
        title: "Status",
        data: "status_termin",
        render: (data) => {
          const statusClasses = {
            'Belum Dibayar': 'danger',
            'DP Dibayar': 'warning',
            'Lunas': 'success'
          };
          return `<span class="badge bg-${statusClasses[data] || 'secondary'}">${data || '-'}</span>`;
        },
        className: 'text-center align-middle',
        width: '100px'
      },
      {
        title: "Aksi",
        data: null,
        orderable: false,
        className: 'text-center align-middle',
        width: '100px',
        render: (data, type, row) => {
          if (!row || !row.id) return '';
          return `
            <div class="d-flex justify-content-center" style="gap: 4px;">
              <button type="button" class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button>
              <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
            </div>
          `;
        }
      }
    ],
    // Basic Configuration
    responsive: false,
    processing: true,
    serverSide: false,
    searching: true,
    
    // Scrolling Configuration
    scrollX: true,
    scrollCollapse: true,
    autoWidth: false,
    
    // Fixed Columns Configuration
    fixedColumns: {
      right: 2 // Fix Status and Action columns
    },
    
    // Other configurations
    dom: '<"top"lf>rt<"bottom"ip><"clear">',
    pageLength: 10,
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
    
    // Event Handlers
    drawCallback: function(settings) {
      const api = this.api();
      
      // Reattach event handlers for edit buttons
      $(this).find('.edit-btn').each(function() {
        $(this).off('click').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const id = $(this).data('id');
          const termin = termins.value.find(t => t.id === parseInt(id));
          if (termin) {
            openModal('edit', termin);
          }
        });
      });

      // Reattach event handlers for delete buttons
      $(this).find('.delete-btn').each(function() {
        $(this).off('click').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const id = $(this).data('id');
          if (id) {
            deleteTermin(id);
          }
        });
      });
    }
  };

  // Initialize DataTable
  const table = $(terminTableRef.value).DataTable(dataTableConfig);

  // Handle window resize
  $(window).on('resize', function() {
    table.columns.adjust().draw();
  });

  return table;
};

const filterByProject = async () => {
  if (selectedProject.value) {
    loading.value = true;
    error.value = "";
    try {
      console.log("Filtering by project:", selectedProject.value); // Debug log
      await Promise.all([
        fetchTermins(),
        fetchPurchases(selectedProject.value)
      ]);
    } catch (err) {
      console.error('Error filtering by project:', err);
      error.value = "Gagal memuat data";
    } finally {
      loading.value = false;
    }
  } else {
    termins.value = [];
    purchases.value = [];
    if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
      $(terminTableRef.value).DataTable().destroy();
      $(terminTableRef.value).empty();
    }
  }
};

// Watch for changes in selectedProject
watch(selectedProject, async (newValue, oldValue) => {
  if (newValue !== oldValue) {
    if (newValue) {
      loading.value = true;
      error.value = "";
      try {
        await Promise.all([
          fetchTermins(),
          fetchPurchases(newValue)
        ]);
      } catch (err) {
        console.error('Error on project change:', err);
        error.value = "Gagal memuat data untuk proyek yang dipilih";
        termins.value = [];
        purchases.value = [];
        if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
          $(terminTableRef.value).DataTable().destroy();
          $(terminTableRef.value).empty();
        }
      } finally {
        loading.value = false;
      }
    } else {
      termins.value = [];
      purchases.value = [];
      if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
        $(terminTableRef.value).DataTable().destroy();
        $(terminTableRef.value).empty();
      }
      error.value = "";
    }
  }
}, { immediate: true });

// Open Modal for Add/Edit
const openModal = async (mode, termin = null) => {
  modalMode.value = mode;
  if (mode === "edit" && termin) {
    // Format dates for input type="date"
    const formatDateForInput = (dateString) => {
      if (!dateString) return "";
      const date = new Date(dateString);
      return date.toISOString().split('T')[0];
    };

    form.value = {
      project_id: termin.proyek_id || selectedProject.value,
      nama_termin: termin.nama_termin,
      nilai_termin: totalPurchases.value,
      dp_percentage: termin.dp_percentage,
      nilai_dp: termin.nilai_dp,
      nilai_pelunasan: termin.nilai_pelunasan,
      tanggal_dp: formatDateForInput(termin.tanggal_dp),
      tanggal_pelunasan: formatDateForInput(termin.tanggal_pelunasan),
      status_termin: termin.status_termin,
      keterangan: termin.keterangan || "",
      displayNilaiTermin: formatCurrency(totalPurchases.value),
      displayNilaiDP: formatCurrency(termin.nilai_dp),
      displayNilaiPelunasan: formatCurrency(termin.nilai_pelunasan)
    };
    editingId.value = termin.id;
    modalTitle.value = "Edit Termin";
    modalButtonText.value = "Update";
  } else {
    // Set project_id to the currently selected project
    const today = new Date().toISOString().split('T')[0];
    form.value = {
      project_id: selectedProject.value,
      nama_termin: "",
      nilai_termin: totalPurchases.value,
      dp_percentage: 50,
      nilai_dp: totalPurchases.value * 0.5,
      nilai_pelunasan: totalPurchases.value * 0.5,
      tanggal_dp: today,
      tanggal_pelunasan: "",
      status_termin: "Belum Dibayar",
      keterangan: "",
      displayNilaiTermin: formatCurrency(totalPurchases.value),
      displayNilaiDP: formatCurrency(totalPurchases.value * 0.5),
      displayNilaiPelunasan: formatCurrency(totalPurchases.value * 0.5)
    };
    editingId.value = null;
    modalTitle.value = "Tambah Termin";
    modalButtonText.value = "Simpan";
  }
  showModal.value = true;
};

// Open Status Update Modal
const openStatusModal = (termin) => {
  statusForm.value = {
    status_termin: termin.status_termin,
    tanggal_dp: termin.tanggal_dp || "",
    tanggal_pelunasan: termin.tanggal_pelunasan || ""
  };
  updatingStatusId.value = termin.id;
  showStatusModal.value = true;
};

// Close the modal
const closeModal = () => {
  showModal.value = false;
};

// Close the status modal
const closeStatusModal = () => {
  showStatusModal.value = false;
};

// Handle Submit of Form
const handleSubmit = async () => {
  loading.value = true;
  error.value = "";

  if (!form.value.project_id) {
    error.value = "Proyek wajib dipilih!";
    loading.value = false;
    return;
  }

  if (!form.value.nama_termin.trim()) {
    error.value = "Nama termin wajib diisi!";
    loading.value = false;
    return;
  }

  if (!form.value.nilai_termin || form.value.nilai_termin <= 0) {
    error.value = "Nilai termin harus lebih dari 0!";
    loading.value = false;
    return;
  }

  if (!form.value.dp_percentage || form.value.dp_percentage <= 0 || form.value.dp_percentage > 100) {
    error.value = "Persentase DP harus antara 0-100%!";
    loading.value = false;
    return;
  }

  calculateValues();

  try {
    const token = sessionStorage.getItem("token");

    // Format dates for API
    const formatDateForAPI = (dateString) => {
      if (!dateString) return null;
      const date = new Date(dateString);
      return date.toISOString().split('T')[0];
    };

    const payload = {
      proyek_id: form.value.project_id,
      nama_termin: form.value.nama_termin,
      nilai_termin: form.value.nilai_termin,
      dp_percentage: form.value.dp_percentage,
      nilai_dp: form.value.nilai_dp,
      nilai_pelunasan: form.value.nilai_pelunasan,
      tanggal_dp: formatDateForAPI(form.value.tanggal_dp),
      tanggal_pelunasan: formatDateForAPI(form.value.tanggal_pelunasan),
      status_termin: form.value.status_termin,
      keterangan: form.value.keterangan || ""
    };

    if (modalMode.value === "edit") {
      await axios.put(`/api/termins/${editingId.value}`, payload, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin diperbarui." });
    } else {
      await axios.post("/api/termins", payload, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin ditambahkan." });
    }

    closeModal();
    await fetchTermins();
  } catch (err) {
    console.error("Error submitting form:", err);
    const errorMessage = err.response?.data?.message || "Terjadi kesalahan saat menyimpan data";
    Swal.fire({
      icon: "error",
      title: "Error",
      text: errorMessage
    });
  } finally {
    loading.value = false;
  }
};

// Add watch for status_termin changes in form
watch(() => form.value.status_termin, (newStatus) => {
  if (newStatus === "Lunas") {
    // If status is Lunas, set pelunasan date to today if not already set
    if (!form.value.tanggal_pelunasan) {
      form.value.tanggal_pelunasan = new Date().toISOString().split('T')[0];
    }
    form.value.nilai_pelunasan = 0;
    form.value.displayNilaiPelunasan = formatCurrency(0);
    form.value.nilai_dp = form.value.nilai_termin;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_termin);
    form.value.dp_percentage = 100;
  } else if (newStatus === "DP Dibayar") {
    // If status is DP Dibayar, set DP date to today if not already set
    if (!form.value.tanggal_dp) {
      form.value.tanggal_dp = new Date().toISOString().split('T')[0];
    }
  }
});

// Add watch for project_id changes in form
watch(() => form.value.project_id, (newProjectId) => {
  if (newProjectId) {
    const project = projects.value.find(p => p.id === parseInt(newProjectId));
    if (project) {
      selectedProject.value = project.id;
    }
  }
});

// Modify handleStatusUpdate function
const handleStatusUpdate = async () => {
  loading.value = true;
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");
    const termin = termins.value.find(t => t.id === updatingStatusId.value);

    // Create expense entry based on status
    if (statusForm.value.status_termin === "DP Dibayar" || statusForm.value.status_termin === "Lunas") {
      const today = new Date();
      const formattedDate = `${String(today.getDate()).padStart(2, '0')}.${String(today.getMonth() + 1).padStart(2, '0')}.${today.getFullYear()}`;

      const expenseData = {
        tanggal: formattedDate,
        kategori: "Pembayaran Termin",
        deskripsi: `Pembayaran ${statusForm.value.status_termin} untuk termin ${termin.nama_termin}`,
        jumlah: statusForm.value.status_termin === "Lunas" ? termin.nilai_termin : termin.nilai_dp,
        metode_pembayaran: "Transfer",
        status: "Selesai"
      };

      await axios.post("/api/expenses", expenseData, {
        headers: { Authorization: `Bearer ${token}` }
      });
    }

    // Update termin status
    await axios.put(`/api/termins/${updatingStatusId.value}/status`, statusForm.value, {
      headers: { Authorization: `Bearer ${token}` }
    });

    Swal.fire({
      icon: "success",
      title: "Berhasil!",
      text: "Status termin berhasil diperbarui"
    });

    closeStatusModal();
    await fetchTermins();
  } catch (err) {
    console.error("Error updating status:", err);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: err.response?.data?.message || "Gagal memperbarui status termin"
    });
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value || 0);
};

const changeProject = () => {
  selectedProject.value = "";
  termins.value = [];
  purchases.value = [];
  if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
    $(terminTableRef.value).DataTable().destroy();
    $(terminTableRef.value).empty();
  }
};

const totalTermin = computed(() => {
  return termins.value.reduce((sum, termin) => sum + (Number(termin.nilai_termin) || 0), 0);
});

const totalDP = computed(() => {
  return termins.value.reduce((sum, termin) => sum + (Number(termin.nilai_dp) || 0), 0);
});

const totalPelunasan = computed(() => {
  return termins.value.reduce((sum, termin) => sum + (Number(termin.nilai_pelunasan) || 0), 0);
});

const totalPurchases = computed(() => {
  return purchases.value.reduce((sum, purchase) => sum + Number(purchase.total_harga), 0);
});

const totalKeseluruhan = computed(() => {
  return totalPurchases.value;
});

const deleteTermin = async (id) => {
  try {
    const result = await Swal.fire({
      title: "Apakah anda yakin?",
      text: "Data yang dihapus tidak dapat dikembalikan!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Ya, hapus!",
      cancelButtonText: "Batal"
    });

    if (result.isConfirmed) {
      const token = sessionStorage.getItem("token");
      await axios.delete(`/api/termins/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      });

      Swal.fire({
        icon: "success",
        title: "Berhasil!",
        text: "Data termin berhasil dihapus"
      });

      await fetchTermins();
    }
  } catch (err) {
    console.error("Error deleting termin:", err);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: err.response?.data?.message || "Gagal menghapus data termin"
    });
  }
};

// Add new function to fetch purchases
const fetchPurchases = async (projectId) => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/purchasematerials", {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: projectId }
    });

    if (response.data && response.data.purchases) {
      purchases.value = response.data.purchases;
    }
  } catch (err) {
    console.error('Error fetching purchases:', err);
    error.value = 'Gagal memuat data pembelian';
  }
};

</script>

<style scoped>
.w-100 {
  width: 100%;
  overflow-x: auto;
}

.dataTables_wrapper {
  overflow-x: auto;
  position: relative;
}

table.display {
  width: 100% !important;
  min-width: 1000px;
}

/* Fixed columns styles */
.dataTables_scroll {
  position: relative;
  clear: both;
  width: 100%;
}

.dataTables_scrollBody {
  overflow-x: auto;
  overflow-y: auto;
  max-height: none;
}

/* Fixed column styles */
.fixed-columns {
  position: sticky;
  background: white;
  z-index: 1;
}

.fixed-columns-left {
  left: 0;
  box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.fixed-columns-right {
  right: 0;
  box-shadow: -2px 0 5px rgba(0,0,0,0.1);
}

/* Table cell styles */
table.dataTable tbody td {
  white-space: nowrap;
  padding: 8px;
}

/* Button styles */
.btn {
  margin: 0 2px;
}

/* Status badge styles */
.badge {
  padding: 0.5em 0.75em;
  font-size: 0.875em;
}
</style>

<style>
/* Table Container */
.dataTables_wrapper {
  margin: 1rem 0;
  padding: 0;
  width: 100%;
}

/* Table Header */
table.dataTable thead th {
  padding: 10px 8px;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  white-space: nowrap;
  vertical-align: middle;
  background-color: #fff;
}

/* Table Body */
table.dataTable tbody td {
  padding: 8px;
  vertical-align: middle;
  border-bottom: 1px solid #dee2e6;
  white-space: nowrap;
  background-color: #fff;
}

/* Fixed Columns */
.DTFC_RightWrapper {
  right: 0 !important;
}

.DTFC_RightWrapper table.dataTable {
  margin-right: 0 !important;
}

.DTFC_RightWrapper .DTFC_RightHeadWrapper,
.DTFC_RightWrapper .DTFC_RightBodyWrapper {
  background-color: #fff;
}

.DTFC_RightWrapper thead th,
.DTFC_RightWrapper tbody td {
  border-left: 1px solid #dee2e6;
}

/* Button Styles */
.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  line-height: 1.2;
  border-radius: 0.2rem;
  min-width: 40px;
}

/* Status Badge */
.badge {
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  white-space: nowrap;
}

/* Responsive Table */
.dataTables_scroll {
  margin-bottom: 1rem;
}

.dataTables_scrollBody {
  min-height: 200px;
}

/* Search and Length Menu */
.dataTables_length,
.dataTables_filter {
  margin-bottom: 1rem;
}

.dataTables_length select {
  min-width: 80px;
}

/* Pagination */
.dataTables_paginate {
  margin-top: 1rem;
}

/* Ensure consistent alignment */
.text-end {
  text-align: right !important;
}

.text-center {
  text-align: center !important;
}

.align-middle {
  vertical-align: middle !important;
}

/* Ensure buttons stay on one line */
.d-flex.justify-content-center {
  flex-wrap: nowrap;
  gap: 4px;
}

/* Scrollbar Styles */
.dataTables_scrollBody::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}

.dataTables_scrollBody::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Shadow for fixed columns */
.DTFC_RightWrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: -6px;
  bottom: 0;
  width: 6px;
  pointer-events: none;
  background: linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,0.1));
}
</style>
