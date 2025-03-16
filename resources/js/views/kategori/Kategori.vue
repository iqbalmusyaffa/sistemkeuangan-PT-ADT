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
            <div class="w-100">
              <table ref="dataTableRef" class="display nowrap"></table>
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
  const kategori = ref("");
  const jenis = ref("pemasukan");
  const deskripsi = ref("");
  const categories = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Kategori");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah"); // 'tambah' or 'edit'
  const editingId = ref(null);

  const fetchCategories = async () => {
    loading.value = true;
    error.value = "";
    try {
      const response = await axios.get("/api/categories");
      categories.value = response.data;
      nextTick(() => initDataTable());
    } catch (err) {
      error.value = "Gagal memuat kategori.";
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Gagal memuat kategori.',
      });
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().destroy(); // Hapus instance DataTables yang lama
    }

    $(dataTableRef.value).DataTable({
      data: categories.value,
      columns: [
        {
          title: "No",
          data: null,
          orderable: false,
          render: function (data, type, row, meta) {
            return meta.row + 1; // Nomor urut dimulai dari 1
          },
        },
        { title: "Nama Kategori", data: "nama_kategori" },
        { title: "Jenis", data: "jenis" },
        { title: "Deskripsi", data: "deskripsi" },
        {
          title: "Aksi",
          data: null,
          orderable: false,
          render: function (data, type, row) {
            return `
              <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button>
              <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
            `;
          },
        },
      ],
      responsive: true, // Aktifkan fitur responsif
      autoWidth: false, // Nonaktifkan penyesuaian lebar otomatiss
      scrollX: true,    // Aktifkan scroll horizontal
      destroy: true,    // Pastikan instance lama dihancurkan
    });

    // Pasang event listener untuk tombol edit
    $(dataTableRef.value).on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const category = categories.value.find((cat) => cat.id == id);
      if (category) openModal('edit', category);
    });

    // Pasang event listener untuk tombol hapus
    $(dataTableRef.value).on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteCategory(id);
    });
  };

  const openModal = (mode, category = null) => {
    modalMode.value = mode;
    if (mode === 'edit' && category) {
      // Editing an existing category
      kategori.value = category.nama_kategori;
      jenis.value = category.jenis;
      deskripsi.value = category.deskripsi;
      editingId.value = category.id;
      modalTitle.value = "Edit Kategori";
      modalButtonText.value = "Update";
    } else {
      // Adding a new category
      kategori.value = "";
      jenis.value = "pemasukan";
      deskripsi.value = "";
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

      if (modalMode.value === 'edit') {
        await axios.put(`/api/categories/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Kategori berhasil diperbarui.',
        });
      } else {
        await axios.post("/api/categories", payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Kategori berhasil ditambahkan.',
        });
      }
      showModal.value = false;
      await fetchCategories();
      initDataTable(); // Inisialisasi ulang DataTables setelah data berubah
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Terjadi kesalahan, silakan coba lagi.',
      });
    } finally {
      loading.value = false;
    }
  };

  const deleteCategory = async (id) => {
    const result = await Swal.fire({
      title: 'Apakah Anda yakin?',
      text: "Anda tidak dapat mengembalikan data ini!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
    });

    if (result.isConfirmed) {
      try {
        const token = localStorage.getItem("token");
        await axios.delete(`/api/categories/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'Kategori berhasil dihapus.',
        });
        fetchCategories();
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Gagal menghapus kategori.',
        });
      }
    }
  };

  onMounted(fetchCategories);
  </script>

  <style scoped>
  .w-100 {
    width: 100%;
    overflow-x: auto; /* Memungkinkan tabel di-scroll horizontal */
  }

  .dataTables_wrapper {
    overflow-x: auto; /* Memastikan wrapper DataTables dapat di-scroll */
  }

  table.display {
    width: 100% !important; /* Pastikan tabel mengambil lebar penuh */
  }
  </style>
