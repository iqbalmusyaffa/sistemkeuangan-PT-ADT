<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-money" /> Manajemen Termin
          <div class="float-end btn-group">
            <!-- Tambah Tombol Export/Import -->
            <CButton color="secondary" @click="exportToPDF" class="me-2">
              <CIcon icon="cil-cloud-download" /> PDF
            </CButton>
            <CButton color="success" @click="exportToExcel" class="me-2">
              <CIcon icon="cid-spreadsheet" /> Excel
            </CButton>
            <CButton color="warning" @click="$refs.fileInput.click()">
              <CIcon icon="cil-cloud-upload" /> Import
            </CButton>
            <input
              type="file"
              ref="fileInput"
              @change="handleExcelImport"
              style="display: none"
              accept=".xlsx, .xls"
            >
          </div>

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
              <CFormSelect v-model="selectedProject" id="project_filter" @change="filterByProject">
                <option value="">-- Pilih Proyek --</option>
                <option v-for="project in projects" :key="project.id" :value="String(project.id)">
                  {{ project.nama_customer }} - {{ project.nama_proyek }}
                </option>
              </CFormSelect>
            </CCol>
            <CCol md="6">
              <CFormLabel for="invoice_filter">Pilih Invoice</CFormLabel>
              <CFormSelect v-model="selectedInvoice" id="invoice_filter" :disabled="!selectedProject || !!selectedInvoice">
                <option value="">-- Pilih Invoice --</option>
                <option v-for="invoice in invoices" :key="invoice.id" :value="String(invoice.id)">
                  {{ invoice.invoice_number }} - {{ invoice.invoice_date }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>

          <!-- Tabel Termin -->
          <div v-if="selectedProject">
            <table ref="terminTableRef" class="table table-striped table-bordered w-100"></table>
          </div>
          <CAlert v-else color="info">
            Silakan pilih proyek terlebih dahulu untuk melihat data termin
          </CAlert>
        </CCardBody>
      </CCard>
      <CCard v-if="selectedProject" class="mt-3">
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
                          :value="String(project.id)">
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
  invoice_id: "",
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
const selectedInvoice = ref("");
const invoices = ref([]);
const API_URL = ""; // Jika perlu, isi dengan base URL API kamu, misal: "http://localhost:8000/api"

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
  if (!selectedInvoice.value) {
    termins.value = [];
    nextTick(() => {
      initDataTable();
    });
    return;
  }
  try {
    const token = sessionStorage.getItem("token");
    const params = { proyek_id: selectedProject.value };
    if (selectedInvoice.value) params.invoice_id = selectedInvoice.value;
    const response = await axios.get("/api/termins", {
      headers: { Authorization: `Bearer ${token}` },
      params
    });
    if (Array.isArray(response.data)) {
      termins.value = response.data;
    } else if (response.data && Array.isArray(response.data.data)) {
      termins.value = response.data.data;
    } else if (response.data) {
      termins.value = [response.data];
    } else {
      termins.value = [];
    }
    nextTick(() => {
      initDataTable();
    });
  } catch (err) {
    error.value = "Gagal memuat data termin: " + (err.response?.data?.message || err.message);
    termins.value = [];
  }
};

// Calculate DP and Pelunasan values
const calculateValues = () => {
  if (form.value.dp_percentage) {
    // Calculate DP based on percentage
    form.value.nilai_dp = form.value.nilai_termin * (form.value.dp_percentage / 100);
    // Calculate final payment as total minus DP
    form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;

    // Update display values
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
    form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  }
};

// DataTable initialization
const initDataTable = () => {
  // Destroy existing DataTable if it exists
  if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
    $(terminTableRef.value).DataTable().destroy();
  }

  // Clear the table contents
  $(terminTableRef.value).empty();


  const dataTableConfig = {
    data: termins.value,
    columns: [
      {
        title: "No",
        data: null,
        render: (data, type, row, meta) => meta.row + 1,
        className: "text-center",
        width: "30px"
      },
      {
        title: "Nama Termin",
        data: "nama_termin",
        render: data => data || "-",
        className: "text-start"
      },
      {
        title: "Nilai Termin",
        data: "nilai_termin",
        render: data => `Rp ${formatCurrency(data || 0)}`,
        className: "text-end"
      },
      {
        title: "DP (%)",
        data: "dp_percentage",
        render: data => `${data || 0}%`,
        className: "text-center"
      },
      {
        title: "Nilai DP",
        data: "nilai_dp",
        render: data => `Rp ${formatCurrency(data || 0)}`,
        className: "text-end"
      },
      {
        title: "Nilai Pelunasan",
        data: "nilai_pelunasan",
        render: data => `Rp ${formatCurrency(data || 0)}`,
        className: "text-end"
      },
      {
        title: "Tanggal DP",
        data: "tanggal_dp",
        render: data => data ? new Date(data).toLocaleDateString("id-ID") : "-",
        className: "text-center"
      },
      {
        title: "Tanggal Pelunasan",
        data: "tanggal_pelunasan",
        render: data => data ? new Date(data).toLocaleDateString("id-ID") : "-",
        className: "text-center"
      },
      {
        title: "Status",
        data: "status_termin",
        render: data => {
          const statusClass = {
            "Belum Dibayar": "danger",
            "DP Dibayar": "warning",
            "Lunas": "success"
          };
          return `<span class="badge bg-${statusClass[data] || "secondary"}">${data || "-"}</span>`;
        },
        className: "text-center"
      },
      {
        title: "Aksi",
        data: null,
        orderable: false,
        className: "text-center",
        render: (data, type, row) => `
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-primary btn-sm edit-btn" data-id="${row.id}" title="Edit">
              <i class="cil-pencil"></i>
            </button>
            <button type="button" class="btn btn-info btn-sm status-btn" data-id="${row.id}" title="Status">
              <i class="cil-task"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${row.id}" title="Hapus">
              <i class="cil-trash"></i>
            </button>
          </div>
        `
      }
    ],
    responsive: false,
    autoWidth: false,
    scrollX: true,
    dom: '<"row mb-2"<"col-sm-6"l><"col-sm-6 text-end"f>>rt<"row mt-2"<"col-sm-6"i><"col-sm-6 text-end"p>>',
    language: {
      emptyTable: "Tidak ada data",
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ entri",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
      paginate: {
        previous: "&laquo;",
        next: "&raquo;"
      }
    },
    drawCallback: function() {
      // Attach actions
      $(this).find(".edit-btn").off().on("click", function () {
        const id = $(this).data("id");
        const termin = termins.value.find(t => t.id === id);
        if (termin) openModal("edit", termin);
      });

      $(this).find(".status-btn").off().on("click", function () {
        const id = $(this).data("id");
        const termin = termins.value.find(t => t.id === id);
        if (termin) openStatusModal(termin);
      });

      $(this).find(".delete-btn").off().on("click", function () {
        const id = $(this).data("id");
        if (id) deleteTermin(id);
      });
    }
  };

  const table = $(terminTableRef.value).DataTable(dataTableConfig);

  $(window).on("resize", () => table.columns.adjust());

  return table;
};

const filterByProject = async () => {
  await fetchInvoices(String(selectedProject.value));
  purchases.value = [];
  selectedInvoice.value = "";
  termins.value = [];
  nextTick(() => {
    initDataTable();
  });
};

const filterByInvoice = async (...args) => {
  await fetchPurchases(selectedProject.value, selectedInvoice.value);
  await fetchTermins();
};

watch(selectedProject, async (newVal) => {
  await fetchInvoices(String(newVal));
  purchases.value = [];
  selectedInvoice.value = "";
  await fetchTermins();
});

watch(selectedInvoice, (newVal) => {
  if (newVal) {
    fetchPurchases(selectedProject.value, newVal);
    fetchTermins();
  } else {
    purchases.value = [];
    termins.value = [];
    nextTick(() => {
      initDataTable();
    });
  }
});

watch(selectedInvoice, (newVal) => {
  if (!newVal) return;
  const invoice = invoices.value.find(inv => inv.id == newVal);
  if (invoice) {
    form.value.invoice_id = invoice.id;
    form.value.nilai_termin = Number(invoice.total_amount) || 0;
    form.value.displayNilaiTermin = formatCurrency(form.value.nilai_termin);
    // Default DP 50%
    form.value.dp_percentage = 50;
    form.value.nilai_dp = form.value.nilai_termin * 0.5;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
    form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
    form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  }
});

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
      invoice_id: termin.invoice_id || selectedInvoice.value || "",
      nama_termin: termin.nama_termin,
      nilai_termin: termin.nilai_termin,
      dp_percentage: termin.dp_percentage,
      nilai_dp: termin.nilai_dp,
      nilai_pelunasan: termin.nilai_pelunasan,
      tanggal_dp: formatDateForInput(termin.tanggal_dp),
      tanggal_pelunasan: formatDateForInput(termin.tanggal_pelunasan),
      status_termin: termin.status_termin,
      keterangan: termin.keterangan || "",
      displayNilaiTermin: formatCurrency(termin.nilai_termin),
      displayNilaiDP: formatCurrency(termin.nilai_dp),
      displayNilaiPelunasan: formatCurrency(termin.nilai_pelunasan)
    };
    editingId.value = termin.id;
    modalTitle.value = "Edit Termin";
    modalButtonText.value = "Update";
  } else {
    // Set nilai dari invoice yang dipilih
    const invoice = invoices.value.find(inv => inv.id == selectedInvoice.value);
    const nilai_termin = invoice ? Number(invoice.total_amount) : 0;
    const dp = nilai_termin * 0.5;
    const pelunasan = nilai_termin - dp;
    const today = new Date().toISOString().split('T')[0];
    form.value = {
      project_id: selectedProject.value,
      invoice_id: selectedInvoice.value || "",
      nama_termin: "",
      nilai_termin: nilai_termin,
      dp_percentage: 50,
      nilai_dp: dp,
      nilai_pelunasan: pelunasan,
      tanggal_dp: today,
      tanggal_pelunasan: "",
      status_termin: "Belum Dibayar",
      keterangan: "",
      displayNilaiTermin: formatCurrency(nilai_termin),
      displayNilaiDP: formatCurrency(dp),
      displayNilaiPelunasan: formatCurrency(pelunasan)
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

  // Basic validation for both modes
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

  // Get the original termin data if we're editing
  const originalTermin = modalMode.value === "edit" ? termins.value.find(t => t.id === editingId.value) : null;

  // Check if we're only editing non-numeric fields
  const isEditingNonNumeric = modalMode.value === "edit" && originalTermin &&
    form.value.nilai_termin === originalTermin.nilai_termin &&
    form.value.dp_percentage === originalTermin.dp_percentage;

  // Skip validation for non-numeric field edits
  if (!isEditingNonNumeric) {
    // Validate total termin amount
    const totalTermin = termins.value
      .filter(t => t.id !== editingId.value)
      .reduce((sum, t) => sum + Number(t.nilai_termin), 0);

    const totalWithNewTermin = totalTermin + Number(form.value.nilai_termin);
    const totalInvoice = selectedInvoice.value ? Number(selectedInvoice.value.total_amount) : 0;

    if (totalWithNewTermin > totalInvoice) {
      error.value = "Total nilai termin tidak boleh melebihi total invoice!";
      loading.value = false;
      return;
    }

    // Validate budget
    const sisaAnggaran = selectedProjectDetails.value?.anggaran_kontrak - (selectedProjectDetails.value?.total_expenses || 0);
    const totalTerminBaru = form.value.nilai_termin || 0;
    if (totalTerminBaru > sisaAnggaran) {
      await Swal.fire({
        icon: 'warning',
        title: 'Anggaran Melebihi Batas!',
        text: 'Jumlah termin melebihi sisa anggaran proyek. Silakan cek kembali nilai termin.',
      });
      loading.value = false;
      return;
    }
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
      invoice_id: form.value.invoice_id,
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

    let response;
    if (modalMode.value === "edit") {
      response = await axios.put(`/api/termins/${editingId.value}`, payload, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin diperbarui." });
    } else {
      response = await axios.post("/api/termins", payload, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin ditambahkan." });
    }

    // Update invoice status after termin operation
    if (form.value.invoice_id) {
      try {
        // Get current invoice with termins
        const invoiceResponse = await axios.get(`/api/invoices/${form.value.invoice_id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        const invoice = invoiceResponse.data.data;
        const termins = invoice.termins || [];

        // Calculate total paid amount and check termin statuses
        let totalPaid = 0;
        let allTerminsPaid = true;
        let hasTermins = termins.length > 0;

        termins.forEach(termin => {
          if (termin.status_termin === "Lunas") {
            totalPaid += parseFloat(termin.nilai_termin);
          } else if (termin.status_termin === "DP Dibayar") {
            totalPaid += parseFloat(termin.nilai_dp);
            allTerminsPaid = false;
          } else {
            allTerminsPaid = false;
          }
        });

        // Determine new invoice status
        let newStatus = 'unpaid';
        if (hasTermins) {
          if (allTerminsPaid && totalPaid >= parseFloat(invoice.total_amount)) {
            newStatus = 'paid';
          } else if (totalPaid > 0) {
            newStatus = 'partially_paid';
          }
        }

        // Update invoice status and amount_paid
        await axios.put(`/api/invoices/${form.value.invoice_id}`, {
          status: newStatus,
          amount_paid: totalPaid
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });
      } catch (err) {
        console.error("Error updating invoice status:", err);
      }
    }

    closeModal();
    await fetchTermins();
    // Dispatch event to notify invoice component
    window.dispatchEvent(new Event('termin-updated'));
  } catch (err) {
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
      selectedProject.value = String(project.id);
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

    // Format dates for API
    const formatDateForAPI = (dateString) => {
      if (!dateString) return null;
      const date = new Date(dateString);
      return date.toISOString().split('T')[0];
    };

    const payload = {
      status_termin: statusForm.value.status_termin,
      tanggal_dp: formatDateForAPI(statusForm.value.tanggal_dp),
      tanggal_pelunasan: formatDateForAPI(statusForm.value.tanggal_pelunasan)
    };

    // Update termin status
    await axios.put(`/api/termins/${updatingStatusId.value}/status`, payload, {
      headers: { Authorization: `Bearer ${token}` }
    });

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

    Swal.fire({
      icon: "success",
      title: "Berhasil!",
      text: "Status termin berhasil diperbarui"
    });

    closeStatusModal();
    await fetchTermins();
  } catch (err) {
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
    Swal.fire({
      icon: "error",
      title: "Error",
      text: err.response?.data?.message || "Gagal menghapus data termin"
    });
  }
};

// Update fetchPurchases to use rest parameters
const fetchPurchases = async (...args) => {
  const [projectId, invoiceId] = args;
  if (!projectId || !invoiceId) {
    purchases.value = [];
    return;
  }
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/purchasematerials", {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: projectId }
    });
    let allPurchases = [];
    if (response.data && response.data.purchases) {
      allPurchases = response.data.purchases;
    } else if (response.data && response.data.data) {
      allPurchases = response.data.data;
    }
    purchases.value = allPurchases.filter(p => String(p.invoice_id) === String(invoiceId));
  } catch (err) {
    error.value = 'Gagal memuat data pembelian';
    purchases.value = [];
  }
};

// Fetch invoices by project
const fetchInvoices = async (projectId) => {
  if (!projectId) {
    invoices.value = [];
    return;
  }
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/invoices", {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: projectId }
    });
    invoices.value = response.data.data || [];
  } catch (err) {
    invoices.value = [];
  }
};

const exportToPDF = () => {
  if (!selectedProject.value) {
    Swal.fire('Peringatan!', 'Pilih proyek terlebih dahulu', 'warning');
    return;
  }
  if (!selectedInvoice.value) {
    Swal.fire('Peringatan!', 'Pilih invoice terlebih dahulu', 'warning');
    return;
  }
  const token = sessionStorage.getItem("token");
  if (!token) {
    Swal.fire('Error', 'Sesi anda telah berakhir. Silakan login kembali.', 'error');
    return;
  }
  axios.get(`/api/termins/export-pdf/${selectedProject.value}`, {
    responseType: 'blob',
    headers: { Authorization: `Bearer ${token}` }
  }).then(response => {
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `termin_${new Date().toISOString().split('T')[0]}.pdf`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  }).catch(err => {
    Swal.fire('Error', err.response?.data?.message || 'Gagal download PDF', 'error');
  });
};

const exportToExcel = () => {
  if (!selectedProject.value) {
    Swal.fire('Peringatan!', 'Pilih proyek terlebih dahulu', 'warning');
    return;
  }
  if (!selectedInvoice.value) {
    Swal.fire('Peringatan!', 'Pilih invoice terlebih dahulu', 'warning');
    return;
  }
  const token = sessionStorage.getItem("token");
  if (!token) {
    Swal.fire('Error', 'Sesi anda telah berakhir. Silakan login kembali.', 'error');
    return;
  }
  axios.get(`/api/termins/export-excel/${selectedProject.value}`, {
    responseType: 'blob',
    headers: { Authorization: `Bearer ${token}` }
  }).then(response => {
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `termin_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  }).catch(err => {
    Swal.fire('Error', err.response?.data?.message || 'Gagal download Excel', 'error');
  });
};

const handleExcelImport = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('project_id', selectedProject.value);

  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.post(`${API_URL}/termins/import-excel`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });

    Swal.fire('Sukses!', response.data.message, 'success');
    await fetchTermins();
  } catch (error) {
    Swal.fire('Error!', error.response?.data?.message || 'Gagal mengimpor data', 'error');
  } finally {
    event.target.value = '';
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

.table .btn-group .btn i {
  font-size: 1rem;
  vertical-align: middle;
}
.table .btn-group .btn {
  padding: 2px 6px;
  line-height: 1;
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
