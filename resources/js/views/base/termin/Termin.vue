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
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>

          <!-- Project Filter -->
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="project_filter">Pilih Project</CFormLabel>
              <CFormSelect 
                v-model="selectedProject" 
                id="project_filter" 
                @change="filterByProject"
              >
                <option value="">-- Pilih Project --</option>
                <option 
                  v-for="project in projects" 
                  :key="project.id" 
                  :value="project.id"
                  :data-nama-project="project.nama_project"
                >
                  {{ project.nama_customer }} - {{ project.nama_project }}
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
                Ganti Project
              </CButton>
            </CCol>
          </CRow>

          <!-- Tabel Termin -->
          <div v-if="selectedProject">
            <div class="w-100">
              <table ref="terminTableRef" class="display nowrap"></table>
            </div>

            <!-- Ringkasan Biaya -->
            <div class="total-section mt-3">
              <div class="card">
                <div class="card-body">
                  <h5>Ringkasan Termin:</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-sm">
                        <tr>
                          <td>Total Nilai Termin</td>
                          <td class="text-end">Rp {{ formatCurrency(totalTermin) }}</td>
                        </tr>
                        <tr>
                          <td>Total DP</td>
                          <td class="text-end">Rp {{ formatCurrency(totalDP) }}</td>
                        </tr>
                        <tr>
                          <td>Total Pelunasan</td>
                          <td class="text-end">Rp {{ formatCurrency(totalPelunasan) }}</td>
                        </tr>
                        <tr class="fw-bold">
                          <td>Total Keseluruhan</td>
                          <td class="text-end">Rp {{ formatCurrency(totalKeseluruhan) }}</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-info">
            Silakan pilih project terlebih dahulu untuk melihat data termin
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Modal Form -->
    <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="project_id">Project</CFormLabel>
              <CFormSelect v-model="form.project_id" id="project_id" required>
                <option value="">Pilih Project</option>
                <optgroup v-for="(projectGroup, customer) in groupedProjects" 
                         :key="customer" 
                         :label="customer">
                  <option v-for="project in projectGroup" 
                          :key="project.id" 
                          :value="project.id">
                    {{ project.nama_project }}
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
                />
              </div>
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
    <CModal :visible="showStatusModal" @close="closeStatusModal" title="Update Status Termin" size="md">
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

// Fetch Projects
const fetchProjects = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/projects", {
      headers: { Authorization: `Bearer ${token}` },
    });
    projects.value = response.data;
  } catch (err) {
    console.error("Failed to load projects:", err);
  }
};

const fetchTermins = async (projectId = null) => {
  loading.value = true;
  error.value = "";

  try {
    if (!projectId) {
      termins.value = [];
      return;
    }

    const token = sessionStorage.getItem("token");
    
    // Fetch both termins and purchases
    const [terminResponse, purchaseResponse] = await Promise.all([
      axios.get(`/api/projects/${projectId}/termins`, {
        headers: { Authorization: `Bearer ${token}` },
      }),
      axios.get(`/api/purchasematerials?project_id=${projectId}`, {
        headers: { Authorization: `Bearer ${token}` },
      })
    ]);

    termins.value = terminResponse.data;
    
    // Calculate total from purchases
    const totalPurchase = purchaseResponse.data.purchases.reduce((sum, purchase) => {
      return sum + Number(purchase.total_harga);
    }, 0);

    // Update form with total purchase value
    form.value.nilai_termin = totalPurchase;
    form.value.displayNilaiTermin = formatCurrency(totalPurchase);
    calculateValues();

    nextTick(() => {
      initDataTable();
    });
  } catch (err) {
    error.value = "Failed to load termin data.";
    Swal.fire({ icon: "error", title: "Oops...", text: error.value });
  } finally {
    loading.value = false;
  }
};

// Calculate DP and Pelunasan values
const calculateValues = () => {
  if (form.value.nilai_termin && form.value.dp_percentage) {
    form.value.nilai_dp = form.value.nilai_termin * (form.value.dp_percentage / 100);
    form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
    form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  }
};

// DataTable initialization
const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
    $(terminTableRef.value).DataTable().destroy();
  }

  $(terminTableRef.value).DataTable({
    data: termins.value,
    columns: [
      { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
      { 
        title: "Project", 
        data: null,
        render: (data, type, row) => {
          const project = row.project || {};
          return project.nama_project && project.nama_customer ? 
            `${project.nama_project} - ${project.nama_customer}` : 
            "-";
        }
      },
      { title: "Nama Termin", data: "nama_termin" },
      {
        title: "Nilai Termin",
        data: "nilai_termin",
        render: (data) => `Rp ${formatCurrency(data)}`
      },
      {
        title: "DP (%)",
        data: "dp_percentage",
        render: (data, type, row) => row.status_termin === "Lunas" ? "100%" : `${data}%`
      },
      {
        title: "Nilai DP",
        data: "nilai_dp",
        render: (data, type, row) => row.status_termin === "Lunas" ? 
          `Rp ${formatCurrency(row.nilai_termin)}` : 
          `Rp ${formatCurrency(data)}`
      },
      {
        title: "Nilai Pelunasan",
        data: "nilai_pelunasan",
        render: (data, type, row) => row.status_termin === "Lunas" ? 
          "Rp 0" : 
          `Rp ${formatCurrency(data)}`
      },
      {
        title: "Status",
        data: "status_termin",
        render: (data) => {
          let badgeClass = "bg-secondary";
          if (data === "DP Dibayar") badgeClass = "bg-warning";
          if (data === "Lunas") badgeClass = "bg-success";
          return `<span class="badge ${badgeClass}">${data}</span>`;
        }
      },
      {
        title: "Aksi",
        data: null,
        render: (data, type, row) => `
          <button class="btn btn-sm btn-info status-btn" data-id="${row.id}">Status</button>
          <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button>
          <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
        `,
      },
    ],
    responsive: true,
    scrollX: true,
    destroy: true,
  });

  $(terminTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
    const id = $(this).data("id");
    const termin = termins.value.find((t) => t.id == id);
    if (termin) openModal("edit", termin);
  });

  $(terminTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
    const id = $(this).data("id");
    deleteTermin(id);
  });

  $(terminTableRef.value).off("click", ".status-btn").on("click", ".status-btn", function () {
    const id = $(this).data("id");
    const termin = termins.value.find((t) => t.id == id);
    if (termin) openStatusModal(termin);
  });
};

const filterByProject = async () => {
  if (selectedProject.value) {
    await fetchTermins(selectedProject.value);
  } else {
    termins.value = [];
    if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
      $(terminTableRef.value).DataTable().destroy();
      $(terminTableRef.value).empty();
    }
  }
};

// Watch for changes in selectedProject
watch(selectedProject, async (newValue) => {
  await filterByProject();
});

// Open Modal for Add/Edit
const openModal = async (mode, termin = null) => {
  modalMode.value = mode;
  if (mode === "edit" && termin) {
    form.value = {
      project_id: termin.project_id,
      nama_termin: termin.nama_termin,
      nilai_termin: termin.nilai_termin,
      dp_percentage: termin.dp_percentage,
      nilai_dp: termin.nilai_dp,
      nilai_pelunasan: termin.nilai_pelunasan,
      tanggal_dp: termin.tanggal_dp || "",
      tanggal_pelunasan: termin.tanggal_pelunasan || "",
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
    // Fetch total purchase value for the selected project
    try {
      const token = sessionStorage.getItem("token");
      const projectId = selectedProject.value;
      const response = await axios.get(`/api/purchasematerials?project_id=${projectId}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      const totalPurchase = response.data.purchases.reduce((sum, purchase) => {
        return sum + Number(purchase.total_harga);
      }, 0);

      form.value = {
        project_id: selectedProject.value || "",
        nama_termin: "",
        nilai_termin: totalPurchase,
        dp_percentage: 50,
        nilai_dp: totalPurchase * 0.5,
        nilai_pelunasan: totalPurchase * 0.5,
        tanggal_dp: "",
        tanggal_pelunasan: "",
        status_termin: "Belum Dibayar",
        keterangan: "",
        displayNilaiTermin: formatCurrency(totalPurchase),
        displayNilaiDP: formatCurrency(totalPurchase * 0.5),
        displayNilaiPelunasan: formatCurrency(totalPurchase * 0.5)
      };
    } catch (err) {
      console.error("Failed to fetch purchase total:", err);
      Swal.fire({ 
        icon: "error", 
        title: "Oops...", 
        text: "Gagal mengambil data total pembelian" 
      });
    }
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
    error.value = "Project wajib dipilih!";
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

  calculateValues(); // Ensure values are calculated

  try {
    const token = sessionStorage.getItem("token");
    const payload = { ...form.value };

    if (modalMode.value === "edit") {
      await axios.put(`/api/termins/${editingId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin diperbarui." });
    } else {
      await axios.post("/api/termins", payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin ditambahkan." });
    }

    closeModal();
    await fetchTermins(selectedProject.value);
  } catch (err) {
    Swal.fire({ icon: "error", title: "Oops...", text: "Terjadi kesalahan." });
  } finally {
    loading.value = false;
  }
};

// Add watch for status_termin changes
watch(() => form.value.status_termin, (newStatus) => {
  if (newStatus === "Lunas") {
    form.value.nilai_pelunasan = 0;
    form.value.displayNilaiPelunasan = formatCurrency(0);
    
    // Update DP to match total nilai termin
    form.value.nilai_dp = form.value.nilai_termin;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_termin);
    form.value.dp_percentage = 100;
  }
});

// Modify handleStatusUpdate function
const handleStatusUpdate = async () => {
  loading.value = true;
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");
    
    // If status is changing to Lunas, update the values
    if (statusForm.value.status_termin === "Lunas") {
      const termin = termins.value.find(t => t.id === updatingStatusId.value);
      if (termin) {
        statusForm.value = {
          ...statusForm.value,
          nilai_pelunasan: 0,
          nilai_dp: termin.nilai_termin,
          dp_percentage: 100
        };
      }
    }

    await axios.put(`/api/termins/${updatingStatusId.value}/status`, statusForm.value, {
      headers: { Authorization: `Bearer ${token}` },
    });
    
    Swal.fire({ icon: "success", title: "Berhasil!", text: "Status termin diperbarui." });
    closeStatusModal();
    await fetchTermins(selectedProject.value);
  } catch (err) {
    Swal.fire({ icon: "error", title: "Oops...", text: "Terjadi kesalahan saat memperbarui status." });
  } finally {
    loading.value = false;
  }
};

// Delete Termin
const deleteTermin = async (id) => {
  const result = await Swal.fire({
    title: "Yakin ingin menghapus?",
    text: "Data tidak dapat dikembalikan!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Ya, hapus!",
    cancelButtonText: "Batal",
  });

  if (result.isConfirmed) {
    try {
      const token = sessionStorage.getItem("token");
      await axios.delete(`/api/termins/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data termin dihapus." });
      await fetchTermins(selectedProject.value);
    } catch (err) {
      Swal.fire({ icon: "error", title: "Oops...", text: "Gagal menghapus data termin." });
    }
  }
};

// Change Project (Reset selected project)
const changeProject = async () => {
  selectedProject.value = null;
  // Clear data and destroy DataTable
  termins.value = [];
  if ($.fn.DataTable.isDataTable(terminTableRef.value)) {
    $(terminTableRef.value).DataTable().destroy();
    $(terminTableRef.value).empty();
  }
};

// Format currency
const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID').format(value);
};

// Unformat currency
const unformatCurrency = (value) => {
  return Number(value.replace(/[^\d,-]/g, ''));
};

// Handle currency input
const handleNilaiTerminInput = (event) => {
  const unformattedValue = unformatCurrency(event.target.value);
  form.value.nilai_termin = unformattedValue;
  form.value.displayNilaiTermin = formatCurrency(unformattedValue);
  calculateValues();
};

// Add new function to handle DP value input
const handleNilaiDPInput = (event) => {
  const unformattedValue = unformatCurrency(event.target.value);
  form.value.nilai_dp = unformattedValue;
  form.value.displayNilaiDP = formatCurrency(unformattedValue);
  
  // Calculate DP percentage based on input value
  if (form.value.nilai_termin > 0) {
    form.value.dp_percentage = (unformattedValue / form.value.nilai_termin) * 100;
  }
  
  // Calculate pelunasan
  form.value.nilai_pelunasan = form.value.nilai_termin - unformattedValue;
  form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
};

// Modify computed properties for totals
const totalTermin = computed(() => {
  return termins.value.reduce((sum, t) => sum + Number(t.nilai_termin), 0);
});

const totalDP = computed(() => {
  return termins.value.reduce((sum, t) => {
    if (t.status_termin === "Lunas") {
      return sum + Number(t.nilai_termin); // Full amount for lunas
    }
    return sum + Number(t.nilai_dp);
  }, 0);
});

const totalPelunasan = computed(() => {
  return termins.value.reduce((sum, t) => {
    if (t.status_termin === "Lunas") {
      return sum + 0; // Zero for lunas
    } else if (t.status_termin === "DP Dibayar") {
      return sum + Number(t.nilai_pelunasan); // Show remaining amount
    } else {
      return sum + Number(t.nilai_pelunasan); // Show full pelunasan amount
    }
  }, 0);
});

const totalKeseluruhan = computed(() => {
  return termins.value.reduce((sum, t) => sum + Number(t.nilai_termin), 0);
});

// Add computed property for grouped projects
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

// Initial Fetching
onMounted(() => {
  fetchProjects();
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
</style> 