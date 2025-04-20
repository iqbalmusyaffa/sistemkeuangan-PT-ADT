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
            
            <!-- Project Filter -->
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="project_filter">Filter by Project</CFormLabel>
                <CFormSelect 
                  v-model="selectedProject" 
                  id="project_filter" 
                  @change="filterByProject"
                >
                  <option value="">Semua Project</option>
                  <option v-for="project in projects" :key="project.id" :value="project.id">
                    {{ project.nama_customer }} - {{ project.nama_project }}
                  </option>
                </CFormSelect>
              </CCol>
            </CRow>
            
            <div class="w-100">
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
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Form -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel for="project_id">Project</CFormLabel>
                <CFormSelect v-model="form.project_id" id="project_id" required>
                  <option value="">Pilih Project</option>
                  <option v-for="project in projects" :key="project.id" :value="project.id">
                    {{ project.nama_customer }} - {{ project.nama_project }}
                  </option>
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
    item: "",
    merek_id: "",
    type: "",
    spesifikasi: "",
    unit_id: "",
    category_id: "",
    qty: 0,
    harga: 0,
    total_harga: 0,
    deskripsi: "",
    displayHarga: "0",
    displayTotalHarga: "0",
    project_id: ""
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

  const fetchProjects = async () => {
    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/projects", {
        headers: { Authorization: `Bearer ${token}` },
      });
      projects.value = response.data;
    } catch (err) {
      console.error("Failed to load projects:", err);
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
      console.error("Failed to load mereks:", err);
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
      console.error("Failed to load units:", err);
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
      console.error("Failed to load categories:", err);
    }
  };

  const fetchPembelians = async () => {
    loading.value = true;
    error.value = "";

    try {
      const token = sessionStorage.getItem("token");
      const url = selectedProject.value 
        ? `/api/purchasematerials?project_id=${selectedProject.value}`
        : "/api/purchasematerials";
        
      const response = await axios.get(url, {
        headers: { Authorization: `Bearer ${token}` },
      });

      pembelians.value = response.data;

      nextTick(() => {
        initDataTable();
      });
    } catch (err) {
      error.value = "Failed to load purchase data.";
      Swal.fire({ icon: "error", title: "Oops...", text: error.value });
    } finally {
      loading.value = false;
    }
  };

  const filterByProject = () => {
    fetchPembelians();
  };

  // DataTable initialization
  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(pembelianTableRef.value)) {
      $(pembelianTableRef.value).DataTable().destroy();
    }

    $(pembelianTableRef.value).DataTable({
      data: pembelians.value,
      columns: [
        { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
        { 
          title: "Project", 
          data: "project",
          render: (data) => data ? `${data.nama_customer} - ${data.nama_project}` : "-"
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
          render: (data) => `Rp${new Intl.NumberFormat('id-ID').format(data)}`
        },
        {
          title: "Total",
          data: "total_harga",
          render: (data) => `Rp${new Intl.NumberFormat('id-ID').format(data)}`
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

  // Open Modal for Add/Edit
  const openModal = (mode, pembelian = null) => {
    modalMode.value = mode;
    if (mode === "edit" && pembelian) {
      form.value = {
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
        displayTotalHarga: formatCurrency(pembelian.total_harga),
        project_id: pembelian.project_id
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
        category_id: "",
        qty: 0,
        harga: 0,
        total_harga: 0,
        deskripsi: "",
        displayHarga: "0",
        displayTotalHarga: "0",
        project_id: selectedProject.value || ""
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
    loading.value = true;
    error.value = "";

    if (!form.value.project_id) {
      error.value = "Project wajib dipilih!";
      loading.value = false;
      return;
    }

    if (!form.value.item.trim()) {
      error.value = "Nama item wajib diisi!";
      loading.value = false;
      return;
    }

    if (!form.value.unit_id) {
      error.value = "Unit wajib dipilih!";
      loading.value = false;
      return;
    }

    if (!form.value.category_id) {
      error.value = "Kategori wajib dipilih!";
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

    calculateTotal();  // Ensure total_harga is calculated

    try {
      const token = sessionStorage.getItem("token");
      const payload = { ...form.value };
      
      // If it's a service, set merek_id to null
      if (isServiceCategory.value) {
        payload.merek_id = null;
      }

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

  // Delete Pembelian (Purchase material)
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

  // Add formatting functions
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value);
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
  }
  table.display {
    width: 100% !important;
  }
  </style>
