<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Kategori
            <CButton color="primary" @click="showModal = true" class="float-end">Tambah Kategori</CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="mt-4">
              <DataTable
                :data="categories"
                :columns="columns"
                :options="dataTableOptions"
                :key="dataTableKey"
              />
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal for Adding/Editing Category -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle" destroyOnClose>
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
            <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
          </CForm>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
        </CModalBody>
      </CModal>
    </CRow>
  </template>
<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import DataTable from "datatables.net-vue3";
import DataTablesCore from "datatables.net-dt";
import DataTablesResponsive from "datatables.net-responsive-dt";

// Register DataTable Plugins
DataTable.use(DataTablesCore);
DataTable.use(DataTablesResponsive);

const kategori = ref("");
const jenis = ref("pemasukan");
const deskripsi = ref("");
const categories = ref([]);
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const dataTableKey = ref(0);
const isEditing = ref(false);
const editingId = ref(null);
const modalTitle = ref("Tambah Kategori");
const modalButtonText = ref("Simpan");

const columns = [
  { title: "Nama Kategori", data: "nama_kategori", responsivePriority: 1 },
  { title: "Jenis", data: "jenis", responsivePriority: 2 },
  { title: "Deskripsi", data: "deskripsi", responsivePriority: 3 },
  {
    title: "Aksi",
    data: null,
    orderable: false,
    responsivePriority: 4,
    render: (data, type, row) => {
      return `<button class='btn btn-sm btn-primary edit-btn' data-id='${row.id}'>Edit</button>
              <button class='btn btn-sm btn-danger delete-btn' data-id='${row.id}'>Hapus</button>`;
    },
  },
];

const dataTableOptions = {
  paging: true,
  searching: true,
  ordering: true,
  info: true,
  autoWidth: false,
  responsive: true,
  scrollX: true,
};

const fetchCategories = async () => {
  loading.value = true;
  error.value = "";
  try {
    const response = await axios.get("/api/categories");
    categories.value = response.data;
    dataTableKey.value++; // Refresh table
  } catch (err) {
    error.value = "Gagal memuat kategori.";
  } finally {
    loading.value = false;
  }
};

const openModal = (category = null) => {
  if (category) {
    kategori.value = category.nama_kategori;
    jenis.value = category.jenis;
    deskripsi.value = category.deskripsi;
    isEditing.value = true;
    editingId.value = category.id;
    modalTitle.value = "Edit Kategori";
    modalButtonText.value = "Update";
  } else {
    kategori.value = "";
    jenis.value = "pemasukan";
    deskripsi.value = "";
    isEditing.value = false;
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
    const token = localStorage.getItem("token");
    const payload = { nama_kategori: kategori.value, jenis: jenis.value, deskripsi: deskripsi.value };

    if (isEditing.value) {
      await axios.put(`/api/categories/${editingId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
    } else {
      await axios.post("/api/categories", payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
    }
    showModal.value = false;
    fetchCategories();
  } catch (err) {
    error.value = "Terjadi kesalahan, silakan coba lagi.";
  } finally {
    loading.value = false;
  }
};

const handleTableClick = (event) => {
  const target = event.target;
  if (target.classList.contains("edit-btn")) {
    const id = target.getAttribute("data-id");
    const category = categories.value.find((cat) => cat.id == id);
    if (category) openModal(category);
  } else if (target.classList.contains("delete-btn")) {
    const id = target.getAttribute("data-id");
    deleteCategory(id);
  }
};

const deleteCategory = async (id) => {
  if (!confirm("Apakah Anda yakin ingin menghapus kategori ini?")) return;
  try {
    const token = localStorage.getItem("token");
    await axios.delete(`/api/categories/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    fetchCategories();
  } catch (err) {
    alert("Gagal menghapus kategori.");
  }
};

onMounted(() => {
  fetchCategories();
  document.addEventListener("click", handleTableClick);
});
</script>

<style scoped>
.mt-4 {
  margin-top: 1rem;
}
</style>
