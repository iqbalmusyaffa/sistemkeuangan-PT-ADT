<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-briefcase" /> Manajemen Project
          <CButton color="primary" @click="openModal('tambah')" class="float-end">
            Tambah Project
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>
          <div class="w-100">
            <table ref="projectTableRef" class="display nowrap"></table>
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Modal Form -->
    <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="nama_customer">Nama Customer</CFormLabel>
              <CFormInput v-model="form.nama_customer" id="nama_customer" required />
            </CCol>
            <CCol md="6">
              <CFormLabel for="nama_project">Nama Project</CFormLabel>
              <CFormInput v-model="form.nama_project" id="nama_project" required />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="lokasi">Lokasi</CFormLabel>
              <CFormInput v-model="form.lokasi" id="lokasi" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="tanggal_mulai">Tanggal Mulai</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_mulai" id="tanggal_mulai" />
            </CCol>
            <CCol md="6">
              <CFormLabel for="tanggal_selesai">Tanggal Selesai</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_selesai" id="tanggal_selesai" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="anggaran_kontrak">Nilai Kontrak</CFormLabel>
              <CFormInput type="number" v-model.number="form.anggaran_kontrak" id="anggaran_kontrak" required min="0" />
            </CCol>
            <CCol md="6">
              <CFormLabel for="status_project">Status Project</CFormLabel>
              <CFormSelect v-model="form.status_project" id="status_project" required>
                <option value="">Pilih Status</option>
                <option value="Berjalan">Berjalan</option>
                <option value="Selesai">Selesai</option>
                <option value="Batal">Batal</option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="deskripsi">Deskripsi</CFormLabel>
              <CFormTextarea v-model="form.deskripsi" id="deskripsi" rows="3" />
            </CCol>
          </CRow>
          <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
        </CForm>
      </CModalBody>
    </CModal>
  </CRow>
</template>

<script setup>
import { ref, onMounted, nextTick } from "vue";
import axios from "axios";
import $ from "jquery";
import Swal from "sweetalert2";
import "datatables.net-dt/css/dataTables.dataTables.min.css";
import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
import "datatables.net-responsive-dt";
import { useRouter } from "vue-router";

const projectTableRef = ref(null);
const form = ref({
  nama_customer: "",
  nama_project: "",
  lokasi: "",
  tanggal_mulai: "",
  tanggal_selesai: "",
  anggaran_kontrak: 0,
  status_project: "Berjalan",
  deskripsi: ""
});

const projects = ref([]);
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const modalTitle = ref("Tambah Project");
const modalButtonText = ref("Simpan");
const modalMode = ref("tambah");
const editingId = ref(null);
const router = useRouter();

const fetchProjects = async () => {
  loading.value = true;
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/projects", {
      headers: { Authorization: `Bearer ${token}` },
    });

    projects.value = response.data;

    nextTick(() => {
      initDataTable();
    });
  } catch (err) {
    error.value = "Failed to load project data.";
    Swal.fire({ icon: "error", title: "Oops...", text: error.value });
  } finally {
    loading.value = false;
  }
};

// DataTable initialization
const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(projectTableRef.value)) {
    $(projectTableRef.value).DataTable().destroy();
  }

  $(projectTableRef.value).DataTable({
    data: projects.value,
    columns: [
      { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
      { title: "Customer", data: "nama_customer" },
      { title: "Project", data: "nama_project" },
      { title: "Lokasi", data: "lokasi" },
      { 
        title: "Tanggal Mulai", 
        data: "tanggal_mulai",
        render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-"
      },
      { 
        title: "Tanggal Selesai", 
        data: "tanggal_selesai",
        render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-"
      },
      {
        title: "Nilai Kontrak",
        data: "anggaran_kontrak",
        render: (data) => `Rp ${new Intl.NumberFormat('id-ID').format(data)}`
      },
      { 
        title: "Status", 
        data: "status_project",
        render: (data) => {
          const statusClasses = {
            'Berjalan': 'warning',
            'Selesai': 'success',
            'Batal': 'danger'
          };
          return `<span class="badge bg-${statusClasses[data]}">${data}</span>`;
        }
      },
      {
        title: "Aksi",
        data: null,
        render: (data, type, row) => `
          <button class="btn btn-sm btn-info detail-btn" data-id="${row.id}">
            <CIcon icon="cil-list" /> Detail
          </button>
          <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
            <CIcon icon="cil-pencil" /> Edit
          </button>
          <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
            <CIcon icon="cil-trash" /> Hapus
          </button>
        `,
      },
    ],
    responsive: true,
    scrollX: true,
    destroy: true,
  });

  $(projectTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
    const id = $(this).data("id");
    const project = projects.value.find((p) => p.id == id);
    if (project) openModal("edit", project);
  });

  $(projectTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
    const id = $(this).data("id");
    deleteProject(id);
  });

  $(projectTableRef.value).off("click", ".detail-btn").on("click", ".detail-btn", function () {
    const id = $(this).data("id");
    router.push(`/base/project/${id}/detail`);
  });
};

// Open Modal for Add/Edit
const openModal = (mode, project = null) => {
  modalMode.value = mode;
  if (mode === "edit" && project) {
    form.value = {
      nama_customer: project.nama_customer,
      nama_project: project.nama_project,
      lokasi: project.lokasi || "",
      tanggal_mulai: project.tanggal_mulai || "",
      tanggal_selesai: project.tanggal_selesai || "",
      anggaran_kontrak: project.anggaran_kontrak,
      status_project: project.status_project,
      deskripsi: project.deskripsi || ""
    };
    editingId.value = project.id;
    modalTitle.value = "Edit Project";
    modalButtonText.value = "Update";
  } else {
    form.value = {
      nama_customer: "",
      nama_project: "",
      lokasi: "",
      tanggal_mulai: "",
      tanggal_selesai: "",
      anggaran_kontrak: 0,
      status_project: "Berjalan",
      deskripsi: ""
    };
    editingId.value = null;
    modalTitle.value = "Tambah Project";
    modalButtonText.value = "Simpan";
  }
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = {
    nama_customer: "",
    nama_project: "",
    lokasi: "",
    tanggal_mulai: "",
    tanggal_selesai: "",
    anggaran_kontrak: 0,
    status_project: "Berjalan",
    deskripsi: ""
  };
};

const handleSubmit = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const headers = { Authorization: `Bearer ${token}` };
    
    if (modalMode.value === "edit") {
      await axios.put(`/api/projects/${editingId.value}`, form.value, { headers });
      Swal.fire({ icon: "success", title: "Success", text: "Project berhasil diupdate" });
    } else {
      await axios.post("/api/projects", form.value, { headers });
      Swal.fire({ icon: "success", title: "Success", text: "Project berhasil ditambahkan" });
    }
    
    closeModal();
    fetchProjects();
  } catch (err) {
    Swal.fire({ 
      icon: "error", 
      title: "Error", 
      text: err.response?.data?.message || "Terjadi kesalahan saat menyimpan data" 
    });
  }
};

const deleteProject = async (id) => {
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
      await axios.delete(`/api/projects/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      
      Swal.fire({ icon: "success", title: "Success", text: "Project berhasil dihapus" });
      fetchProjects();
    }
  } catch (err) {
    Swal.fire({ 
      icon: "error", 
      title: "Error", 
      text: err.response?.data?.message || "Terjadi kesalahan saat menghapus data" 
    });
  }
};

onMounted(() => {
  fetchProjects();
});
</script> 