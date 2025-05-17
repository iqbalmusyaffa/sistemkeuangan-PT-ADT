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
    <CFormLabel for="project_filter">Pilih Proyek</CFormLabel>
    <CFormSelect
      v-model="selectedProject"
      id="project_filter"
      @change="filterByProject"
    >
      <option value="">-- Pilih Proyek --</option>
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
                Ganti Proyek
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
                  :disabled="form.merek_disabled"
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
                <CFormSelect
                  v-model="form.category_id"
                  id="category_id"
                  required
                  :disabled="!form.unit_id"
                >
                  <option value="">Pilih Kategori</option>
                  <template v-if="isServiceUnit">
                    <option
                      v-for="category in serviceCategories"
                      :key="category.id"
                      :value="category.id"
                    >
                      {{ category.nama_kategori }}
                    </option>
                  </template>
                  <template v-else>
                    <option
                      v-for="category in normalCategories"
                      :key="category.id"
                      :value="category.id"
                    >
                      {{ category.nama_kategori }}
                    </option>
                  </template>
                </CFormSelect>
                <div v-if="form.unit_id && isServiceUnit && serviceCategories.length === 0" class="text-danger mt-1">
                  Tidak ada kategori jasa untuk unit ini
                </div>
                <div v-if="form.unit_id && !isServiceUnit && normalCategories.length === 0" class="text-danger mt-1">
                  Tidak ada kategori material
                </div>
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
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    :value="form.displayHarga"
                    @input="handleHargaInput"
                    id="harga"
                    required
                    :readonly="isServiceUnit"
                    :class="{ 'bg-light': isServiceUnit }"
                  />
                </div>
                <small v-if="isServiceUnit" class="text-muted">
                  Harga diambil dari kategori jasa
                </small>
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
import { ref, onMounted, watch, computed } from "vue";
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
  service_category_id: "",
  is_service: false,
  qty: 1,
  harga: 0,
  total_harga: 0,
  deskripsi: "",
  displayHarga: "0",
  displayTotalHarga: "0",
  invoice_id: null,
  merek_disabled: false
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
const serviceCategories = ref([]);
const normalCategories = ref([]);
const selectedUnitType = ref(null);
const SERVICE_UNIT_NAMES = ['jasa', 'set', 'transaksi'];
const isServiceUnit = computed(() => {
  if (!form.value.unit_id) return false;
  const selectedUnit = units.value.find(u => String(u.id) === String(form.value.unit_id));
  selectedUnitType.value = selectedUnit?.unit_name?.toLowerCase() || null;
  return selectedUnit && SERVICE_UNIT_NAMES.includes(selectedUnit.unit_name.toLowerCase());
});

// Add invoices ref
const invoices = ref([]);

// Add function to fetch invoices
const fetchInvoices = async (proyekId) => {
  if (!proyekId) return;
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/invoices", {
      headers: { Authorization: `Bearer ${token}` },
      params: { proyek_id: proyekId }
    });
    invoices.value = response.data.data || [];
  } catch (err) {
    invoices.value = [];
  }
};

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
    const response = await axios.get("/api/kategori", {
      headers: { Authorization: `Bearer ${token}` },
    });
    // Pastikan categories.value selalu array
    categories.value = Array.isArray(response.data)
      ? response.data
      : (response.data.data ? response.data.data : []);
  } catch (err) {
    error.value = "Gagal memuat data kategori: " + (err.response?.data?.message || err.message);
    categories.value = [];
  }
};

const fetchServiceCategories = async (unitId) => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/service-categories", {
      headers: { Authorization: `Bearer ${token}` },
      params: { unit_id: unitId }
    });

    const data = Array.isArray(response.data) ? response.data :
                (response.data.data ? response.data.data : []);

    serviceCategories.value = data.data;
    console.log("Service categories loaded:", serviceCategories.value);

    // Set service mode
    form.value.is_service = true;
    form.value.merek_id = null;
    form.value.merek_disabled = true;

    // Disable merek field
    if (document.getElementById('merek_id')) {
      document.getElementById('merek_id').disabled = true;
    }
  } catch (err) {
    console.error("Error fetching service categories:", err);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Gagal memuat kategori jasa"
    });
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
      pembelians.value = Array.isArray(purchaseResponse.data.purchases)
        ? purchaseResponse.data.purchases.map(purchase => ({
            ...purchase,
            nama_customer: purchase.proyek?.nama_customer || '-',
            nama_proyek: purchase.proyek?.nama_proyek || '-',
            item: purchase.item || '-',
            merek: purchase.merek?.name || '-',
            type: purchase.type || '-',
            unit: purchase.unit?.unit_name || '-',
            category_name: purchase.is_service
              ? (purchase.service_category?.nama_kategori || '-')
              : (purchase.category?.nama_kategori || '-'),
            qty: purchase.qty ?? '-',
            harga: purchase.harga ?? '-',
            total_harga: purchase.total_harga ?? '-',
            status: purchase.is_service ? 'Jasa' : 'Material'
          }))
        : [];

      selectedProjectDetails.value = proyekResponse.data;
      initDataTable();
    }
  } catch (err) {
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

  if (!pembelians.value || pembelians.value.length === 0) {
    return;
  }

  $(pembelianTableRef.value).DataTable({
    data: pembelians.value,
    columns: [
      { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1, className: "text-center" },
      { title: "Customer", data: "nama_customer" },
      { title: "Proyek", data: "nama_proyek" },
      { title: "Item", data: "item" },
      { title: "Merek", data: "merek" },
      { title: "Tipe", data: "type" },
      { title: "Unit", data: "unit" },
      { title: "Kategori", data: "category_name" },
      { title: "Jumlah", data: "qty" },
      { title: "Harga", data: "harga", render: data => data === '-' ? '-' : `Rp ${Number(data).toLocaleString('id-ID')}` },
      { title: "Total", data: "total_harga", render: data => data === '-' ? '-' : `Rp ${Number(data).toLocaleString('id-ID')}` },
      { title: "Status", data: "status", render: data => data === 'Jasa' ? `<span class='badge bg-info'>Jasa</span>` : `<span class='badge bg-primary'>Material</span>` },
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
        `,
        className: "text-center"
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
    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Semua"]],
    language: {
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data per halaman",
      zeroRecords: "Tidak ada data yang ditemukan",
      info: "Menampilkan halaman _PAGE_ dari _PAGES_",
      infoEmpty: "Tidak ada data tersedia",
      infoFiltered: "(difilter dari _MAX_ total data)",
      paginate: {
        first: "Pertama",
        last: "Terakhir",
        next: "Selanjutnya",
        previous: "Sebelumnya"
      }
    },
    rowCallback: function(row, data) {
      // Optional: highlight row based on type if needed
    }
  });

  // Add event listeners for buttons
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
    await Promise.all([
      fetchPembelians(selectedProject.value),
      fetchInvoices(selectedProject.value)
    ]);
  } else {
    pembelians.value = [];
    invoices.value = [];
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

// Update watch handler for unit changes
watch(
  () => form.value.unit_id,
  async (newValue) => {
    if (!newValue) {
      form.value.category_id = "";
      serviceCategories.value = [];
      normalCategories.value = [];
      return;
    }

    const token = sessionStorage.getItem("token");
    const selectedUnit = units.value.find(u => String(u.id) === String(newValue));
    console.log("Selected unit in watcher:", selectedUnit);

    // Reset form values
    form.value.category_id = "";
    form.value.service_category_id = "";
    form.value.is_service = false;
    form.value.harga = 0;
    form.value.displayHarga = "0";
    serviceCategories.value = [];
    normalCategories.value = [];

    if (selectedUnit && SERVICE_UNIT_NAMES.includes(selectedUnit.unit_name.toLowerCase())) {
      try {
        const response = await axios.get("/api/service-categories", {
          headers: { Authorization: `Bearer ${token}` },
          params: { unit_id: newValue }
        });

        const data = Array.isArray(response.data) ? response.data :
                    (response.data.data ? response.data.data : []);

        serviceCategories.value = data;
        console.log("Service categories loaded:", serviceCategories.value);

        // Set service mode
        form.value.is_service = true;
        form.value.merek_id = null;
        form.value.merek_disabled = true;

        // Disable merek field
        if (document.getElementById('merek_id')) {
          document.getElementById('merek_id').disabled = true;
        }
      } catch (err) {
        console.error("Error fetching service categories:", err);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Gagal memuat kategori jasa"
        });
      }
    } else {
      try {
        const response = await axios.get("/api/categories", {
          headers: { Authorization: `Bearer ${token}` },
        });

        normalCategories.value = Array.isArray(response.data) ? response.data :
                               (response.data.data ? response.data.data : []);
        console.log("Normal categories loaded:", normalCategories.value);

        // Reset service mode
        form.value.is_service = false;
        form.value.service_category_id = null;
        form.value.merek_disabled = false;

        // Enable merek field
        if (document.getElementById('merek_id')) {
          document.getElementById('merek_id').disabled = false;
        }
      } catch (err) {
        console.error("Error fetching categories:", err);
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Gagal memuat kategori"
        });
      }
    }
  },
  { immediate: true }
);

// Update watch handler untuk category_id
watch(
  () => form.value.category_id,
  (newValue) => {
    if (isServiceUnit.value && newValue) {
      const selectedCategory = serviceCategories.value.find(cat => cat.id === parseInt(newValue));
      if (selectedCategory) {
        form.value.harga = parseFloat(selectedCategory.harga);
        form.value.displayHarga = formatCurrency(selectedCategory.harga);
        form.value.total_harga = form.value.qty * form.value.harga;
        form.value.displayTotalHarga = formatCurrency(form.value.total_harga);
      }
    }
  }
);

// Update watch handler untuk qty
watch(
  () => form.value.qty,
  (newValue) => {
    if (newValue && form.value.harga) {
      if (isServiceUnit.value) {
        // Untuk service, gunakan harga dari service category
        const selectedCategory = serviceCategories.value.find(cat => cat.id === parseInt(form.value.category_id));
        if (selectedCategory) {
          form.value.harga = parseFloat(selectedCategory.harga);
          form.value.total_harga = newValue * form.value.harga;
          form.value.displayTotalHarga = formatCurrency(form.value.total_harga);
        }
      } else {
        // Untuk material, hitung seperti biasa
        form.value.total_harga = newValue * form.value.harga;
        form.value.displayTotalHarga = formatCurrency(form.value.total_harga);
      }
    }
  }
);

// Open Modal for Add/Edit
const openModal = (mode, pembelian = null) => {
  modalMode.value = mode;
  if (mode === "edit" && pembelian) {
    console.log('Opening modal for editing:', pembelian);
    const isService = pembelian.is_service;
    form.value = {
      proyek_id: pembelian.proyek_id,
      item: pembelian.item,
      merek_id: isService ? null : pembelian.merek_id,
      type: pembelian.type,
      spesifikasi: pembelian.spesifikasi || "",
      unit_id: pembelian.unit_id,
      category_id: isService ? pembelian.service_category_id : pembelian.category_id,
      service_category_id: pembelian.service_category_id,
      is_service: isService,
      qty: pembelian.qty,
      harga: pembelian.harga,
      total_harga: pembelian.total_harga,
      deskripsi: pembelian.deskripsi || "",
      displayHarga: formatCurrency(pembelian.harga),
      displayTotalHarga: formatCurrency(pembelian.total_harga),
      invoice_id: pembelian.invoice_id,
      merek_disabled: isService
    };

    if (isService) {
      fetchServiceCategories(pembelian.unit_id);
    }

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
      service_category_id: "",
      is_service: false,
      qty: 1,
      harga: 0,
      total_harga: 0,
      deskripsi: "",
      displayHarga: "0",
      displayTotalHarga: "0",
      invoice_id: null,
      merek_disabled: false
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

// Update the handleSubmit function
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
    if (!form.value.unit_id) {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: "Unit harus dipilih"
      });
      return;
    }
    // Pastikan kategori sesuai jenis unit
    if (isServiceUnit.value) {
      if (!form.value.category_id) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Kategori jasa harus dipilih"
        });
        return;
      }
    } else {
      if (!form.value.category_id) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Kategori material harus dipilih"
        });
        return;
      }
    }
    // Pastikan invoice_id selalu ada
    if (!form.value.invoice_id) {
      try {
        const token = sessionStorage.getItem('token');
        const invoiceResponse = await axios.post("/api/invoices", {
          proyek_id: form.value.proyek_id,
          invoice_date: new Date().toISOString().split('T')[0],
          purchase_materials: [{
            item: form.value.item,
            type: form.value.type || '-',
            spesifikasi: form.value.spesifikasi || '-',
            unit_id: form.value.unit_id,
            qty: Number(form.value.qty),
            harga: Number(form.value.harga),
            deskripsi: form.value.deskripsi || '-',
            is_service: isServiceUnit.value,
            category_id: isServiceUnit.value ? null : form.value.category_id,
            service_category_id: isServiceUnit.value ? form.value.category_id : null,
            merek_id: isServiceUnit.value ? null : form.value.merek_id
          }]
        }, {
          headers: { Authorization: `Bearer ${token}` }
        });
        form.value.invoice_id = invoiceResponse.data.id;
      } catch (err) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "Gagal membuat invoice: " + (err.response?.data?.message || err.message)
        });
        return;
      }
    }
    const token = sessionStorage.getItem('token');
    const selectedUnit = units.value.find(u => String(u.id) === String(form.value.unit_id));
    const isService = selectedUnit && SERVICE_UNIT_NAMES.includes(selectedUnit.unit_name.toLowerCase());
    // Siapkan payload sesuai controller
    const payload = {
      proyek_id: form.value.proyek_id,
      invoice_id: form.value.invoice_id,
      item: form.value.item,
      type: form.value.type || '-',
      spesifikasi: form.value.spesifikasi || '-',
      unit_id: form.value.unit_id,
      qty: Number(form.value.qty),
      harga: Number(form.value.harga),
      total_harga: Number(form.value.total_harga),
      deskripsi: form.value.deskripsi || '-',
      is_service: isService,
      category_id: isService ? null : form.value.category_id,
      service_category_id: isService ? form.value.category_id : null,
      merek_id: isService ? null : form.value.merek_id
    };
    let response;
    if (modalMode.value === "edit") {
      response = await axios.put(`/api/purchasematerials/${editingId.value}`, payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      Swal.fire({ icon: "success", title: "Success", text: "Pembelian berhasil diupdate" });
    } else {
      response = await axios.post("/api/purchasematerials", payload, {
        headers: { Authorization: `Bearer ${token}` }
      });
      Swal.fire({ icon: "success", title: "Success", text: "Pembelian berhasil ditambahkan" });
    }
    closeModal();
    await fetchPembelians(form.value.proyek_id);
  } catch (err) {
    const errorMessage = err.response?.data?.error || err.response?.data?.message || "Terjadi kesalahan saat menyimpan data";
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

const unformatCurrency = (value) => {
  return Number(value.replace(/[^\d,-]/g, ''));
};

// Update the calculateTotal function
const calculateTotal = () => {
  if (form.value.qty && form.value.harga) {
    if (isServiceUnit.value) {
      // Untuk service, gunakan harga dari service category
      const selectedCategory = serviceCategories.value.find(cat => cat.id === parseInt(form.value.category_id));
      if (selectedCategory) {
        form.value.harga = parseFloat(selectedCategory.harga);
        form.value.total_harga = form.value.qty * form.value.harga;
        form.value.displayTotalHarga = formatCurrency(form.value.total_harga);
      }
    } else {
      // Untuk material, hitung seperti biasa
      form.value.total_harga = form.value.qty * form.value.harga;
      form.value.displayTotalHarga = formatCurrency(form.value.total_harga);
    }
  }
};

// Update handleHargaInput function
const handleHargaInput = (event) => {
  if (isServiceUnit.value) {
    // Untuk jasa, harga otomatis dari kategori
    const selectedCategory = serviceCategories.value.find(cat => cat.id === parseInt(form.value.category_id));
    if (selectedCategory) {
      form.value.harga = parseFloat(selectedCategory.harga);
      form.value.displayHarga = formatCurrency(selectedCategory.harga);
    }
  } else {
    // Untuk material, bisa diinput manual
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.harga = unformattedValue;
    form.value.displayHarga = formatCurrency(unformattedValue);
  }
  calculateTotal();
};

const handleTotalHargaInput = (event) => {
  const unformattedValue = unformatCurrency(event.target.value);
  form.value.total_harga = unformattedValue;
  form.value.displayTotalHarga = formatCurrency(unformattedValue);
};

// Update computed properties for totals
const totalMaterial = computed(() => {
  return pembelians.value
    .filter(p => !p.is_service)
    .reduce((sum, p) => sum + Number(p.total_harga), 0);
});

const totalJasa = computed(() => {
  return pembelians.value
    .filter(p => {
      if (!p.is_service) return false;
      const unit = units.value.find(u => u.id === p.unit_id);
      return unit && unit.unit_name.toLowerCase() === 'jasa';
    })
    .reduce((sum, p) => sum + Number(p.total_harga), 0);
});

const totalJasaLain = computed(() => {
  return pembelians.value
    .filter(p => {
      if (!p.is_service) return false;
      const unit = units.value.find(u => u.id === p.unit_id);
      return unit && ['set', 'transaksi'].includes(unit.unit_name.toLowerCase());
    })
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

const changeProject = () => {
  selectedProject.value = "";
  pembelians.value = [];
  if ($.fn.DataTable.isDataTable(pembelianTableRef.value)) {
    $(pembelianTableRef.value).DataTable().destroy();
    $(pembelianTableRef.value).empty();
  }
};

// Pastikan allCategories dan allServiceCategories sudah di-fetch di onMounted
const allCategories = ref([]);
const allServiceCategories = ref([]);

const fetchAllCategories = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const [catRes, svcRes] = await Promise.all([
      axios.get("/api/categories", { headers: { Authorization: `Bearer ${token}` } }),
      axios.get("/api/service-categories", { headers: { Authorization: `Bearer ${token}` } })
    ]);
    allCategories.value = Array.isArray(catRes.data) ? catRes.data : (catRes.data.data ? catRes.data.data : []);
    allServiceCategories.value = Array.isArray(svcRes.data) ? svcRes.data : (svcRes.data.data ? svcRes.data.data : []);
  } catch (err) {
    error.value = "Gagal memuat data kategori: " + (err.response?.data?.error || err.message);
  }
};

// Helper: get unit type by id
const getUnitType = (unit_id) => {
  const unit = units.value.find(u => String(u.id) === String(unit_id));
  return unit ? unit.unit_name.toLowerCase() : '';
};

// Update the updateItemCategories function
function updateItemCategories(item) {
  if (!item.unit_id) {
    item.categories = [];
    item.serviceCategories = [];
    return;
  }
  const unitType = getUnitType(item.unit_id);
  if (SERVICE_UNIT_NAMES.includes(unitType)) {
    item.is_service = true;
    item.serviceCategories = allServiceCategories.value.filter(cat => String(cat.unit_id) === String(item.unit_id));
    item.categories = [];
    item.category_id = '';
  } else {
    item.is_service = false;
    // Ambil semua kategori dengan jenis 'pengeluaran' (material), tanpa filter unit_id
    item.categories = allCategories.value.filter(cat => cat.jenis === 'pengeluaran');
    item.serviceCategories = [];
    item.service_category_id = '';
  }
}

// Update the onUnitChange function
function onUnitChange(item) {
  updateItemCategories(item);
}

// Initial Fetching
onMounted(() => {
  fetchProjects();
  fetchMereks();
  fetchUnits();
  fetchAllCategories();
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

  /* Row styles */
  .service-row {
    background-color: #e8f4ff !important;
  }

  .service-other-row {
    background-color: #fff3e0 !important;
  }

  .material-row {
    background-color: #ffffff !important;
  }

  /* DataTables controls */
  .dataTables_length select {
    padding: 0.375rem 1.75rem 0.375rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    background-color: #fff;
  }

  .dataTables_filter {
    text-align: right;
    margin-bottom: 1rem;
  }

  .dataTables_filter input {
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    margin-left: 0.5rem;
    width: 250px;
  }

  .dataTables_filter label {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    margin: 0;
  }

  .dataTables_filter label::before {
    content: "Cari:";
    margin-right: 0.5rem;
    font-weight: 500;
  }

  .dataTables_info {
    padding-top: 1rem;
    font-size: 0.875rem;
    color: #6c757d;
  }

  .dataTables_paginate {
    padding-top: 1rem;
  }

  .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 0.25rem;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    background-color: #fff;
    color: #007bff;
    cursor: pointer;
  }

  .dataTables_paginate .paginate_button:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #0056b3;
  }

  .dataTables_paginate .paginate_button.current {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
  }

  /* Badge styles */
  .badge {
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 600;
    border-radius: 0.25rem;
  }

  /* Responsive styles */
  @media (max-width: 768px) {
    .dataTables_wrapper {
      padding: 0.5rem;
    }

    .dataTables_filter {
      text-align: left;
      margin-top: 1rem;
    }

    .dataTables_filter input {
      width: 100%;
      margin-left: 0;
    }

    .dataTables_filter label {
      flex-direction: column;
      align-items: flex-start;
    }

    .dataTables_filter label::before {
      margin-bottom: 0.5rem;
    }

    .btn {
      margin: 0.125rem 0;
    }

    table.display {
      min-width: 100%;
    }
  }
  </style>
