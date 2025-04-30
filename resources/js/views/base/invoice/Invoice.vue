<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-file" /> Invoice
          <CButton color="primary" @click="openModal('tambah')" class="float-end" :disabled="!selectedProject">
            Tambah Invoice
          </CButton>
        </CCardHeader>
        <CCardBody>
          <div v-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading...</div>

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

          <!-- Data Table for Invoices -->
          <div v-if="selectedProject">
            <div style="overflow-x: auto; width: 100%;">
              <table ref="invoiceTableRef" class="display nowrap"></table>
            </div>

            <div class="total-section mt-3">
              <div class="card">
                <div class="card-body">
                  <h5>Ringkasan Invoice:</h5>
                  <CRow>
                    <CCol xs="12" md="6">
                      <div style="overflow-x: auto;">
                        <table class="table table-sm w-100">
                          <tbody>
                            <tr>
                              <td>Total Invoice</td>
                              <td class="text-end">{{ formatCurrency(totalInvoice) }}</td>
                            </tr>
                            <tr>
                              <td>Total Dibayar</td>
                              <td class="text-end">{{ formatCurrency(totalPaid) }}</td>
                            </tr>
                            <tr>
                              <td>Total Belum Dibayar</td>
                              <td class="text-end">{{ formatCurrency(totalUnpaid) }}</td>
                            </tr>
                            <tr class="fw-bold">
                              <td>Status Keseluruhan</td>
                              <td class="text-end">{{ overallStatus }}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </CCol>
                  </CRow>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="alert alert-info">
            Silakan pilih project terlebih dahulu untuk melihat data invoice
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- Modal Form -->
    <CModal
      :visible="showModal"
      @close="closeModal"
      size="lg"
      :title="modalTitle"
      backdrop="static"
    >
      <CModalHeader>
        <CModalTitle>{{ modalTitle }}</CModalTitle>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="project_id">Proyek</CFormLabel>
              <CFormSelect
                id="project_id"
                v-model="form.proyek_id"
                :disabled="modalMode === 'view'"
                required
              >
                <option value="">Pilih Proyek</option>
                <option
                  v-for="project in projects"
                  :key="project.id"
                  :value="project.id"
                >
                  {{ project.nama_customer }} - {{ project.nama_proyek }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="invoice_date">Tanggal Invoice</CFormLabel>
              <CFormInput
                type="date"
                id="invoice_date"
                v-model="form.invoice_date"
                :readonly="modalMode === 'view'"
                required
              />
            </CCol>
            <CCol md="6">
              <CFormLabel for="status">Status</CFormLabel>
              <CFormSelect
                id="status"
                v-model="form.status"
                :disabled="modalMode === 'view'"
                required
              >
                <option value="unpaid">Belum Dibayar</option>
                <option value="partially_paid">Dibayar Sebagian</option>
                <option value="paid">Lunas</option>
              </CFormSelect>
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="notes">Catatan</CFormLabel>
              <CFormTextarea
                id="notes"
                v-model="form.notes"
                rows="3"
                :readonly="modalMode === 'view'"
              />
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel>Metode Pembayaran</CFormLabel>
              <CFormCheck
                type="radio"
                label="Cash (Tanpa Termin)"
                v-model="form.is_cash"
                :value="true"
              />
              <CFormCheck
                type="radio"
                label="Termin"
                v-model="form.is_cash"
                :value="false"
              />
            </CCol>
          </CRow>

          <div v-if="!form.is_cash">
            <div v-for="(termin, idx) in form.termins" :key="idx" class="border p-2 mb-2">
              <CRow>
                <CCol md="4">
                  <CFormInput v-model="termin.nama_termin" placeholder="Nama Termin" />
                </CCol>
                <CCol md="3">
                  <CFormInput v-model.number="termin.nilai_termin" type="number" placeholder="Nilai Termin" />
                </CCol>
                <CCol md="3">
                  <CFormInput v-model.number="termin.dp_percentage" type="number" placeholder="Persentase DP" />
                </CCol>
                <CCol md="2">
                  <CButton color="danger" @click="form.termins.splice(idx,1)" v-if="form.termins.length > 1">Hapus</CButton>
                </CCol>
              </CRow>
            </div>
            <CButton color="success" @click="form.termins.push({nama_termin:'',nilai_termin:0,dp_percentage:0})">Tambah Termin</CButton>
          </div>

          <CRow class="mb-3">
            <CCol md="12">
              <h5>Items</h5>
              <div v-for="(item, index) in form.purchase_materials" :key="index" class="border p-3 mb-3">
                <CRow>
                  <CCol md="4">
                    <CFormLabel>Item</CFormLabel>
                    <CFormInput v-model="item.item" :readonly="modalMode === 'view'" required />
                  </CCol>
                  <CCol md="2">
                    <CFormLabel>Type</CFormLabel>
                    <CFormInput
                      v-model="item.type"
                      :readonly="modalMode === 'view'"
                      required
                      placeholder="Masukkan tipe, contoh: Lithium"
                    />
                  </CCol>
                  <CCol md="2">
                    <CFormLabel>Qty</CFormLabel>
                    <CFormInput
                      type="number"
                      v-model="item.qty"
                      :readonly="modalMode === 'view'"
                      required
                      min="1"
                      @input="calculateTotalHarga(item)"
                    />
                  </CCol>
                  <CCol md="4">
                    <CFormLabel>Harga</CFormLabel>
                    <div class="input-group">
                      <span class="input-group-text">Rp</span>
                      <CFormInput
                        type="text"
                        :value="item.harga"
                        @input="onHargaInput($event, item)"
                        :readonly="modalMode === 'view'"
                        min="0"
                        :class="{ 'bg-light': isServiceType(item) }"
                      />
                    </div>
                    <div v-if="isServiceType(item) && item.harga">
                      <small class="text-success">Harga diambil dari kategori jasa</small>
                    </div>
                  </CCol>
                </CRow>
                <CRow class="mt-3">
                  <CCol md="4">
                    <CFormLabel>Unit</CFormLabel>
                    <CFormSelect
                      v-model="item.unit_id"
                      :readonly="modalMode === 'view'"
                      required
                      @change="handleUnitChange(item)"
                    >
                      <option value="">Pilih Unit</option>
                      <option v-for="unit in units" :key="unit.id" :value="unit.id">
                        {{ unit.unit_name }}
                      </option>
                    </CFormSelect>
                  </CCol>
                  <CCol md="4">
                    <CFormLabel>{{ isServiceType(item) ? 'Kategori Jasa' : 'Kategori' }}</CFormLabel>
                    <CFormSelect
                      v-if="isServiceType(item)"
                      v-model="item.service_category_id"
                      :readonly="modalMode === 'view'"
                      required
                      :disabled="!item.unit_id"
                      @change="handleCategoryChange(item)"
                    >
                      <option value="">Pilih Kategori Jasa</option>
                      <option v-for="category in getServiceCategoriesByUnit(item.unit_id)" :key="category.id" :value="category.id">
                        {{ category.nama_kategori }}
                      </option>
                    </CFormSelect>
                    <CFormSelect
                      v-else
                      v-model="item.category_id"
                      :readonly="modalMode === 'view'"
                      required
                      :disabled="!item.unit_id"
                    >
                      <option value="">Pilih Kategori Material</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.nama_kategori }}
                      </option>
                    </CFormSelect>
                    <div v-if="isServiceType(item) && getServiceCategoriesByUnit(item.unit_id).length === 0" class="text-danger small mt-1">
                      Tidak ada kategori jasa untuk unit ini. Silakan tambahkan kategori jasa terlebih dahulu.
                    </div>
                  </CCol>
                  <CCol md="4">
                    <CFormLabel>Merek</CFormLabel>
                    <CFormSelect
                      v-model="item.merek_id"
                      :readonly="modalMode === 'view'"
                      :disabled="isServiceType(item)"
                      :class="{ 'bg-light': isServiceType(item) }"
                    >
                      <option value="">Pilih Merek</option>
                      <option v-for="merek in mereks" :key="merek.id" :value="merek.id">
                        {{ merek.name }}
                      </option>
                    </CFormSelect>
                    <small v-if="isServiceType(item)" class="text-muted">
                      Merek otomatis diatur untuk jasa
                    </small>
                  </CCol>
                </CRow>
                <CRow class="mt-3">
                  <CCol md="6">
                    <CFormLabel>Spesifikasi</CFormLabel>
                    <CFormTextarea v-model="item.spesifikasi" rows="2" :readonly="modalMode === 'view'" />
                  </CCol>
                  <CCol md="6">
                    <CFormLabel>Deskripsi</CFormLabel>
                    <CFormTextarea v-model="item.deskripsi" rows="2" :readonly="modalMode === 'view'" />
                  </CCol>
                </CRow>
                <CRow class="mt-3">
                  <CCol md="6">
                    <strong>Total Harga: {{ formatCurrency(item.total_harga) }}</strong>
                  </CCol>
                  <CCol md="6" class="text-end">
                    <CButton
                      v-if="modalMode !== 'view'"
                      color="danger"
                      size="sm"
                      @click="removeItem(index)"
                      :disabled="form.purchase_materials.length === 1"
                    >
                      <CIcon icon="cil-trash" /> Hapus Item
                    </CButton>
                  </CCol>
                </CRow>
              </div>
              <CButton v-if="modalMode !== 'view'" color="success" size="sm" @click="addItem">
                <CIcon icon="cil-plus" /> Tambah Item
              </CButton>
            </CCol>
          </CRow>
        </CForm>
      </CModalBody>
      <CModalFooter v-if="modalMode !== 'view'">
        <CButton color="secondary" @click="closeModal">
          Batal
        </CButton>
        <CButton color="primary" @click="handleSubmit">
          {{ editingId ? 'Update' : 'Simpan' }}
        </CButton>
      </CModalFooter>
      <CModalFooter v-else><CButton color="secondary" @click="closeModal">Tutup</CButton></CModalFooter>
    </CModal>
  </CRow>
</template>

<script>
import { ref, onMounted, watch, computed, nextTick, onUnmounted } from "vue";
import axios from "axios";
import $ from "jquery";
import Swal from "sweetalert2";
import moment from "moment";
import 'datatables.net-dt/css/dataTables.dataTables.min.css';
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css';
import 'datatables.net';
import 'datatables.net-responsive';
import { useRoute } from 'vue-router'

export default {
  name: 'Invoice',
  setup() {
    const invoiceTableRef = ref(null)
    const selectedProject = ref('')
    const projects = ref([])
    const showModal = ref(false)
    const loading = ref(false)
    const error = ref(null)
    const units = ref([])
    const categories = ref([])
    const mereks = ref([])
    const serviceCategories = ref([])
    const selectedUnitType = ref(null)
    const invoices = ref([])
    const modalMode = ref('add') // 'add', 'edit', 'view'
    const route = useRoute()

    const isServiceUnit = computed(() => {
      if (!form.value.unit_id) return false
      const selectedUnit = units.value.find(u => String(u.id) === String(form.value.unit_id))
      selectedUnitType.value = selectedUnit?.unit_name?.toLowerCase() || null
      return selectedUnit && ["jasa", "set", "transaksi"].includes(selectedUnit.unit_name.toLowerCase())
    })

    const handleUnitChange = (item) => {
      const oldHarga = item.harga
      item.category_id = ''
      item.service_category_id = ''
      const unit = units.value.find(u => String(u.id) === String(item.unit_id))
      const isServiceType = unit && ['jasa', 'transaksi', 'set'].includes(unit.unit_name.toLowerCase())
      if (isServiceType) {
        item.is_service = true
        item.harga = 0
        const defaultMerek = mereks.value.find(m => m.name === '-')
        if (defaultMerek) {
          item.merek_id = defaultMerek.id
        }
      } else {
        item.is_service = false
        item.merek_id = ''
        item.harga = oldHarga
      }
    }

    const fetchServiceCategories = async (unitId, item) => {
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/service-categories', {
          headers: { Authorization: `Bearer ${token}` }
        })
        const data = Array.isArray(response.data) ? response.data : (response.data.data ? response.data.data : [])
        // Filter di frontend
        item.serviceCategories = data.filter(cat => String(cat.unit_id) === String(unitId))
        console.log('Fetched service categories:', item.serviceCategories)
      } catch (err) {
        item.serviceCategories = []
      }
    }

    const loadMasterData = async () => {
      try {
        const token = sessionStorage.getItem('token')
        const [unitsRes, categoriesRes, mereksRes, serviceCategoriesRes] = await Promise.all([
          axios.get('/api/units', {
            headers: { Authorization: `Bearer ${token}` }
          }),
          axios.get('/api/categories', {
            headers: { Authorization: `Bearer ${token}` }
          }),
          axios.get('/api/mereks', {
            headers: { Authorization: `Bearer ${token}` }
          }),
          axios.get('/api/service-categories', {
            headers: { Authorization: `Bearer ${token}` }
          })
        ])
        units.value = unitsRes.data
        categories.value = categoriesRes.data.map(cat => ({
          ...cat,
          is_service: false
        }))
        mereks.value = mereksRes.data
        serviceCategories.value = serviceCategoriesRes.data
      } catch (error) {
        console.error('Error loading master data:', error)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Gagal memuat data master: ' + (error.response?.data?.message || error.message)
        })
      }
    }

    const form = ref({
      proyek_id: '',
      invoice_date: '',
      status: 'unpaid',
      notes: '',
      purchase_materials: [{
        item: '',
        type: '',
        qty: 1,
        harga: '',
        unit_id: '',
        total_harga: 0,
        spesifikasi: '',
        deskripsi: '',
        category_id: '',
        service_category_id: '',
        merek_id: '',
        expense_id: null,
        is_service: false,
        serviceCategories: []
      }],
      is_cash: true,
      termins: [{nama_termin:'',nilai_termin:0,dp_percentage:0}]
    })
    const modalTitle = ref('Tambah Invoice')
    const editingId = ref(null)
    let dataTable = null

    const formatCurrency = (value) => {
      if (!value && value !== 0) return 'Rp 0'
      const numericValue = Number(value)
      if (isNaN(numericValue)) return 'Rp 0'
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(numericValue)
    }

    const loadProjects = async () => {
      try {
        const token = sessionStorage.getItem('token')
        console.log('Loading projects...')
        const response = await axios.get('/api/proyeks', {
          headers: {
            Authorization: `Bearer ${token}`
          }
        })
        console.log('Projects response:', response.data)
        projects.value = response.data.data || []
      } catch (err) {
        console.error('Error loading projects:', err)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Gagal memuat data proyek: ' + (err.response?.data?.message || err.message)
        })
      }
    }

    const loadInvoices = async () => {
      if (!selectedProject.value) return

      loading.value = true
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/invoices', {
          params: { proyek_id: selectedProject.value },
          headers: {
            Authorization: `Bearer ${token}`
          }
        })

        console.log('Invoices API response:', response.data);
        invoices.value = response.data.data || [];

        if (dataTable) {
          dataTable.clear().destroy();
          dataTable = null;
        }

        if (invoiceTableRef.value) {
          dataTable = $(invoiceTableRef.value).DataTable({
            data: response.data.data || [],
            columns: [
              { title: 'No Invoice', data: 'invoice_number' },
              { title: 'Tanggal', data: 'invoice_date', render: data => moment(data).format('DD/MM/YYYY') },
              { title: 'Total Amount', data: 'total_amount', render: data => formatCurrency(data) },
              { title: 'Amount Paid', data: 'amount_paid', render: data => formatCurrency(data) },
              { 
                title: 'Jumlah Item', 
                data: 'purchase_materials',
                render: data => data ? data.length : 0,
                className: 'text-center'
              },
              {
                title: 'Status',
                data: 'status',
                render: data => {
                  const statusMap = {
                    unpaid: '<span class="badge bg-danger">Belum Dibayar</span>',
                    partially_paid: '<span class="badge bg-warning">Dibayar Sebagian</span>',
                    paid: '<span class="badge bg-success">Lunas</span>',
                    cancelled: '<span class="badge bg-secondary">Dibatalkan</span>'
                  }
                  return statusMap[data] || data
                }
              },
              {
                title: 'Actions',
                data: null,
                render: function(data) {
                  return `
                    <button class="btn btn-info btn-sm view-btn" data-id="${data.id}">
                      <i class="fas fa-eye"></i>
                    </button>
                    ${data.status === 'unpaid' ? `
                      <button class="btn btn-warning btn-sm edit-btn" data-id="${data.id}">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm delete-btn" data-id="${data.id}">
                        <i class="fas fa-trash"></i>
                      </button>
                    ` : ''}
                  `
                }
              }
            ],
            order: [[1, 'desc']],
            responsive: true
          })

          // Add event listeners for action buttons
          $(invoiceTableRef.value).on('click', '.view-btn', function() {
            const id = $(this).data('id')
            openModal('view', id)
          })

          $(invoiceTableRef.value).on('click', '.edit-btn', function() {
            const id = $(this).data('id')
            openModal('edit', id)
          })

          $(invoiceTableRef.value).on('click', '.delete-btn', function() {
            const id = $(this).data('id')
            deleteInvoice(id)
          })
        }
      } catch (err) {
        console.error('Error loading invoices:', err)
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Gagal memuat data invoice: ' + (err.response?.data?.message || err.message)
        })
      } finally {
        loading.value = false
      }
    }

    const totalInvoice = computed(() => {
      return invoices.value.reduce((sum, row) => sum + parseFloat(row.total_amount || 0), 0)
    })

    const totalPaid = computed(() => {
      return invoices.value.reduce((sum, row) => sum + parseFloat(row.amount_paid || 0), 0)
    })

    const totalUnpaid = computed(() => totalInvoice.value - totalPaid.value)

    const overallStatus = computed(() => {
      if (totalUnpaid.value === 0 && totalInvoice.value > 0) return 'Lunas'
      if (totalPaid.value === 0) return 'Belum Dibayar'
      if (totalUnpaid.value > 0) return 'Dibayar Sebagian'
      return '-'
    })

    const setHargaFromServiceCategory = (item) => {
      if (isServiceType(item) && item.category_id) {
        const selectedCategory = getServiceCategoriesByUnit(item.unit_id, item).find(cat => String(cat.id) === String(item.category_id))
        console.log('Selected service category:', selectedCategory)
        if (selectedCategory) {
          item.harga = selectedCategory.harga !== undefined ? selectedCategory.harga : (selectedCategory.price !== undefined ? selectedCategory.price : 0)
          console.log('Set item.harga:', item.harga)
          calculateTotalHarga(item)
        }
      }
    }

    const openModal = async (mode, id = null) => {
      modalMode.value = mode
      editingId.value = id
      modalTitle.value = mode === 'tambah' ? 'Tambah Invoice' : (mode === 'edit' ? 'Edit Invoice' : 'Detail Invoice')
      if ((mode === 'edit' || mode === 'view') && id) {
        await loadMasterData();
        await loadProjects();
        await loadInvoice(id);
      } else {
        await loadMasterData();
        await loadProjects();
        resetForm()
        form.value.purchase_materials.forEach(item => handleCategoryChange(item))
      }
      showModal.value = true
    }

    const closeModal = () => {
      showModal.value = false
      resetForm()
    }

    const resetForm = () => {
      form.value = {
        proyek_id: selectedProject.value,
        invoice_date: '',
        status: 'unpaid',
        notes: '',
        purchase_materials: [{
          item: '',
          type: '',
          qty: 1,
          harga: '',
          unit_id: '',
          total_harga: 0,
          spesifikasi: '',
          deskripsi: '',
          category_id: '',
          service_category_id: '',
          merek_id: '',
          expense_id: null,
          is_service: false,
          serviceCategories: []
        }],
        is_cash: true,
        termins: [{nama_termin:'',nilai_termin:0,dp_percentage:0}]
      }
      editingId.value = null
      form.value.purchase_materials.forEach(item => handleCategoryChange(item))
    }

    const calculateTotalHarga = (item) => {
      item.total_harga = Number(item.qty) * Number(item.harga)
    }

    const addItem = () => {
      const newItem = {
        item: '',
        type: '',
        qty: 1,
        harga: '',
        unit_id: '',
        total_harga: 0,
        spesifikasi: '',
        deskripsi: '',
        category_id: '',
        service_category_id: '',
        merek_id: '',
        expense_id: null,
        is_service: false,
        serviceCategories: []
      }
      form.value.purchase_materials.push(newItem)
    }

    const removeItem = (index) => {
      form.value.purchase_materials.splice(index, 1)
    }

    const validateForm = () => {
      if (!form.value.proyek_id) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Proyek harus dipilih!' });
        return false;
      }
      if (!form.value.invoice_date) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Tanggal invoice harus diisi!' });
        return false;
      }
      for (const [i, item] of form.value.purchase_materials.entries()) {
        if (!item.unit_id) {
          Swal.fire({ icon: 'error', title: 'Error', text: `Unit pada item ke-${i+1} harus dipilih!` });
          return false;
        }
        if (!item.type || item.type.trim() === '') {
          Swal.fire({ icon: 'error', title: 'Error', text: `Tipe pada item ke-${i+1} harus diisi!` });
          return false;
        }
        if (!item.qty || item.qty <= 0) {
          Swal.fire({ icon: 'error', title: 'Error', text: `Qty pada item ke-${i+1} harus diisi dan > 0!` });
          return false;
        }
        if (!item.harga || item.harga <= 0) {
          Swal.fire({ icon: 'error', title: 'Error', text: `Harga pada item ke-${i+1} harus diisi dan > 0!` });
          return false;
        }
        if (!item.type) {
          Swal.fire({ icon: 'error', title: 'Error', text: `Type pada item ke-${i+1} harus diisi!` });
          return false;
        }
        if (isServiceType(item)) {
          const categories = getServiceCategoriesByUnit(item.unit_id)
          if (categories.length > 0 && !item.service_category_id) {
            Swal.fire({ icon: 'error', title: 'Error', text: `Kategori jasa pada item ke-${i+1} harus dipilih!` });
            return false;
          }
          if (!item.harga || item.harga <= 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: `Harga jasa pada item ke-${i+1} tidak valid!` });
            return false;
          }
        } else {
          if (!item.category_id) {
            Swal.fire({ icon: 'error', title: 'Error', text: `Kategori material pada item ke-${i+1} harus dipilih!` });
            return false;
          }
        }
      }
      return true;
    }

    const handleSubmit = async () => {
      if (!validateForm()) return;
      // Validasi anggaran proyek sebelum submit
      const selectedProj = projects.value.find(p => String(p.id) === String(form.value.proyek_id));
      if (selectedProj) {
        const sisaAnggaran = Number(selectedProj.anggaran_kontrak || 0) - Number(selectedProj.total_expenses || 0);
        if (totalInvoice.value > sisaAnggaran) {
          await Swal.fire({
            icon: 'warning',
            title: 'Anggaran Melebihi Batas!',
            text: 'Jumlah invoice melebihi sisa anggaran proyek. Silakan cek kembali nilai invoice.'
          });
          return;
        }
      }
      try {
        // Penyesuaian agar data jasa selalu konsisten
        form.value.purchase_materials.forEach(item => {
          if (isServiceType(item)) {
            item.service_category_id = item.service_category_id || item.category_id;
            item.type = 'service';
            item.category_id = null;
          } else {
            item.service_category_id = null;
          }
          item.harga = item.harga ? Number(String(item.harga).replace(/\./g, '')) : 0
          calculateTotalHarga(item)
        })
        const token = sessionStorage.getItem('token')
        const payload = {
          ...form.value,
          is_cash: form.value.is_cash,
          termins: form.value.is_cash ? [] : form.value.termins,
          purchase_materials: form.value.purchase_materials.map(item => ({
            ...item,
            harga: item.harga ? Number(String(item.harga).replace(/\./g, '')) : 0
          })),
          total_amount: totalInvoice.value,
          amount_paid: totalPaid.value,
          total_unpaid: totalUnpaid.value,
          overall_status: overallStatus.value
        }
        const response = await axios.post('/api/invoices', payload, {
          headers: { Authorization: `Bearer ${token}` }
        })
        if (response.data.status === 'error') {
          throw new Error(response.data.message || 'Gagal membuat invoice')
        }
        Swal.fire({
          icon: 'success',
          title: 'Sukses',
          text: 'Invoice berhasil ditambahkan'
        })
        closeModal()
        loadInvoices()
      } catch (error) {
        console.error('Error submitting form:', error)
        const errorMessage = error.response?.data?.message || error.message || 'Gagal membuat invoice'
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage
        })
      }
    }

    const loadInvoice = async (id) => {
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get(`/api/invoices/${id}`, {
          headers: {
            Authorization: `Bearer ${token}`
          }
        })
        console.log('API response:', response.data);
        const invoice = response.data.data;
        console.log('Invoice yang akan di-assign:', invoice);
        form.value = {
          ...form.value,
          proyek_id: invoice.proyek?.id || '',
          invoice_date: invoice.invoice_date ? invoice.invoice_date.substring(0, 10) : '',
          status: invoice.status || 'unpaid',
          notes: invoice.notes || '',
          purchase_materials: Array.isArray(invoice.purchase_materials) && invoice.purchase_materials.length > 0
            ? invoice.purchase_materials
            : [{
                item: '',
                type: '',
                qty: 1,
                harga: '',
                unit_id: '',
                total_harga: 0,
                spesifikasi: '',
                deskripsi: '',
                category_id: '',
                service_category_id: '',
                merek_id: '',
                expense_id: null,
                is_service: false,
                serviceCategories: []
              }]
        }
        console.log('Invoice loaded:', form.value);
        console.log('projects.value:', projects.value);
        console.log('form.value.proyek_id:', form.value.proyek_id);
      } catch (err) {
        let errorMsg = 'Gagal memuat data invoice';
        if (err.response && err.response.data && err.response.data.message) {
          errorMsg += ': ' + err.response.data.message;
        }
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMsg
        });
        console.error(err);
      }
    }

    const deleteInvoice = async (id) => {
      const result = await Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Invoice akan dihapus secara permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
      })

      if (result.isConfirmed) {
        try {
          const token = sessionStorage.getItem('token')
          await axios.delete(`/api/invoices/${id}`, {
            headers: {
              Authorization: `Bearer ${token}`
            }
          })
          Swal.fire({
            icon: 'success',
            title: 'Sukses',
            text: 'Invoice berhasil dihapus'
          })
          loadInvoices()
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Gagal menghapus invoice'
          })
          console.error(err)
        }
      }
    }

    const filterByProject = () => {
      loadInvoices()
    }

    const changeProject = () => {
      selectedProject.value = ''
      if (invoiceTableRef.value) {
        const dt = $(invoiceTableRef.value).DataTable()
        dt.clear().draw()
      }
    }

    // Tambahkan fungsi pengecekan tipe unit per item
    const isServiceType = (item) => {
      const unit = units.value.find(u => String(u.id) === String(item.unit_id))
      return unit && ['jasa', 'transaksi', 'set'].includes(unit.unit_name.toLowerCase())
    }

    // Fungsi untuk filter kategori jasa sesuai unit
    const getServiceCategoriesByUnit = (unitId) => {
      if (!unitId) return []
      return serviceCategories.value.filter(cat => String(cat.unit_id) === String(unitId))
    }

    const handleCategoryChange = (item) => {
      if (isServiceType(item)) {
        const categories = getServiceCategoriesByUnit(item.unit_id)
        const selectedCategory = categories.find(cat => String(cat.id) === String(item.service_category_id))
        if (selectedCategory) {
          item.noServiceCategory = false
          // Set harga otomatis dari kategori jasa jika ada
          if (selectedCategory.harga !== undefined && selectedCategory.harga !== null) {
            item.harga = Number(selectedCategory.harga)
          } else if (selectedCategory.price !== undefined && selectedCategory.price !== null) {
            item.harga = Number(selectedCategory.price)
          }
          calculateTotalHarga(item)
        } else {
          item.harga = 0
          item.noServiceCategory = categories.length === 0
        }
      }
    }

    const onHargaInput = (event, item) => {
      if (!isServiceType(item)) {
        // Ambil hanya angka
        let value = event.target.value.replace(/[^\d]/g, '')
        // Hilangkan 0 di depan
        value = value.replace(/^0+/, '')
        // Format ke rupiah dengan titik
        if (value) {
          item.harga = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".")
        } else {
          item.harga = ''
        }
        // Untuk perhitungan total_harga, konversi ke number
        const numericValue = item.harga ? Number(item.harga.replace(/\./g, '')) : 0
        item.total_harga = Number(item.qty) * numericValue
      }
    }

    // Tambahkan watcher otomatis untuk setiap item di purchase_materials
    watch(
      () => form.value.purchase_materials.map(item => [item.category_id, item.unit_id]),
      (newVals, oldVals) => {
        form.value.purchase_materials.forEach(item => {
          if (isServiceType(item) && item.category_id && item.unit_id) {
            const selectedCategory = getServiceCategoriesByUnit(item.unit_id, item).find(cat => String(cat.id) === String(item.category_id))
            if (selectedCategory) {
              let hargaVal = 0;
              if (selectedCategory.harga !== undefined && selectedCategory.harga !== null && selectedCategory.harga !== '') {
                hargaVal = Number(selectedCategory.harga);
              } else if (selectedCategory.price !== undefined && selectedCategory.price !== null && selectedCategory.price !== '') {
                hargaVal = Number(selectedCategory.price);
              }
              item.harga = isNaN(hargaVal) ? 0 : hargaVal;
              calculateTotalHarga(item)
            }
          }
        })
      },
      { deep: true }
    )

    // Tambahkan watcher otomatis untuk setiap perubahan service_category_id pada setiap item
    watch(
      () => form.value.purchase_materials.map(item => item.service_category_id),
      (newVals, oldVals) => {
        form.value.purchase_materials.forEach((item, idx) => {
          if (newVals[idx] !== oldVals[idx]) {
            handleCategoryChange(item)
          }
        })
      },
      { deep: true }
    )

    onMounted(() => {
      loadMasterData()
      loadProjects()
      loadInvoices()
      const handler = () => loadInvoices()
      window.addEventListener('termin-updated', handler)
      // Bersihkan event listener saat komponen di-unmount
      onUnmounted(() => {
        window.removeEventListener('termin-updated', handler)
      })
    })

    // Watch for changes in selectedProject
    watch(selectedProject, (newValue) => {
      if (newValue) {
        loadInvoices()
      }
    })

    // Tambahkan watcher pada perubahan route untuk auto-refresh invoice
    watch(
      () => route.fullPath,
      (newPath, oldPath) => {
        if (newPath.includes('/invoice')) {
          loadInvoices()
        }
      }
    )

    return {
      invoiceTableRef,
      selectedProject,
      projects,
      units,
      categories,
      mereks,
      serviceCategories,
      showModal,
      loading,
      error,
      form,
      modalTitle,
      totalInvoice,
      totalPaid,
      totalUnpaid,
      overallStatus,
      openModal,
      closeModal,
      addItem,
      removeItem,
      handleSubmit,
      filterByProject,
      changeProject,
      deleteInvoice,
      formatCurrency,
      calculateTotalHarga,
      isServiceUnit,
      handleUnitChange,
      isServiceType,
      getServiceCategoriesByUnit,
      handleCategoryChange,
      onHargaInput,
      validateForm
    }
  }
}
</script>

<style scoped>
 .w-100 {
    width: 100%;
    overflow-x: auto;
  }

.badge {
  padding: 5px 10px;
  border-radius: 3px;
}
</style>
