<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Merek
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah Merek
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="w-100">
              <table ref="brandTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Form -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="name">Nama Merek</CFormLabel>
                <CFormInput v-model="name" id="name" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="deskripsi">Deskripsi</CFormLabel>
                <CFormInput v-model="deskripsi" id="deskripsi" />
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

  const brandTableRef = ref(null);
  const name = ref("");
  const deskripsi = ref("");
  const brands = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Merek");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);

  const fetchBrands = async () => {
    loading.value = true;
    error.value = "";

    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/mereks", {
        headers: { Authorization: `Bearer ${token}` },
      });

      brands.value = response.data;

      nextTick(() => {
        initDataTable();
      });
    } catch (err) {
      error.value = "Gagal memuat merek.";
      Swal.fire({ icon: "error", title: "Oops...", text: error.value });
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(brandTableRef.value)) {
      $(brandTableRef.value).DataTable().destroy();
    }

    $(brandTableRef.value).DataTable({
      data: brands.value,
      columns: [
        {
          title: "No",
          data: null,
          render: (data, type, row, meta) => meta.row + 1,
        },
        { title: "Nama Merek", data: "name" },
        { title: "Deskripsi", data: "deskripsi" },
        {
          title: "Aksi",
          data: null,
          render: (data, type, row) => `
            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
          `,
        },
      ],
      responsive: true,
      scrollX: true,
      destroy: true,
    });

    $(brandTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const brand = brands.value.find((b) => b.id == id);
      if (brand) openModal("edit", brand);
    });

    $(brandTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteBrand(id);
    });
  };

  const openModal = (mode, brand = null) => {
    modalMode.value = mode;
    if (mode === "edit" && brand) {
      name.value = brand.name;
      deskripsi.value = brand.deskripsi;
      editingId.value = brand.id;
      modalTitle.value = "Edit Merek";
      modalButtonText.value = "Update";
    } else {
      name.value = "";
      deskripsi.value = "";
      editingId.value = null;
      modalTitle.value = "Tambah Merek";
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

    if (!name.value.trim()) {
      error.value = "Nama merek wajib diisi!";
      loading.value = false;
      return;
    }

    try {
      const token = sessionStorage.getItem("token");
      const payload = { name: name.value, deskripsi: deskripsi.value };

      if (modalMode.value === "edit") {
        await axios.put(`/api/mereks/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Merek diperbarui." });
      } else {
        await axios.post("/api/mereks", payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Merek ditambahkan." });
      }

      closeModal();
      await fetchBrands();
    } catch (err) {
      Swal.fire({ icon: "error", title: "Oops...", text: "Terjadi kesalahan." });
    } finally {
      loading.value = false;
    }
  };

  const deleteBrand = async (id) => {
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
        await axios.delete(`/api/mereks/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Merek dihapus." });
        await fetchBrands();
      } catch (err) {
        Swal.fire({ icon: "error", title: "Oops...", text: "Gagal menghapus merek." });
      }
    }
  };

  onMounted(fetchBrands);
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
