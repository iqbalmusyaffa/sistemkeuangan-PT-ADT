<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-cart" /> Pembelian Material
            <CButton color="primary" @click="openModal('tambah')" class="float-end" :disabled="!selectedProject">
              Tambah Pembelian
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>

           <!-- Project Filter -->
         <!-- Project Filter -->
<CRow class="mb-3">
  <CCol md="6">
    <CFormLabel for="project_filter">Pilih Project</CFormLabel>
    <CFormSelect
      v-model="selectedProject"
      id="project_filter"
      @change="filterByProject"
    >
      <option value="">-- Pilih Project --</option>
      <option
        v-for="project in projects"
        :key="project.id"
        :value="project.id"
      >
        {{ project.nama_customer }} - {{ project.nama_proyek }}
      </option>
    </CFormSelect>
  </CCol>
            <!-- Ganti Project Button -->
            <CCol md="6" class="text-end">
              <CButton
                color="secondary"
                v-if="selectedProject"
                @click="changeProject"
                class="mt-2"
              >
                Ganti Project
              </CButton>
            </CCol>

          </CRow>



            <!-- Tabel Pembelian -->
         <!-- Data Table for Purchases -->
<div v-if="selectedProject">
  <table ref="pembelianTableRef" class="display nowrap"></table>

            <div class="total-section mt-3">
              <div class="card">
                <div class="card-body">
                  <h5>Ringkasan Biaya:</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <table class="table table-sm">
                        <tr>
                          <td>Total Material</td>
                          <td class="text-end">Rp {{ formatCurrency(totalMaterial) }}</td>
                        </tr>
                        <tr>
                          <td>Total Jasa</td>
                          <td class="text-end">Rp {{ formatCurrency(totalJasa) }}</td>
                        </tr>
                        <tr>
                          <td>Total Jasa Lain-lain</td>
                          <td class="text-end">Rp {{ formatCurrency(totalJasaLain) }}</td>
                        </tr>
                        <tr class="fw-bold">
                          <td>Total Belanja (Invoice)</td>
                          <td class="text-end">Rp {{ formatCurrency(totalKeseluruhan) }}</td>
                        </tr>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-info">
            Silakan pilih project terlebih dahulu untuk melihat data pembelian
          </div>
        </CCardBody>
      </CCard>
    </CCol>

      <!-- Modal Form -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="project_id">Proyek</CFormLabel>
                <CFormSelect v-model="form.proyek_id" id="project_id" required>
                  <option value="">Pilih Proyek</option>
                  <optgroup v-for="(projectGroup, customer) in groupedProjects"
                           :key="customer"
                           :label="customer">
                    <option v-for="project in projectGroup"
                            :key="project.id"
                            :value="project.id">
                      {{ project.nama_proyek }}
                    </option>
                  </optgroup>
                </CFormSelect>
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="item">Nama Item</CFormLabel>
                <CFormInput v-model="form.item" id="item" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="merek_id">Merek</CFormLabel>
                <CFormSelect
                  v-model="form.merek_id"
                  id="merek_id"
                  :required="!isServiceCategory"
                  :disabled="isServiceCategory"
                >
                  <option value="">Pilih Merek</option>
                  <option v-for="merek in mereks" :key="merek.id" :value="merek.id">
                    {{ merek.name }}
                  </option>
                </CFormSelect>
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="type">Tipe</CFormLabel>
                <CFormInput v-model="form.type" id="type" />
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
                <CFormLabel for="category_id">Kategori</CFormLabel>
                <CFormSelect v-model="form.category_id" id="category_id" required>
                  <option value="">Pilih Kategori</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.nama_kategori }}
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
                <CFormInput type="number" v-model.number="form.qty" id="qty" required @input="calculateTotal" min="1" />
              </CCol>
              <CCol md="4">
                <CFormLabel for="harga">Harga</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    :value="form.displayHarga"
                    @input="handleHargaInput"
                    id="harga"
                    required
                  />
                </div>
              </CCol>
              <CCol md="4">
                <CFormLabel for="total_harga">Total Harga</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    :value="form.displayTotalHarga"
                    id="total_harga"
                    readonly
                    class="bg-light"
                  />
                </div>
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
  import { ref, onMounted, nextTick, watch, computed } from "vue";
  import axios from "axios";
  import $ from "jquery";
  import Swal from "sweetalert2";
  import "datatables.net-dt/css/dataTables.dataTables.min.css";
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
  import "datatables.net-responsive-dt";

  const pembelianTableRef = ref(null);
  const form = ref({
    proyek_id: "",
    item: "",
    merek_id: "",
    type: "",
    spesifikasi: "",
    unit_id: "",
    category_id: "",
    qty: 1,
    harga: 0,
    total_harga: 0,
    deskripsi: "",
    displayHarga: "0",
    displayTotalHarga: "0"
  });
  const mereks = ref([]);
  const units = ref([]);
  const categories = ref([]);
  const projects = ref([]);
  const pembelians = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Pembelian");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);
  const selectedProject = ref("");
  const selectedProjectDetails = ref(null);

  // Fetch Projects
const fetchProjects = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/proyeks", {
      headers: { Authorization: `Bearer ${token}` },
    });

    if (response.data && response.data.status === 'success') {
      projects.value = response.data.data;
    } else {
      projects.value = [];
      error.value = "Data tidak valid";
    }
  } catch (err) {
    error.value = "Gagal memuat data proyek: " + (err.response?.data?.message || err.message);
  }
};

  const fetchMereks = async () => {
    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/mereks", {
        headers: { Authorization: `Bearer ${token}` },
      });
      mereks.value = response.data;
    } catch (err) {
      error.value = "Gagal memuat data merek";
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
      error.value = "Gagal memuat data unit";
    }
  };

  const fetchCategories = async () => {
    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/categories", {
        headers: { Authorization: `Bearer ${token}` },
      });
      categories.value = response.data;
    } catch (err) {
      error.value = "Gagal memuat data kategori";
    }
  };
  const fetchPembelians = async (proyekId) => {
    if (!proyekId) return;

    try {
      const token = sessionStorage.getItem("token");
      const [purchaseResponse, proyekResponse] = await Promise.all([
        axios.get(`/api/purchasematerials`, {
          headers: { Authorization: `Bearer ${token}` },
          params: {
            proyek_id: proyekId
          }
        }),
        axios.get(`/api/proyeks/${proyekId}`, {
          headers: { Authorization: `Bearer ${token}` }
        })
      ]);

      if (purchaseResponse.data && proyekResponse.data) {
        pembelians.value = purchaseResponse.data.purchases.map(purchase => ({
          ...purchase,
          displayHarga: formatCurrency(purchase.harga),
          displayTotalHarga: formatCurrency(purchase.total_harga),
          nama_proyek: proyekResponse.data.nama_proyek,
          nama_customer: proyekResponse.data.nama_customer
        }));

        selectedProjectDetails.value = proyekResponse.data;
        initDataTable();
      }
    } catch (err) {
      console.error("Error fetching purchases:", err);
      let errorMessage = "Gagal mengambil data pembelian";
      if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
      }
      Swal.fire({
        icon: "error",
        title: "Oops...",
        text: errorMessage
      });
    }
  };

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(pembelianTableRef.value)) {
      $(pembelianTableRef.value).DataTable().destroy();
    }

    $(pembelianTableRef.value).DataTable({
      data: pembelians.value,
      columns: [
        { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
        {
          title: "Proyek",
          data: null,
          render: (data) => {
            const proyek = selectedProjectDetails.value;
            return proyek ? `${proyek.nama_customer} - ${proyek.nama_proyek}` : "-";
          }
        },
        { title: "Item", data: "item" },
        {
          title: "Merek",
          data: "merek",
          render: (data) => data ? data.name : "-"
        },
        { title: "Tipe", data: "type" },
        {
          title: "Unit",
          data: "unit",
          render: (data) => data ? data.unit_name : "-"
        },
        {
          title: "Kategori",
          data: "category",
          render: (data) => data ? data.nama_kategori : "-"
        },
        {
          title: "Jumlah",
          data: "qty",
          render: (data) => data.toLocaleString()
        },
        {
          title: "Harga",
          data: "harga",
          render: (data) => `Rp ${new Intl.NumberFormat('id-ID').format(data)}`
        },
        {
          title: "Total",
          data: "total_harga",
          render: (data) => `Rp ${new Intl.NumberFormat('id-ID').format(data)}`
        },
        {
          title: "Aksi",
          data: null,
          render: (data, type, row) => `
            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
              <i class="cil-pencil"></i> Edit
            </button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
              <i class="cil-trash"></i> Hapus
            </button>
          `
        }
      ],
      scrollX: true,
      scrollCollapse: true,
      fixedColumns: {
        left: 1,
        right: 1
      },
      dom: '<"top"lf>rt<"bottom"ip><"clear">',
      pageLength: 10,
      lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
      autoWidth: false,
      columnDefs: [
        { width: "5%", targets: 0 }, // No
        { width: "15%", targets: 1 }, // Proyek
        { width: "10%", targets: 2 }, // Item
        { width: "10%", targets: 3 }, // Merek
        { width: "10%", targets: 4 }, // Tipe
        { width: "10%", targets: 5 }, // Unit
        { width: "10%", targets: 6 }, // Kategori
        { width: "5%", targets: 7 }, // Jumlah
        { width: "10%", targets: 8 }, // Harga
        { width: "10%", targets: 9 }, // Total
        { width: "5%", targets: 10 } // Aksi
      ]
    });

    $(pembelianTableRef.value).off("click", ".edit-btn").on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const pembelian = pembelians.value.find(p => p.id === id);
      if (pembelian) openModal("edit", pembelian);
    });

    $(pembelianTableRef.value).off("click", ".delete-btn").on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      handleDelete(id);
    });
  };

  const filterByProject = async () => {
    if (selectedProject.value) {
      await fetchPembelians(selectedProject.value);
    } else {
      pembelians.value = [];
      if ($.fn.DataTable.isDataTable(pembelianTableRef.value)) {
        $(pembelianTableRef.value).DataTable().destroy();
        $(pembelianTableRef.value).empty();
      }
    }
  };

  // Watch for changes in selectedProject
  watch(selectedProject, async (newValue) => {
    await filterByProject();
  });

  // Open Modal for Add/Edit
  const openModal = (mode, pembelian = null) => {
    modalMode.value = mode;
    if (mode === "edit" && pembelian) {
      form.value = {
        proyek_id: pembelian.proyek_id,
        item: pembelian.item,
        merek_id: pembelian.merek_id,
        type: pembelian.type,
        spesifikasi: pembelian.spesifikasi || "",
        unit_id: pembelian.unit_id,
        category_id: pembelian.category_id,
        qty: pembelian.qty,
        harga: pembelian.harga,
        total_harga: pembelian.total_harga,
        deskripsi: pembelian.deskripsi || "",
        displayHarga: formatCurrency(pembelian.harga),
        displayTotalHarga: formatCurrency(pembelian.total_harga)
      };
      editingId.value = pembelian.id;
      modalTitle.value = "Edit Pembelian";
      modalButtonText.value = "Update";
    } else {
      form.value = {
        proyek_id: selectedProject.value,
        item: "",
        merek_id: "",
        type: "",
        spesifikasi: "",
        unit_id: "",
        category_id: "",
        qty: 1,
        harga: 0,
        total_harga: 0,
        deskripsi: "",
        displayHarga: "0",
        displayTotalHarga: "0"
      };
      editingId.value = null;
      modalTitle.value = "Tambah Pembelian";
      modalButtonText.value = "Simpan";
    }
    showModal.value = true;
  };

  // Close the modal
  const closeModal = () => {
    showModal.value = false;
  };

  // Add this computed property before the onMounted hook
  const isServiceCategory = computed(() => {
    const selectedCategory = categories.value.find(cat => cat.id === form.value.category_id);
    return selectedCategory?.nama_kategori?.toLowerCase().includes('jasa');
  });

  // Update the handleSubmit function validation
  const handleSubmit = async () => {
    try {
      if (!form.value.proyek_id) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Proyek harus dipilih"
        });
        return;
      }

      const token = sessionStorage.getItem("token");
      const payload = {
        proyek_id: form.value.proyek_id,
        item: form.value.item,
        merek_id: form.value.merek_id,
        type: form.value.type,
        spesifikasi: form.value.spesifikasi,
        unit_id: form.value.unit_id,
        category_id: form.value.category_id,
        qty: form.value.qty,
        harga: unformatCurrency(form.value.displayHarga),
        total_harga: unformatCurrency(form.value.displayTotalHarga),
        deskripsi: form.value.deskripsi
      };

      if (modalMode.value === "edit") {
        await axios.put(`/api/purchasematerials/${editingId.value}`, payload, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
        Swal.fire({ icon: "success", title: "Success", text: "Pembelian berhasil diupdate" });
      } else {
        await axios.post("/api/purchasematerials", payload, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
        Swal.fire({ icon: "success", title: "Success", text: "Pembelian berhasil ditambahkan" });
      }

      closeModal();
      await fetchPembelians(form.value.proyek_id);
    } catch (err) {
      console.error("Error submitting form:", err);
      let errorMessage = "Terjadi kesalahan saat menyimpan data";
      if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
      }
      Swal.fire({
        icon: "error",
        title: "Error",
        text: errorMessage
      });
    }
  };

  const handleDelete = async (id) => {
    try {
      const result = await Swal.fire({
        title: "Yakin ingin menghapus?",
        text: "Data tidak dapat dikembalikan!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Ya, hapus!",
        cancelButtonText: "Batal"
      });

      if (result.isConfirmed) {
        const token = sessionStorage.getItem("token");
        await axios.delete(`/api/purchasematerials/${id}`, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });
        Swal.fire({ icon: "success", title: "Success", text: "Data berhasil dihapus" });
        await fetchPembelians(selectedProject.value);
      }
    } catch (err) {
      console.error("Error deleting purchase:", err);
      let errorMessage = "Gagal menghapus data";
      if (err.response?.data?.message) {
        errorMessage = err.response.data.message;
      }
      Swal.fire({
        icon: "error",
        title: "Error",
        text: errorMessage
      });
    }
  };

  // Add formatting functions
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value);
  };
// Change Project (Reset selected project)
const changeProject = async () => {
  selectedProject.value = null;
  // Clear data and destroy DataTable
  pembelians.value = [];
  if ($.fn.DataTable.isDataTable(pembelianTableRef.value)) {
    $(pembelianTableRef.value).DataTable().destroy();
    // Clear the table contents
    $(pembelianTableRef.value).empty();
  }
};

  const unformatCurrency = (value) => {
    return Number(value.replace(/[^\d,-]/g, ''));
  };

  // Update the calculateTotal function
  const calculateTotal = () => {
    if (form.value.qty && form.value.harga) {
      form.value.total_harga = form.value.qty * form.value.harga;
      form.value.displayTotalHarga = formatCurrency(form.value.total_harga);
    }
  };

  // Add handlers for currency input
  const handleHargaInput = (event) => {
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.harga = unformattedValue;
    form.value.displayHarga = formatCurrency(unformattedValue);
    calculateTotal();
  };

  const handleTotalHargaInput = (event) => {
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.total_harga = unformattedValue;
    form.value.displayTotalHarga = formatCurrency(unformattedValue);
  };

  // Add computed properties for totals
  const totalMaterial = computed(() => {
    return pembelians.value
      .filter(p => p.category?.nama_kategori?.toLowerCase().includes('material'))
      .reduce((sum, p) => sum + Number(p.total_harga), 0);
  });

  const totalJasa = computed(() => {
    return pembelians.value
      .filter(p => p.category?.nama_kategori?.toLowerCase().includes('jasa') && !p.category?.nama_kategori?.toLowerCase().includes('lain'))
      .reduce((sum, p) => sum + Number(p.total_harga), 0);
  });

  const totalJasaLain = computed(() => {
    return pembelians.value
      .filter(p => p.category?.nama_kategori?.toLowerCase().includes('jasa') && p.category?.nama_kategori?.toLowerCase().includes('lain'))
      .reduce((sum, p) => sum + Number(p.total_harga), 0);
  });

  const totalKeseluruhan = computed(() => {
    return pembelians.value.reduce((sum, p) => sum + Number(p.total_harga), 0);
  });

  // Add this computed property
  const groupedProjects = computed(() => {
    const grouped = {};
    projects.value.forEach(project => {
      if (!grouped[project.nama_customer]) {
        grouped[project.nama_customer] = [];
      }
      grouped[project.nama_customer].push(project);
    });
    return grouped;
  });

  // Initial Fetching
  onMounted(() => {
    fetchProjects();
    fetchMereks();
    fetchUnits();
    fetchCategories();
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
    position: relative;
  }

  table.display {
    width: 100% !important;
    min-width: 1200px;
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
  </style>
