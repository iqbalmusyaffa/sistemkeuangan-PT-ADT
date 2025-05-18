<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Unit
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah Unit
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="w-100">
              <table ref="unitTableRef" class="display nowrap"></table>
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
                <CFormLabel for="unit_name">Nama Unit</CFormLabel>
                <CFormInput v-model="unit_name" id="unit_name" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="unit_code">Kode Unit</CFormLabel>
                <CFormInput v-model="unit_code" id="unit_code" />
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

  const unitTableRef = ref(null);
  const unit_name = ref("");
  const unit_code = ref("");
  const units = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Unit");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);

  const fetchUnits = async () => {
    loading.value = true;
    error.value = "";

    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/units", {
        headers: { Authorization: `Bearer ${token}` },
      });

      units.value = Array.isArray(response.data)
        ? response.data
        : (response.data.data ? response.data.data : []);

      nextTick(() => {
        initDataTable();
      });
    } catch (err) {
      error.value = "Gagal memuat unit.";
      Swal.fire({ icon: "error", title: "Oops...", text: error.value });
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(unitTableRef.value)) {
      $(unitTableRef.value).DataTable().destroy();
    }

    $(unitTableRef.value).DataTable({
      data: units.value,
      columns: [
        {
          title: "No",
          data: null,
          render: (data, type, row, meta) => meta.row + 1,
        },
        { title: "Nama Unit", data: "unit_name" },
        { title: "Kode Unit", data: "unit_code" },
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

    $(unitTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const unit = units.value.find((u) => u.id == id);
      if (unit) openModal("edit", unit);
    });

    $(unitTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteUnit(id);
    });
  };

  const openModal = (mode, unit = null) => {
    modalMode.value = mode;
    if (mode === "edit" && unit) {
      unit_name.value = unit.unit_name;
      unit_code.value = unit.unit_code;
      editingId.value = unit.id;
      modalTitle.value = "Edit Unit";
      modalButtonText.value = "Update";
    } else {
      unit_name.value = "";
      unit_code.value = "";
      editingId.value = null;
      modalTitle.value = "Tambah Unit";
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

    if (!unit_name.value.trim()) {
      error.value = "Nama unit wajib diisi!";
      loading.value = false;
      return;
    }

    try {
      const token = sessionStorage.getItem("token");
      const payload = { unit_name: unit_name.value, unit_code: unit_code.value };

      if (modalMode.value === "edit") {
        await axios.put(`/api/units/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Unit diperbarui." });
      } else {
        await axios.post("/api/units", payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Unit ditambahkan." });
      }

      closeModal();
      await fetchUnits();
    } catch (err) {
      Swal.fire({ icon: "error", title: "Oops...", text: "Terjadi kesalahan." });
    } finally {
      loading.value = false;
    }
  };

  const deleteUnit = async (id) => {
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
        await axios.delete(`/api/units/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Unit dihapus." });
        await fetchUnits();
      } catch (err) {
        Swal.fire({ icon: "error", title: "Oops...", text: "Gagal menghapus unit." });
      }
    }
  };

  onMounted(fetchUnits);
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
