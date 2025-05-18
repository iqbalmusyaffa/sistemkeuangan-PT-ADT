<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Kategori Jasa
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah Kategori Jasa
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>

            <div class="w-100">
              <table ref="categoryTableRef" class="display nowrap"></table>
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
                <CFormSelect v-model="jenis" id="jenis" disabled>
                  <option value="pengeluaran">Pengeluaran</option>
                </CFormSelect>
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="harga">Harga Jasa</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput v-model="formattedHarga" id="harga" @input="updateHarga" required />
                </div>
              </CCol>
              <CCol md="6">
                <CFormLabel for="unit">Unit</CFormLabel>
                <CFormSelect v-model="unit_id" id="unit" required>
                  <option value="">Pilih Unit</option>
                  <option v-for="unit in filteredUnits" :key="unit.id" :value="unit.id">{{ unit.unit_name }}</option>
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
        </CModalBody>
      </CModal>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted, nextTick, computed } from "vue";
  import axios from "axios";
  import $ from "jquery";
  import Swal from "sweetalert2";
  import "datatables.net-dt/css/dataTables.dataTables.min.css";
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
  import "datatables.net-responsive-dt";

  const categoryTableRef = ref(null);
  const kategori = ref("");
  const jenis = ref("pengeluaran");
  const deskripsi = ref("");
  const harga = ref(0);
  const unit_id = ref("");
  const units = ref([]);
  const categories = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Kategori Jasa");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);

  const fetchCategories = async () => {
    loading.value = true;
    error.value = "";

    try {
      const token = sessionStorage.getItem("token");

      const [categoryRes, unitRes] = await Promise.all([
        axios.get("/api/service-categories", { headers: { Authorization: `Bearer ${token}` } }),
        axios.get("/api/units", { headers: { Authorization: `Bearer ${token}` } }),
      ]);

      categories.value = Array.isArray(categoryRes.data)
        ? categoryRes.data
        : (categoryRes.data.data ? categoryRes.data.data : []);
      units.value = Array.isArray(unitRes.data)
        ? unitRes.data
        : (unitRes.data.data ? unitRes.data.data : []);

      nextTick(() => {
        initDataTable();
      });
    } catch (err) {
      error.value = "Gagal memuat data.";
      Swal.fire({ icon: "error", title: "Oops...", text: "Gagal memuat data." });
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
    const data = categories.value;

    if ($.fn.DataTable.isDataTable(categoryTableRef.value)) {
      $(categoryTableRef.value).DataTable().destroy();
    }

    $(categoryTableRef.value).DataTable({
      data,
      columns: [
        { title: "No", data: null, orderable: false, render: (data, type, row, meta) => meta.row + 1 },
        { title: "Nama Kategori", data: "nama_kategori" },
        { title: "Harga", data: "harga", render: (data) => `Rp ${parseInt(data).toLocaleString('de-DE')}` },
        { title: "Unit", data: "unit.unit_name" },
        { title: "Deskripsi", data: "deskripsi" },
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

    $(categoryTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const category = categories.value.find((cat) => cat.id == id);
      if (category) openModal("edit", category);
    });

    $(categoryTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteCategory(id);
    });
  };

  const openModal = (mode, category = null) => {
    modalMode.value = mode;
    if (mode === "edit" && category) {
      kategori.value = category.nama_kategori;
      harga.value = category.harga;
      deskripsi.value = category.deskripsi;
      unit_id.value = category.unit_id;
      editingId.value = category.id;
      modalTitle.value = "Edit Kategori Jasa";
      modalButtonText.value = "Update";
    } else {
      kategori.value = "";
      harga.value = 0;
      deskripsi.value = "";
      unit_id.value = "";
      editingId.value = null;
      modalTitle.value = "Tambah Kategori Jasa";
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

    if (!kategori.value.trim() || !unit_id.value) {
      error.value = "Nama kategori dan unit wajib diisi!";
      loading.value = false;
      return;
    }

    try {
      const token = sessionStorage.getItem('token');
      const payload = {
        nama_kategori: kategori.value,
        jenis: jenis.value,
        harga: harga.value,
        deskripsi: deskripsi.value,
        unit_id: unit_id.value,
      };

      if (modalMode.value === "edit") {
        await axios.put(`/api/service-categories/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Kategori berhasil diperbarui." });
      } else {
        await axios.post("/api/service-categories", payload, {
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
        const token = sessionStorage.getItem('token');
        await axios.delete(`/api/service-categories/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({ icon: "success", title: "Berhasil!", text: "Kategori berhasil dihapus." });
        await fetchCategories();
      } catch (err) {
        Swal.fire({ icon: "error", title: "Oops...", text: "Gagal menghapus kategori." });
      }
    }
  };

  const formattedHarga = computed({
    get: () => {
      return harga.value ? parseInt(harga.value).toLocaleString('de-DE') : "0";
    },
    set: (newValue) => {
      const numericValue = newValue.replace(/[^0-9]/g, '');
      harga.value = numericValue ? parseInt(numericValue) : 0;
    }
  });

  const updateHarga = (event) => {
    const inputValue = event.target.value;
    const numericValue = inputValue.replace(/[^0-9]/g, '');
    harga.value = numericValue ? parseInt(numericValue) : 0;
  };

  const filteredUnits = computed(() => {
    return units.value.filter(unit => ['set', 'jasa', 'transaksi'].includes(unit.unit_name.toLowerCase()));
  });

  onMounted(fetchCategories);
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
  .input-group-text {
    background-color: #f8f9fa;
    border: 1px solid #ced4da;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #495057;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  </style>
