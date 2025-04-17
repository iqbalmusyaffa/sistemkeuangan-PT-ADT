<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-cart" /> Pembelian Material
          <CButton color="primary" @click="openModal('tambah')" class="float-end">
            Tambah Pembelian
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>
          <div class="w-100">
            <table ref="pembelianTableRef" class="display nowrap"></table>
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
              <CFormLabel for="item">Nama Item</CFormLabel>
              <CFormInput v-model="form.item" id="item" required />
            </CCol>
            <CCol md="6">
              <CFormLabel for="merek_id">Merek</CFormLabel>
              <CFormSelect v-model="form.merek_id" id="merek_id" required>
                <option value="">Pilih Merek</option>
                <option v-for="merek in mereks" :key="merek.id" :value="merek.id">
                  {{ merek.merek_name }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="type">Tipe</CFormLabel>
              <CFormInput v-model="form.type" id="type" required />
            </CCol>
            <CCol md="6">
              <CFormLabel for="unit_id">Unit</CFormLabel>
              <CFormSelect v-model="form.unit_id" id="unit_id" required>
                <option value="">Pilih Unit</option>
                <option v-for="unit in units" :key="unit.id" :value="unit.id">
                  {{ unit.unit_name }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="spesifikasi">Spesifikasi</CFormLabel>
              <CFormTextarea v-model="form.spesifikasi" id="spesifikasi" rows="2" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="4">
              <CFormLabel for="qty">Jumlah</CFormLabel>
              <CFormInput
                type="number"
                v-model.number="form.qty"
                id="qty"
                required
                @input="calculateTotal"
                min="1"
              />
            </CCol>
            <CCol md="4">
              <CFormLabel for="harga">Harga</CFormLabel>
              <CFormInput
                type="number"
                v-model.number="form.harga"
                id="harga"
                required
                @input="calculateTotal"
                min="0"
              />
            </CCol>
            <CCol md="4">
              <CFormLabel for="total_harga">Total Harga</CFormLabel>
              <CFormInput
                type="number"
                v-model.number="form.total_harga"
                id="total_harga"
                readonly
                class="bg-light"
              />
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
import { ref, onMounted, nextTick, watch } from "vue";
import axios from "axios";
import $ from "jquery";
import Swal from "sweetalert2";
import "datatables.net-dt/css/dataTables.dataTables.min.css";
import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
import "datatables.net-responsive-dt";

const pembelianTableRef = ref(null);
const form = ref({
  item: "",
  merek_id: "",
  type: "",
  spesifikasi: "",
  unit_id: "",
  qty: 0,
  harga: 0,
  total_harga: 0,
  deskripsi: ""
});
const mereks = ref([]);
const units = ref([]);
const pembelians = ref([]);
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const modalTitle = ref("Tambah Pembelian");
const modalButtonText = ref("Simpan");
const modalMode = ref("tambah");
const editingId = ref(null);

const fetchMereks = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/mereks", {
      headers: { Authorization: `Bearer ${token}` },
    });
    mereks.value = response.data;
  } catch (err) {
    // console.error("Gagal memuat merek:", err);
  }
};

const fetchUnits = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/units", {
      headers: { Authorization: `Bearer ${token}` },
    });
    units.value = response.data;
  } catch (err) {
    // console.error("Gagal memuat unit:", err);
  }
};

const fetchPembelians = async () => {
  loading.value = true;
  error.value = "";

  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/purchasematerials", {
      headers: { Authorization: `Bearer ${token}` },
    });

    pembelians.value = response.data;

    nextTick(() => {
      initDataTable();
    });
  } catch (err) {
    error.value = "Gagal memuat data pembelian.";
    Swal.fire({ icon: "error", title: "Oops...", text: error.value });
  } finally {
    loading.value = false;
  }
};

const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(pembelianTableRef.value)) {
    $(pembelianTableRef.value).DataTable().destroy();
  }

  $(pembelianTableRef.value).DataTable({
    data: pembelians.value,
    columns: [
      {
        title: "No",
        data: null,
        render: (data, type, row, meta) => meta.row + 1,
      },
      { title: "Item", data: "item" },
      {
        title: "Merek",
        data: "merek",
        render: (data) => data ? data.merek_name : "-"
      },
      { title: "Tipe", data: "type" },
      {
        title: "Unit",
        data: "unit",
        render: (data) => data ? data.unit_name : "-"
      },
      {
        title: "Jumlah",
        data: "qty",
        render: (data) => data.toLocaleString()
      },
      {
        title: "Harga",
        data: "harga",
        render: (data) => `Rp ${data.toLocaleString()}`
      },
      {
        title: "Total",
        data: "total_harga",
        render: (data) => `Rp ${data.toLocaleString()}`
      },
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

  $(pembelianTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
    const id = $(this).data("id");
    const pembelian = pembelians.value.find((p) => p.id == id);
    if (pembelian) openModal("edit", pembelian);
  });

  $(pembelianTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
    const id = $(this).data("id");
    deletePembelian(id);
  });
};

const calculateTotal = () => {
  // Ensure qty and harga are numbers
  const qty = parseFloat(form.value.qty) || 0;
  const harga = parseFloat(form.value.harga) || 0;

  // Calculate total
  form.value.total_harga = qty * harga;
};

// Watch for changes in qty and harga to automatically calculate total
watch(() => form.value.qty, () => {
  calculateTotal();
});

watch(() => form.value.harga, () => {
  calculateTotal();
});

const openModal = (mode, pembelian = null) => {
  modalMode.value = mode;
  if (mode === "edit" && pembelian) {
    form.value = {
      item: pembelian.item,
      merek_id: pembelian.merek_id,
      type: pembelian.type,
      spesifikasi: pembelian.spesifikasi || "",
      unit_id: pembelian.unit_id,
      qty: pembelian.qty,
      harga: pembelian.harga,
      total_harga: pembelian.total_harga,
      deskripsi: pembelian.deskripsi || ""
    };
    editingId.value = pembelian.id;
    modalTitle.value = "Edit Pembelian";
    modalButtonText.value = "Update";
  } else {
    form.value = {
      item: "",
      merek_id: "",
      type: "",
      spesifikasi: "",
      unit_id: "",
      qty: 0,
      harga: 0,
      total_harga: 0,
      deskripsi: ""
    };
    editingId.value = null;
    modalTitle.value = "Tambah Pembelian";
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

  if (!form.value.item.trim()) {
    error.value = "Nama item wajib diisi!";
    loading.value = false;
    return;
  }

  if (!form.value.merek_id) {
    error.value = "Merek wajib dipilih!";
    loading.value = false;
    return;
  }

  if (!form.value.type.trim()) {
    error.value = "Tipe wajib diisi!";
    loading.value = false;
    return;
  }

  if (!form.value.unit_id) {
    error.value = "Unit wajib dipilih!";
    loading.value = false;
    return;
  }

  if (!form.value.qty || form.value.qty <= 0) {
    error.value = "Jumlah harus lebih dari 0!";
    loading.value = false;
    return;
  }

  if (!form.value.harga || form.value.harga <= 0) {
    error.value = "Harga harus lebih dari 0!";
    loading.value = false;
    return;
  }

  // Ensure total_harga is calculated before submission
  calculateTotal();

  try {
    const token = sessionStorage.getItem("token");
    const payload = { ...form.value };

    if (modalMode.value === "edit") {
      await axios.put(`/api/purchasematerials/${editingId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data pembelian diperbarui." });
    } else {
      await axios.post("/api/purchasematerials", payload, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data pembelian ditambahkan." });
    }

    closeModal();
    await fetchPembelians();
  } catch (err) {
    Swal.fire({ icon: "error", title: "Oops...", text: "Terjadi kesalahan." });
  } finally {
    loading.value = false;
  }
};

const deletePembelian = async (id) => {
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
      await axios.delete(`/api/purchasematerials/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire({ icon: "success", title: "Berhasil!", text: "Data pembelian dihapus." });
      await fetchPembelians();
    } catch (err) {
      Swal.fire({ icon: "error", title: "Oops...", text: "Gagal menghapus data pembelian." });
    }
  }
};

onMounted(() => {
  fetchMereks();
  fetchUnits();
  fetchPembelians();
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
