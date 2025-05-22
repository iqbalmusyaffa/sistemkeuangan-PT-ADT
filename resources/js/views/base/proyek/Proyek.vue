<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-briefcase" /> Manajemen Proyek
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah Proyek
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="w-100">
              <table ref="proyekTableRef" class="display nowrap"></table>
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
                <CFormLabel for="nama_proyek">Nama Proyek</CFormLabel>
                <CFormInput v-model="form.nama_proyek" id="nama_proyek" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="nama_perusahaan">Nama Perusahaan</CFormLabel>
                <CFormInput v-model="form.nama_perusahaan" id="nama_perusahaan" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="email">Email</CFormLabel>
                <CFormInput type="email" v-model="form.email" id="email" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="alamat">Alamat</CFormLabel>
                <CFormInput v-model="form.alamat" id="alamat" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="no_telp">No. Telepon</CFormLabel>
                <CFormInput v-model="form.no_telp" id="no_telp" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="lokasi">Lokasi Proyek</CFormLabel>
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
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    id="anggaran_kontrak"
                    :value="displayAnggaranKontrak"
                    @input="onAnggaranInput"
                    required
                  />
                </div>
                <div v-if="showBudgetWarning" class="text-warning mt-2">
                  <CIcon icon="cil-warning" /> Nilai kontrak lebih kecil dari total pengeluaran proyek
                </div>
              </CCol>
              <CCol md="6">
                <CFormLabel for="status_project">Status Proyek</CFormLabel>
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
  import { ref, onMounted, nextTick, watch } from "vue";
  import axios from "axios";
  import $ from "jquery";
  import Swal from "sweetalert2";
  import "datatables.net-dt/css/dataTables.dataTables.min.css";
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
  import "datatables.net";
  import "datatables.net-responsive";
  import { useRouter } from "vue-router";

  const proyekTableRef = ref(null);
  const form = ref({
    nama_customer: "",
    nama_proyek: "",
    nama_perusahaan: "",
    alamat: "",
    no_telp: "",
    email: "",
    lokasi: "",
    tanggal_mulai: "",
    tanggal_selesai: "",
    anggaran_kontrak: 0,
    status_project: "Berjalan",
    deskripsi: ""
  });

  const proyeks = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Proyek");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);
  const router = useRouter();
  const displayAnggaranKontrak = ref('');
  const showBudgetWarning = ref(false);
  const filterAbove100M = ref(false);
  const filterBelow100M = ref(false);

  function formatRupiah(value) {
    if (!value) return '';
    return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }
  function unformatRupiah(value) {
    return Number(String(value).replace(/\./g, ''));
  }

watch(() => form.value.anggaran_kontrak, (val) => {
  displayAnggaranKontrak.value = formatRupiah(val);
});

function onAnggaranInput(e) {
  const raw = e.target.value.replace(/[^0-9]/g, '');
  form.value.anggaran_kontrak = Number(raw);
  // hapus update displayAnggaranKontrak di sini
}

  const fetchProyeks = async () => {
    loading.value = true;
    error.value = "";

    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/proyeks", {
        headers: { Authorization: `Bearer ${token}` },
      });

      if (response.data && response.data.status === 'success') {
        proyeks.value = response.data.data;
      } else {
        proyeks.value = [];
        error.value = "Data tidak valid";
      }

      nextTick(() => {
        initDataTable();
      });
    } catch (err) {
      console.error('Error fetching projects:', err);
      error.value = "Gagal memuat data proyek: " + (err.response?.data?.message || err.message);
      Swal.fire({
        icon: "error",
        title: "Oops...",
        text: error.value
      });
    } finally {
      loading.value = false;
    }
  };

  // DataTable initialization
  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(proyekTableRef.value)) {
      $(proyekTableRef.value).DataTable().destroy();
    }

    if (!proyeks.value || proyeks.value.length === 0) {
      return;
    }

    $(proyekTableRef.value).DataTable({
      data: proyeks.value,
      columns: [
        {
          title: "No",
          data: null,
          render: (data, type, row, meta) => meta.row + 1
        },
        {
          title: "Customer",
          data: "nama_customer"
        },
        {
          title: "Proyek",
          data: "nama_proyek"
        },
        {
          title: "Perusahaan",
          data: "nama_perusahaan"
        },
        {
          title: "Lokasi",
          data: "lokasi"
        },
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
          render: (data, type, row) => {
            const formattedAmount = `Rp ${new Intl.NumberFormat('id-ID').format(data)}`;
            const totalExpenses = row.total_expenses || 0;
            const percentage = (totalExpenses / data) * 100;

            if (percentage >= 80) {
              return `<div class="text-warning">
                <CIcon icon="cil-warning" /> ${formattedAmount}
                <small class="d-block">(${percentage.toFixed(1)}% terpakai)</small>
              </div>`;
            }
            return formattedAmount;
          }
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
              <i class="cil-list"></i> Detail
            </button>
            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
              <i class="cil-pencil"></i> Edit
            </button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
              <i class="cil-trash"></i> Hapus
            </button>
          `,
        },
      ],
      scrollX: true,
      scrollCollapse: true,
      fixedColumns: {
        left: 1,
        right: 1
      },
      dom: '<"top"lf>rt<"bottom"ip><"clear">',
      pageLength: 10,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]]
    });

    $(proyekTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const proyek = proyeks.value.find((p) => p.id == id);
      if (proyek) openModal("edit", proyek);
    });

    $(proyekTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteProyek(id);
    });

    $(proyekTableRef.value).off("click", ".detail-btn").on("click", ".detail-btn", function () {
      const id = $(this).data("id");
      router.push(`/base/proyek/${id}/detail`);
    });
  };

  // Open Modal for Add/Edit
  const openModal = (mode, proyek = null) => {
    modalMode.value = mode;
    if (mode === "edit" && proyek) {
      form.value = {
        nama_customer: proyek.nama_customer,
        nama_proyek: proyek.nama_proyek,
        nama_perusahaan: proyek.nama_perusahaan,
        alamat: proyek.alamat,
        no_telp: proyek.no_telp,
        email: proyek.email,
        lokasi: proyek.lokasi || "",
        tanggal_mulai: proyek.tanggal_mulai || "",
        tanggal_selesai: proyek.tanggal_selesai || "",
        anggaran_kontrak: proyek.anggaran_kontrak,
        status_project: proyek.status_project,
        deskripsi: proyek.deskripsi || ""
      };
      editingId.value = proyek.id;
      modalTitle.value = "Edit Proyek";
      modalButtonText.value = "Update";
    } else {
      form.value = {
        nama_customer: "",
        nama_proyek: "",
        nama_perusahaan: "",
        alamat: "",
        no_telp: "",
        email: "",
        lokasi: "",
        tanggal_mulai: "",
        tanggal_selesai: "",
        anggaran_kontrak: 0,
        status_project: "Berjalan",
        deskripsi: ""
      };
      editingId.value = null;
      modalTitle.value = "Tambah Proyek";
      modalButtonText.value = "Simpan";
    }
    showModal.value = true;
  };

  const closeModal = () => {
    showModal.value = false;
    showBudgetWarning.value = false;
    resetForm();
    form.value = {
      nama_customer: "",
      nama_proyek: "",
      nama_perusahaan: "",
      alamat: "",
      no_telp: "",
      email: "",
      lokasi: "",
      tanggal_mulai: "",
      tanggal_selesai: "",
      anggaran_kontrak: 0,
      status_project: "Berjalan",
      deskripsi: ""
    };
  };

  const handleSubmit = async () => {
    // Ambil total pengeluaran proyek
    let totalPengeluaran = 0;
    if (modalMode.value === "edit") {
      const proyek = proyeks.value.find((p) => p.id == editingId.value);
      totalPengeluaran = proyek?.total_expenses || 0;
    }
    const resetForm = () => {
    form.value = {
        nama_customer: "",
        nama_proyek: "",
        nama_perusahaan: "",
        alamat: "",
        no_telp: "",
        email: "",
        lokasi: "",
        tanggal_mulai: "",
        tanggal_selesai: "",
        anggaran_kontrak: 0,
        status_project: "Berjalan",
        deskripsi: ""
    };
    };

    // Tampilkan peringatan jika anggaran lebih kecil dari pengeluaran
    if (form.value.anggaran_kontrak < totalPengeluaran) {
      showBudgetWarning.value = true;
      Swal.fire({
        icon: 'warning',
        title: 'Anggaran Tidak Valid!',
        text: 'Nilai kontrak lebih kecil dari total pengeluaran proyek. Silakan periksa kembali.',
      });
      return;
    }

    try {
      const token = sessionStorage.getItem("token");
      const headers = { Authorization: `Bearer ${token}` };

      if (modalMode.value === "edit") {
        await axios.put(`/api/proyeks/${editingId.value}`, form.value, { headers });
        Swal.fire({ icon: "success", title: "Success", text: "Proyek berhasil diupdate" });
      } else {
        await axios.post("/api/proyeks", form.value, { headers });
        Swal.fire({ icon: "success", title: "Success", text: "Proyek berhasil ditambahkan" });
      }

      closeModal();
      fetchProyeks();
    } catch (err) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: err.response?.data?.message || "Terjadi kesalahan saat menyimpan data"
      });
    }
  };

  const deleteProyek = async (id) => {
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
        await axios.delete(`/api/proyeks/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });

        Swal.fire({ icon: "success", title: "Success", text: "Proyek berhasil dihapus" });
        fetchProyeks();
      }
    } catch (err) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: err.response?.data?.message || "Terjadi kesalahan saat menghapus data"
      });
    }
  };

  const resetOtherFilters = (type) => {
    if (type === 'above') {
      filterBelow100M.value = false;
    } else if (type === 'below') {
      filterAbove100M.value = false;
    }
    fetchProyeks();
  };

  const filterProyek = async (filter) => {
    loading.value = true;
    try {
      const response = await axios.get('/api/proyeks', { params: { filter } });
      proyeks.value = response.data.data;
    } catch (err) {
      console.error('Error filtering projects:', err);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Gagal memuat data proyek',
      });
    } finally {
      loading.value = false;
    }
  };

  onMounted(() => {
    fetchProyeks();
  });
  </script>

  <style scoped>
  .w-100 {
    width: 100%;
    overflow-x: auto;
  }

  .dataTables_wrapper {
    overflow-x: auto;
    position: relative;
  }

  table.display {
    width: 100% !important;
    min-width: 1000px;
  }

  /* Fixed columns styles */
  .dataTables_scroll {
    position: relative;
    clear: both;
    width: 100%;
  }

  .dataTables_scrollBody {
    overflow-x: auto;
    overflow-y: auto;
    max-height: none;
  }

  /* Fixed column styles */
  .fixed-columns {
    position: sticky;
    background: white;
    z-index: 1;
  }

  .fixed-columns-left {
    left: 0;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
  }

  .fixed-columns-right {
    right: 0;
    box-shadow: -2px 0 5px rgba(0,0,0,0.1);
  }

  /* Table cell styles */
  table.dataTable tbody td {
    white-space: nowrap;
    padding: 8px;
  }

  /* Button styles */
  .btn {
    margin: 0 2px;
  }

  /* Status badge styles */
  .badge {
    padding: 0.5em 0.75em;
    font-size: 0.875em;
  }

  .text-warning {
    color: #ffc107 !important;
  }

  .text-warning small {
    font-size: 0.8em;
    opacity: 0.8;
  }
  </style>
