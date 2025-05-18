<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Kategori
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah Kategori
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>

            <h5 class="mb-2">Kategori Pemasukan</h5>
            <div class="w-100 mb-4">
              <table ref="incomeTableRef" class="display nowrap"></table>
            </div>

            <h5 class="mb-2">Kategori Pengeluaran</h5>
            <div class="w-100">
              <table ref="expenseTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Form -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="kategori">Nama Kategori</CFormLabel>
                <CFormInput v-model="kategori" id="kategori" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="jenis">Jenis</CFormLabel>
                <CFormSelect v-model="jenis" id="jenis">
                  <option value="pemasukan">Pemasukan</option>
                  <option value="pengeluaran">Pengeluaran</option>
                </CFormSelect>
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="deskripsi">Deskripsi</CFormLabel>
                <CFormInput v-model="deskripsi" id="deskripsi" />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="unit_id">Unit</CFormLabel>
                <CFormSelect v-model="unit_id" id="unit_id">
                  <option value="">Tanpa Unit</option>
                  <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.unit_name }}</option>
                </CFormSelect>
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

const incomeTableRef = ref(null);
const expenseTableRef = ref(null);
const kategori = ref("");
const jenis = ref("pemasukan");
const deskripsi = ref("");
const categories = ref([]);
const units = ref([]);
const unit_id = ref("");
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const modalTitle = ref("Tambah Kategori");
const modalButtonText = ref("Simpan");
const modalMode = ref("tambah");
const editingId = ref(null);

const fetchUnits = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/units", {
      headers: { Authorization: `Bearer ${token}` },
    });
    units.value = Array.isArray(response.data) ? response.data : (response.data.data || []);
  } catch (err) {
    units.value = [];
  }
};

const fetchCategories = async () => {
  loading.value = true;
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");

    const response = await axios.get("/api/kategori", {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    categories.value = Array.isArray(response.data)
      ? response.data
      : (response.data.data ? response.data.data : []);

    nextTick(() => {
      initDataTable("pemasukan");
      initDataTable("pengeluaran");
    });
  } catch (err) {
    error.value = "Gagal memuat kategori.";
    Swal.fire({
      icon: "error",
      title: "Oops...",
      text: "Gagal memuat kategori.",
    });
  } finally {
    loading.value = false;
  }
};

const getUnitName = (unitId) => {
  if (!unitId) return "-";
  const unit = units.value.find(u => String(u.id) === String(unitId));
  return unit ? unit.unit_name : "-";
};

const initDataTable = (type) => {
  const data = categories.value.filter(cat => cat.jenis === type);
  const ref = type === 'pemasukan' ? incomeTableRef : expenseTableRef;

  if ($.fn.DataTable.isDataTable(ref.value)) {
    $(ref.value).DataTable().destroy();
  }

  $(ref.value).DataTable({
    data,
    columns: [
      {
        title: "No",
        data: null,
        orderable: false,
        render: (data, type, row, meta) => meta.row + 1,
      },
      { title: "Nama Kategori", data: "nama_kategori" },
      { title: "Jenis", data: "jenis" },
      { title: "Deskripsi", data: "deskripsi" },
      { title: "Unit", data: "unit_id", render: (data) => getUnitName(data) },
      {
        title: "Aksi",
        data: null,
        orderable: false,
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

  $(ref.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
    const id = $(this).data("id");
    const category = categories.value.find((cat) => cat.id == id);
    if (category) openModal("edit", category);
  });

  $(ref.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
    const id = $(this).data("id");
    deleteCategory(id);
  });
};

const openModal = (mode, category = null) => {
  modalMode.value = mode;
  if (mode === "edit" && category) {
    kategori.value = category.nama_kategori;
    jenis.value = category.jenis;
    deskripsi.value = category.deskripsi;
    unit_id.value = category.unit_id || "";
    editingId.value = category.id;
    modalTitle.value = "Edit Kategori";
    modalButtonText.value = "Update";
  } else {
    kategori.value = "";
    jenis.value = "pemasukan";
    deskripsi.value = "";
    unit_id.value = "";
    editingId.value = null;
    modalTitle.value = "Tambah Kategori";
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

  if (!kategori.value.trim()) {
    error.value = "Nama kategori wajib diisi!";
    loading.value = false;
    return;
  }

  try {
    const token = sessionStorage.getItem('token')
    const payload = { nama_kategori: kategori.value, jenis: jenis.value, deskripsi: deskripsi.value, unit_id: unit_id.value || null };

    if (modalMode.value === "edit") {
      await axios.put(`/api/kategori/${editingId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Kategori berhasil diperbarui." });
    } else {
      await axios.post("/api/kategori", payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Kategori berhasil ditambahkan." });
    }
    showModal.value = false;
    await fetchCategories();
  } catch (err) {
    Swal.fire({ icon: "error", title: "Oops...", text: "Terjadi kesalahan, silakan coba lagi." });
  } finally {
    loading.value = false;
  }
};

const deleteCategory = async (id) => {
  const result = await Swal.fire({
    title: "Apakah Anda yakin?",
    text: "Data tidak dapat dikembalikan!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Ya, hapus!",
    cancelButtonText: "Batal",
  });

  if (result.isConfirmed) {
    try {
      const token = sessionStorage.getItem('token')
      await axios.delete(`/api/kategori/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Kategori berhasil dihapus." });
      await fetchCategories();
    } catch (err) {
      Swal.fire({ icon: "error", title: "Oops...", text: "Gagal menghapus kategori." });
    }
  }
};

onMounted(() => {
  fetchUnits();
  fetchCategories();
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

