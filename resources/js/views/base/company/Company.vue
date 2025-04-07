<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-factory" /> Data Perusahaan
            <CButton color="primary" class="float-end" @click="openModal('tambah')">
              Tambah Perusahaan
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="w-100">
              <table ref="dataTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="nama">Nama Lengkap</CFormLabel>
                <CFormInput v-model="nama_lengkap" id="nama" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="email">Email</CFormLabel>
                <CFormInput v-model="email" id="email" required type="email" />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="no_telp">No. Telp</CFormLabel>
                <CFormInput v-model="no_telp" id="no_telp" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="alamat">Alamat</CFormLabel>
                <CFormInput v-model="alamat" id="alamat" required />
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
  import Swal from 'sweetalert2';
  import "datatables.net-dt/css/dataTables.dataTables.min.css";
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
  import "datatables.net-responsive-dt";
  import "datatables.net-buttons-dt";

  const dataTableRef = ref(null);
  const nama_lengkap = ref("");
  const alamat = ref("");
  const no_telp = ref("");
  const email = ref("");
  const companies = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Perusahaan");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);

  const fetchCompanies = async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await axios.get("/api/companies", {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      });
      companies.value = response.data;
      nextTick(() => initDataTable());
    } catch (err) {
      error.value = "Gagal memuat data perusahaan.";
      Swal.fire("Gagal", "Data tidak bisa dimuat", "error");
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().destroy();
    }

    $(dataTableRef.value).DataTable({
      data: companies.value,
      columns: [
        {
          title: "No",
          data: null,
          render: (data, type, row, meta) => meta.row + 1,
        },
        { title: "Nama Lengkap", data: "nama_lengkap" },
        { title: "Email", data: "email" },
        { title: "No. Telp", data: "no_telp" },
        { title: "Alamat", data: "alamat" },
        {
          title: "Aksi",
          data: null,
          render: (data, type, row) => `
            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
          `,
          orderable: false,
        },
      ],
      responsive: true,
      scrollX: true,
      destroy: true,
    });

    $(dataTableRef.value).on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const company = companies.value.find(c => c.id == id);
      if (company) openModal("edit", company);
    });

    $(dataTableRef.value).on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteCompany(id);
    });
  };

  const openModal = (mode, company = null) => {
    modalMode.value = mode;
    if (mode === "edit" && company) {
      nama_lengkap.value = company.nama_lengkap;
      alamat.value = company.alamat;
      no_telp.value = company.no_telp;
      email.value = company.email;
      editingId.value = company.id;
      modalTitle.value = "Edit Perusahaan";
      modalButtonText.value = "Update";
    } else {
      nama_lengkap.value = "";
      alamat.value = "";
      no_telp.value = "";
      email.value = "";
      editingId.value = null;
      modalTitle.value = "Tambah Perusahaan";
      modalButtonText.value = "Simpan";
    }
    showModal.value = true;
  };

  const closeModal = () => {
    showModal.value = false;
  };

  const handleSubmit = async () => {
    loading.value = true;
    error.value = "";

    const payload = {
      nama_lengkap: nama_lengkap.value,
      alamat: alamat.value,
      no_telp: no_telp.value,
      email: email.value,
    };

    try {
      const token = localStorage.getItem("token");

      if (modalMode.value === "edit") {
        await axios.put(`/api/companies/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire("Berhasil", "Data perusahaan diperbarui", "success");
      } else {
        await axios.post("/api/companies", payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire("Berhasil", "Data perusahaan ditambahkan", "success");
      }

      showModal.value = false;
      await fetchCompanies();
    } catch (err) {
      error.value = "Terjadi kesalahan saat menyimpan data.";
      Swal.fire("Gagal", "Cek kembali data inputan.", "error");
    } finally {
      loading.value = false;
    }
  };

  const deleteCompany = async (id) => {
    const result = await Swal.fire({
      title: "Yakin hapus?",
      text: "Data tidak bisa dikembalikan!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, hapus!",
      cancelButtonText: "Batal",
    });

    if (result.isConfirmed) {
      try {
        const token = localStorage.getItem("token");
        await axios.delete(`/api/companies/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire("Berhasil", "Data perusahaan dihapus", "success");
        fetchCompanies();
      } catch (err) {
        Swal.fire("Gagal", "Tidak bisa menghapus data", "error");
      }
    }
  };

  onMounted(fetchCompanies);
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
