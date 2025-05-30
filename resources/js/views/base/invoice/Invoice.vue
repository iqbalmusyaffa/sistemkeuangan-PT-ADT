<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <FontAwesomeIcon :icon="['fas', 'file']" /> Invoice
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

          <!-- Filter Checkboxes -->
          <CRow class="mb-3">
            <CCol md="12">
              <div class="d-flex gap-3">
                <CFormCheck
                  type="checkbox"
                  label="Gunakan PPN (11%)"
                  v-model="filterPpn"
                  id="filter_ppn"
                  :disabled="!canUsePpn"
                />
                <CFormCheck
                  type="checkbox"
                  label="Gunakan PPH Non Final"
                  v-model="filterPphNonFinal"
                  id="filter_pph_non_final"
                  :disabled="!canUsePphNonFinal"
                />
                <CFormCheck
                  type="checkbox"
                  label="Gunakan PPH Final"
                  v-model="filterPphFinal"
                  id="filter_pph_final"
                  :disabled="!canUsePphFinal"
                />
              </div>
            </CCol>
          </CRow>

          <!-- Budget Filter Checkboxes -->
          <CRow class="mb-3">
            <CCol md="12">
              <div class="d-flex gap-3">
                <CFormCheck
                  type="checkbox"
                  label="Proyek di atas 100 juta"
                  v-model="filterAbove100M"
                  @change="resetOtherFilters('above')"
                  :disabled="!invoices.length"
                />
                <CFormCheck
                  type="checkbox"
                  label="Proyek di bawah 100 juta"
                  v-model="filterBelow100M"
                  @change="resetOtherFilters('below')"
                  :disabled="!invoices.length"
                />
              </div>
            </CCol>
          </CRow>

          <!-- Data Table for Invoices -->
          <div v-if="selectedProject">
            <div class="mb-3">
              <!-- <div class="alert alert-info">
                <strong>Total Pemasukan Proyek:</strong>
                <span class="float-end">{{ formatCurrency(projectTotalIncome) }}</span>
              </div> -->
            </div>
            <div style="overflow-x:auto; width:100%">
              <table ref="invoiceTableRef" class="display nowrap w-100"></table>
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
                              <td>Subtotal</td>
                              <td class="text-end">{{ formatCurrency(totalInvoice) }}</td>
                            </tr>
                            <tr v-if="filterPpn">
                              <td>PPN (11%)</td>
                              <td class="text-end">{{ formatCurrency(filteredPpnAmount) }}</td>
                            </tr>
                            <tr v-if="filterPphNonFinal">
                              <td>PPH Non Final (2.5%)</td>
                              <td class="text-end">
                                -{{ formatCurrency(filteredPphNonFinalTotal) }}
                              </td>
                            </tr>
                            <tr v-if="filterPphFinal">
                              <td>PPH Final (22%)</td>
                              <td class="text-end">-{{ formatCurrency(filteredPphFinalAmount) }}</td>
                            </tr>
                            <tr>
                              <td><b>Grand Total</b></td>
                              <td class="text-end"><b>{{ formatCurrency(filteredGrandTotal) }}</b></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </CCol>
                  </CRow>
                </div>
              </div>
            </div>

            <div v-if="anggaranProyek">
              <div class="alert alert-warning" v-if="totalInvoice > anggaranProyek">
                <strong>Peringatan!</strong> Total invoice melebihi anggaran proyek (Rp {{ formatCurrency(anggaranProyek) }})
              </div>
              <div class="progress mb-2">
                <div class="progress-bar" :style="{ width: ((totalInvoice / anggaranProyek) * 100) + '%' }">
                  {{ ((totalInvoice / anggaranProyek) * 100).toFixed(0) }}%
                </div>
              </div>
              <p>Total terpakai: <b>Rp {{ formatCurrency(totalInvoice) }}</b> / <b>Rp {{ formatCurrency(anggaranProyek) }}</b></p>
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
          <!-- Project Selection -->
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

          <!-- Invoice Details -->
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

          <!-- Payment Method -->
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="payment_method_id">Metode Pembayaran</CFormLabel>
              <CFormSelect
                id="payment_method_id"
                v-model="form.payment_method_id"
                :disabled="modalMode === 'view'"
                required
              >
                <option value="">Pilih Metode Pembayaran</option>
                <option v-for="method in paymentMethods" :key="method.id" :value="String(method.id)">
                  {{ method.nama_metode }}
                </option>
              </CFormSelect>
            </CCol>
          </CRow>

          <!-- Items Section -->
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
                      <option v-for="unit in units" :key="unit.id" :value="String(unit.id)">
                        {{ unit.unit_name }}
                      </option>
                    </CFormSelect>
                  </CCol>
                  <CCol md="4">
                    <CFormLabel>{{ isServiceType(item) ? 'Kategori Jasa' : 'Kategori Barang' }}</CFormLabel>
                   <CFormSelect
  v-if="isServiceType(item)"
  v-model="item.service_category_id"
  :disabled="!item.unit_id"
  @change="handleCategoryChange(item)"
>
  <option value="">Pilih Kategori Jasa</option>
  <option
    v-for="category in getServiceCategoriesByUnit(item.unit_id, item)"
    :key="category.id"
    :value="String(category.id)"
    :selected="String(category.id) === String(item.service_category_id)"
  >
    {{ category.nama_kategori }} ({{ formatCurrency(category.harga || category.price) }})
  </option>
</CFormSelect>
                    <CFormSelect
  v-else
  v-model="item.category_id"
  :disabled="!item.unit_id"
  @change="handleCategoryChange(item)"
>
  <option value="">Pilih Kategori Barang</option>
  <option
    v-for="category in getMaterialCategories(item.unit_id, item)"
    :key="category.id"
    :value="String(category.id)"
    :selected="String(category.id) === String(item.category_id)"
  >
    {{ category.nama_kategori }}
  </option>
</CFormSelect>
                    <div v-if="isServiceType(item) && getServiceCategoriesByUnit(item.unit_id, item).length === 0" class="text-danger small mt-1">
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
                      @change="handleMerekChange(item)"
                    >
                      <option value="">Pilih Merek</option>
                      <option value="new">+ Tambah Merek Baru</option>
                      <option v-for="merek in mereks" :key="merek.id" :value="String(merek.id)">
                        {{ merek.name }}
                        <!-- DEBUG: -->
                        <!-- id:{{String(merek.id)}} selected:{{String(item.merek_id) === String(merek.id)}} -->
                      </option>
                    </CFormSelect>
                    <div class="small text-muted">
                      <!-- DEBUG: item.merek_id={{item.merek_id}} | mereks.value=[{{ mereks.map(m => m.id).join(',') }}] -->
                    </div>
                    <div v-if="item.merek_id === 'new'" class="mt-2">
                      <CFormLabel for="newBrandName">Nama Merek Baru</CFormLabel>
                      <CFormInput id="newBrandName" v-model="newBrandName" required />
                      <div class="mt-2">
                        <CButton color="primary" size="sm" @click="saveNewBrandInline(item)">Simpan</CButton>
                        <CButton color="secondary" size="sm" @click="cancelNewBrand(item)">Batal</CButton>
                      </div>
                    </div>
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
                    <FontAwesomeIcon :icon="['fas', 'trash']" /> Hapus Item
                  </CButton>
                  </CCol>
                </CRow>
              </div>
              <div class="mt-3">
                <CButton v-if="modalMode !== 'view'" color="success" size="sm" @click="addItem">
                  <FontAwesomeIcon :icon="['fas', 'plus']" /> Tambah Item
                </CButton>
              </div>
            </CCol>
          </CRow>

          <!-- Tax Information -->
          <CRow class="mb-3">
            <CCol md="12">
              <h5>Informasi Pajak</h5>
              <div class="border p-3">
                <CRow class="mb-3">
                  <CCol md="12">
                    <div class="d-flex gap-3">
                      <CFormCheck
                        type="checkbox"
                        label="Gunakan PPN (11%)"
                        v-model="form.use_ppn"
                        id="use_ppn"
                      />
                      <CFormCheck
                        type="checkbox"
                        label="Gunakan PPH Non Final"
                        v-model="form.use_pph_non_final"
                        id="use_pph_non_final"
                      />
                      <CFormCheck
                        type="checkbox"
                        label="Gunakan PPH Final"
                        v-model="form.use_pph_final"
                        id="use_pph_final"
                      />
                    </div>
                  </CCol>
                </CRow>
                <CRow>
                  <CCol md="6">
                    <div class="mb-2">
                      <strong>Total Pembelian:</strong>
                      <span class="float-end">{{ formatCurrency(totalInvoice) }}</span>
                    </div>
                    <div class="mb-2" v-if="form.use_ppn">
                      <strong>PPN (11%):</strong>
                      <span class="float-end">{{ formatCurrency(ppnAmount) }}</span>
                    </div>
                    <div class="mb-2" v-if="form.use_pph_non_final">
                      <strong>PPH Non Final (2.5%):</strong>
                      <span class="float-end">{{ formatCurrency(pphNonFinalTotal) }}</span>
                    </div>
                    <div class="mb-2" v-if="form.use_pph_non_final">
                      <strong>Total PPH Non Final:</strong>
                      <span class="float-end">{{ formatCurrency(pphNonFinalTotal) }}</span>
                    </div>
                  </CCol>
                  <CCol md="6">
                    <!-- <div class="mb-2">
                      <strong>Laba Bersih ({{ profitMarginLabel }}):</strong>
                      <span class="float-end">{{ formatCurrency(netProfit) }}</span>
                    </div> -->
                    <div class="mb-2" v-if="form.use_pph_final">
                      <strong>PPH Final (3%):</strong>
                      <span class="float-end">{{ formatCurrency(pphFinal) }}</span>
                    </div>
                    <div class="mb-2">
                      <strong>Total Pajak:</strong>
                      <span class="float-end">{{ formatCurrency(totalTax) }}</span>
                    </div>
                    <div class="mb-2">
                      <strong>Total dengan Pajak:</strong>
                      <span class="float-end">{{ formatCurrency(totalWithTax) }}</span>
                    </div>
                  </CCol>
                </CRow>
              </div>
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

    <CModal :visible="showBrandModal" @close="closeBrandModal" title="Tambah Merek Baru">
      <CModalBody>
        <CForm @submit.prevent="saveNewBrand">
          <CFormLabel for="newBrandName">Nama Merek</CFormLabel>
          <CFormInput id="newBrandName" v-model="newBrandName" required />
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeBrandModal">Batal</CButton>
        <CButton color="primary" @click="saveNewBrand">Simpan</CButton>
      </CModalFooter>
    </CModal>
  </CRow>

  <!-- Tampilkan pesan jika hasil filter kosong -->
  <!-- <div v-if="filteredInvoices.length === 0" class="alert alert-info">
    Tidak ada invoice sesuai filter.
  </div> -->
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref, onMounted, watch, computed, nextTick, onUnmounted, h, render } from "vue";
import axios from "axios";
import $ from "jquery";
import Swal from "sweetalert2";
import moment from "moment";
import 'datatables.net-dt/css/dataTables.dataTables.min.css';
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css';
import 'datatables.net';
import 'datatables.net-responsive';

import { useRoute } from 'vue-router'

const name = 'Invoice';

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
const paymentMethods = ref([])
const showBrandModal = ref(false)
const newBrandName = ref('')
const brandItemRef = ref(null)
const projectTotalIncome = ref(0);
const editingId = ref(null)
const anggaranProyek = ref(0);
const filterPpn = ref(false)
const filterPphNonFinal = ref(false)
const filterPphFinal = ref(false)

const filterAbove100M = ref(false)
const filterBelow100M = ref(false)


// Filtered invoices by nominal
const filteredInvoices = computed(() => {
  let filtered = invoices.value;
  if (filterAbove100M.value) {
    filtered = filtered.filter(inv => parseFloat(inv.total_amount || 0) > 100_000_000);
  } else if (filterBelow100M.value) {
    filtered = filtered.filter(inv => parseFloat(inv.total_amount || 0) <= 100_000_000);
  }
  return filtered;
});

// Fungsi untuk reset filter budget (agar hanya satu aktif)
function resetOtherFilters(type) {
  if (type === 'above') filterBelow100M.value = false;
  if (type === 'below') filterAbove100M.value = false;
}

// Gunakan filteredInvoices untuk summary agar konsisten dengan filter
// Duplicated totalInvoice removed. Sudah dideklarasikan di atas.

// Duplicated subtotalBarang removed. Sudah dideklarasikan di atas.

const subtotalJasa = computed(() => {
  if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
    return form.value.purchase_materials.filter(item => isServiceType(item)).reduce((sum, item) => {
      const harga = typeof item.harga === 'string' ? parseFloat(item.harga.replace(/\./g, '')) : item.harga;
      return sum + (item.qty * harga);
    }, 0);
  }
  return filteredInvoices.value.reduce((sum, inv) => sum + parseFloat(inv.total_jasa || 0), 0);
});

// Tampilkan pesan jika hasil filter kosong
// (Tambahkan di template, contoh:)
// <div v-if="filteredInvoices.length === 0" class="alert alert-info">Tidak ada invoice sesuai filter.</div>

// Watch for filter changes and update DataTable
watch([filteredInvoices], () => {
  if (dataTable && invoiceTableRef.value) {
    dataTable.clear();
    dataTable.rows.add(filteredInvoices.value);
    dataTable.draw();
  }
});


// Project total income should use filteredInvoices
// (Hapus deklarasi duplikat, gunakan yang sudah ada di atas jika sudah dideklarasikan)



// Subtotal barang dan jasa

const subtotalBarang = computed(() => {
  if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
    return form.value.purchase_materials.filter(item => !isServiceType(item)).reduce((sum, item) => {
      const harga = typeof item.harga === 'string' ? parseFloat(item.harga.replace(/\./g, '')) : item.harga;
      return sum + (item.qty * harga);
    }, 0);
  }
  // summary mode: sum dari semua invoice (filtered)
  return filteredInvoices.value.reduce((sum, inv) => sum + parseFloat(inv.total_barang || 0), 0);
});

// Duplicated subtotalJasa removed. Sudah dideklarasikan di atas.


// === USE BACKEND FIELDS FOR SUMMARY ===
// Sum of backend-calculated fields for filtered invoices
const filteredPpnAmount = computed(() => {
  return filteredInvoices.value.reduce((sum, inv) => sum + (Number(inv.ppn_amount) || 0), 0);
});

const filteredPphNonFinalAmount = computed(() => {
  return filteredInvoices.value.reduce((sum, inv) => sum + (Number(inv.pph_non_final_amount) || 0), 0);
});

const filteredPphFinalAmount = computed(() => {
  return filteredInvoices.value.reduce((sum, inv) => sum + (Number(inv.pph_final_amount) || 0), 0);
});

const filteredGrandTotal = computed(() => {

  // Hitung subtotal terlebih dahulu
  const subtotal = filteredInvoices.value.reduce((sum, inv) => sum + Number(inv.total_amount || 0), 0);

  // Jika tidak ada filter yang dicentang, tampilkan subtotal
  if (!filterPpn.value && !filterPphNonFinal.value && !filterPphFinal.value) {
    return subtotal;
  }

  // Hitung nilai pajak berdasarkan filter yang dicentang
  const ppnAmount = filterPpn.value ? filteredInvoices.value.reduce((sum, inv) => sum + Number(inv.ppn_amount || 0), 0) : 0;
  const pphNonFinalAmount = filterPphNonFinal.value ? filteredInvoices.value.reduce((sum, inv) => sum + Number(inv.pph_non_final_amount || 0), 0) : 0;
  const pphFinalAmount = filterPphFinal.value ? filteredInvoices.value.reduce((sum, inv) => sum + Number(inv.pph_final_amount) || 0, 0) : 0;


  // Hitung Grand Total dengan penyesuaian pajak
  const grandTotal = subtotal + ppnAmount - pphNonFinalAmount - pphFinalAmount;
  return grandTotal;
});

// Watchers to ensure reactivity
watch([filterPpn, filterPphNonFinal, filterPphFinal, filteredInvoices], () => {
});


    const isServiceUnit = computed(() => {
      if (!form.value.unit_id) return false
      const selectedUnit = units.value.find(u => String(u.id) === String(form.value.unit_id))
      selectedUnitType.value = selectedUnit?.unit_name?.toLowerCase() || null
      return selectedUnit && ["jasa", "set", "transaksi"].includes(selectedUnit.unit_name.toLowerCase())
    })

    const handleUnitChange = (item) => {
      const oldHarga = item.harga
      item.unit_id = String(item.unit_id)
      const unit = units.value.find(u => String(u.id) === String(item.unit_id))
      const isService = unit && ['jasa', 'transaksi', 'set'].includes(unit.unit_name.toLowerCase())

      if (isService) {
        item.is_service = true
        item.harga = 0
        const defaultMerek = mereks.value.find(m => m.name === '-')
        if (defaultMerek) {
          item.merek_id = defaultMerek.id
        }
        item.noMaterialCategory = false
        fetchServiceCategories(item.unit_id, item)
        // Jangan reset service_category_id jika sudah ada (mode edit)
        if (typeof item.service_category_id === 'undefined' || item.service_category_id === null) {
          item.service_category_id = ''
        }
        item.category_id = null
      } else {
        item.is_service = false
        // Jangan reset merek_id jika sudah ada (mode edit)
        if (typeof item.merek_id === 'undefined' || item.merek_id === null) {
          item.merek_id = ''
        }
        item.harga = oldHarga
        const materialCategories = getMaterialCategories(item.unit_id)
        item.noMaterialCategory = materialCategories.length === 0
        // Jangan reset category_id jika sudah ada (mode edit)
        if (typeof item.category_id === 'undefined' || item.category_id === null) {
          item.category_id = ''
        }
        item.service_category_id = null
      }
    }

    const fetchServiceCategories = async (unitId, item) => {
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.get('/api/service-categories', {
          headers: { Authorization: `Bearer ${token}` }
        })
        const data = Array.isArray(response.data) ? response.data : (response.data.data ? response.data.data : [])
        item.serviceCategories = data.filter(cat => String(cat.unit_id) === String(unitId))

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
          axios.get('/api/kategori', {
            headers: { Authorization: `Bearer ${token}` }
          }),
          axios.get('/api/mereks', {
            headers: { Authorization: `Bearer ${token}` }
          }),
          axios.get('/api/service-categories', {
            headers: { Authorization: `Bearer ${token}` }
          })
        ])

        units.value = Array.isArray(unitsRes.data) ? unitsRes.data : (unitsRes.data.data || [])

        const categoriesData = Array.isArray(categoriesRes.data) ? categoriesRes.data : (categoriesRes.data.data || [])
        categories.value = categoriesData.map(cat => ({
          ...cat,
          id: String(cat.id),
          unit_id: String(cat.unit_id),
          is_service: false
        }))

        mereks.value = (Array.isArray(mereksRes.data) ? mereksRes.data : (mereksRes.data.data || [])).map(m => ({
          ...m,
          id: String(m.id),
          name: m.name || '',
        }))

        serviceCategories.value = (Array.isArray(serviceCategoriesRes.data) ? serviceCategoriesRes.data : (serviceCategoriesRes.data.data || [])).map(cat => ({
          ...cat,
          id: String(cat.id),
          unit_id: String(cat.unit_id)
        }))

  , {
          units: units.value,
          categories: categories.value,
          mereks: mereks.value,
          serviceCategories: serviceCategories.value
        }
      } catch (error) {
        error.value = 'Gagal memuat data master: ' + (error.response?.data?.message || error.message)
        categories.value = [];
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error.value
        })
      }
    }

    const form = ref({
      proyek_id: '',
      invoice_date: '',
      status: 'unpaid',
      notes: '',
      use_ppn: false,
      use_pph_non_final: false,
      use_pph_final: false,
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
      is_cash: 'cash',
      termins: [{nama_termin:'',nilai_termin:0,dp_percentage:0}],
      payment_method_id: ''
    })
    const modalTitle = ref('Tambah Invoice')
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

        const response = await axios.get('/api/proyeks', {
          headers: {
            Authorization: `Bearer ${token}`
          }
        })
        projects.value = response.data.data || []
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Gagal memuat data proyek: ' + (err.response?.data?.message || err.message)
        })
      }
    }

    const loadInvoices = async () => {
      if (!selectedProject.value) return;

      loading.value = true;
      try {
        const token = sessionStorage.getItem('token');
        if (!token) {
          window.location.href = '/login';
          return;
        }

        const response = await axios.get('/api/invoices', {
          params: { proyek_id: selectedProject.value },
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });

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
                render: (data, type, row) => `
                  <button class="btn btn-sm btn-info view-btn" data-id="${row.id}"><i class="fa fa-eye"></i></button>
                  <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}"><i class="fa fa-pen"></i></button>
                  <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}"><i class="fa fa-trash"></i></button>
                `,
                orderable: false
              }
            ],
            order: [[1, 'desc']],
            responsive: true
          });

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
        if (err.response?.status === 401) {
          sessionStorage.removeItem('token');
          window.location.href = '/login';
          return;
        }
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
      if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
        return form.value.purchase_materials.reduce((sum, item) => {
          const harga = typeof item.harga === 'string' ? parseFloat(item.harga.replace(/\./g, '')) : item.harga;
          return sum + (item.qty * harga);
        }, 0);
      }
      return invoices.value.reduce((sum, inv) => sum + parseFloat(inv.total_amount || 0), 0);
    });

    const totalPaid = computed(() => {
      if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
        return 0;
      }
      return invoices.value.reduce((sum, inv) => sum + parseFloat(inv.amount_paid || 0), 0);
    });

    const totalUnpaid = computed(() => {
      if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
        return totalInvoice.value;
      }
      return totalInvoice.value - totalPaid.value;
    });

    const overallStatus = computed(() => {
      if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
        if (totalUnpaid.value === 0 && totalInvoice.value > 0) return 'Lunas';
        if (totalPaid.value === 0) return 'Belum Dibayar';
        if (totalUnpaid.value > 0) return 'Dibayar Sebagian';
        return '-';
      }
      if (totalUnpaid.value === 0 && totalInvoice.value > 0) return 'Lunas';
      if (totalPaid.value === 0) return 'Belum Dibayar';
      if (totalUnpaid.value > 0) return 'Dibayar Sebagian';
      return '-';
    });

    const setHargaFromServiceCategory = (item) => {
      if (isServiceType(item) && item.category_id) { // Use service_category_id instead of category_id for service items
        const selectedCategory = getServiceCategoriesByUnit(item.unit_id, item).find(cat => String(cat.id) === String(item.service_category_id))
        if (selectedCategory) {
          item.harga = selectedCategory.harga !== undefined ? selectedCategory.harga : (selectedCategory.price !== undefined ? selectedCategory.price : 0)
          calculateTotalHarga(item)
        }
      }
    }

    const openModal = async (mode, id = null) => {
      modalMode.value = mode
      editingId.value = id
      modalTitle.value = mode === 'tambah' ? 'Tambah Invoice' : (mode === 'edit' ? 'Edit Invoice' : 'Detail Invoice')
  await fetchPaymentMethods(); // Load payment methods before showing the modal
      if ((mode === 'edit' || mode === 'view') && id) {
        await loadMasterData();
        await loadProjects();
        await loadInvoice(id);
        showModal.value = true;
      } else {
        await loadMasterData();
        await loadProjects();
        resetForm();
        form.value.purchase_materials.forEach(item => handleCategoryChange(item));
        showModal.value = true;
      }
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
        use_ppn: false,
        use_pph_non_final: false,
        use_pph_final: false,
        purchase_materials: [{
          item: '',
          type: '',
          qty: '',
          harga: '',
          unit_id: '', // Ensure this is initialized as empty string
          total_harga: '',
          spesifikasi: '',
          deskripsi: '',
          category_id: '',
          service_category_id: '',
          merek_id: '',
          expense_id: null,
          is_service: false,
          serviceCategories: []
        }],
        is_cash: 'cash',
        termins: [{nama_termin:'',nilai_termin:0,dp_percentage:0}],
        payment_method_id: ''
      }
      editingId.value = null
      form.value.purchase_materials.forEach(item => handleCategoryChange(item))
    }


    // Perbaikan: pastikan qty dan harga selalu valid number, dan total_harga tidak menjadi NaN/0 saat qty/harga dikurangi
    const calculateTotalHarga = (item) => {
      let qty = Number(item.qty);
      let harga = typeof item.harga === 'string' ? Number(item.harga.replace(/\./g, '')) : Number(item.harga);
      if (isNaN(qty) || qty < 0) qty = 0;
      if (isNaN(harga) || harga < 0) harga = 0;
      item.total_harga = qty * harga;
    }

    const addItem = () => {
      const newItem = {
        item: '',
        type: '',
        qty: '',
        harga: '',
        unit_id: '',
        total_harga: '',
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
      if (!form.value.payment_method_id) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Metode pembayaran harus dipilih!' });
        return false;
      }
      if (!form.value.purchase_materials || form.value.purchase_materials.length === 0) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Minimal satu item harus ditambahkan!' });
        return false;
      }

      for (const [i, item] of form.value.purchase_materials.entries()) {
        if (!item.item || item.item.trim() === '') {
          Swal.fire({ icon: 'error', title: 'Error', text: `Nama item ke-${i+1} harus diisi!` });
          return false;
        }
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
        const unit = units.value.find(u => String(u.id) === String(item.unit_id));
        const isService = unit && ["jasa", "set", "transaksi"].includes(unit.unit_name.toLowerCase());
        if (isService) {
          if (!item.service_category_id) {
            Swal.fire({ icon: 'error', title: 'Error', text: `Kategori jasa pada item ke-${i+1} harus dipilih!` });
            return false;
          }
        } else {
          if (!item.category_id) {
            Swal.fire({ icon: 'error', title: 'Error', text: `Kategori material pada item ke-${i+1} harus dipilih!` });
            return false;
          }
          if (!item.merek_id) {
            Swal.fire({ icon: 'error', title: 'Error', text: `Merek pada item ke-${i+1} harus dipilih!` });
            return false;
          }
        }
      }
      return true;
    }

    const totalTerminAmount = computed(() => {
      if (!form.value.termins) return 0;
      return form.value.termins.reduce((sum, termin) => sum + Number(termin.nilai_termin || 0), 0);
    });

    const totalTerminDP = computed(() => {
      if (!form.value.termins) return 0;
      return form.value.termins.reduce((sum, termin) => sum + Number(termin.nilai_dp || 0), 0);
    });

    const totalTerminPelunasan = computed(() => {
      if (!form.value.termins) return 0;
      return form.value.termins.reduce((sum, termin) => sum + Number(termin.nilai_pelunasan || 0), 0);
    });

    const calculateTerminValues = (termin) => {
      if (termin.nilai_termin && termin.dp_percentage) {
        termin.nilai_dp = (termin.nilai_termin * termin.dp_percentage) / 100;
        termin.nilai_pelunasan = termin.nilai_termin - termin.nilai_dp;
      }
    };

    const onTerminNilaiInput = (event, termin) => {
      let value = event.target.value.replace(/[^\d]/g, '');
      value = value.replace(/^0+/, '');
      termin.nilai_termin = value ? Number(value) : 0;
      calculateTerminValues(termin);
    };

    const addTermin = () => {
      const today = new Date().toISOString().split('T')[0];
      form.value.termins.push({
        nama_termin: '',
        nilai_termin: 0,
        dp_percentage: 0,
        nilai_dp: 0,
        nilai_pelunasan: 0,
        tanggal_dp: today,
        tanggal_pelunasan: today,
        keterangan: '',
        status_termin: 'Belum Dibayar'
      });
    };

    // === TOTAL TAX ===
    // Kompatibel dengan tampilan pajak di form modal
    const totalTax = computed(() => {
      let total = 0;
      if (form.value.use_ppn) total += Number(ppnAmount?.value || 0);
      if (form.value.use_pph_non_final) total += Number(pphNonFinalTotal?.value || 0);
      if (form.value.use_pph_final) total += Number(pphFinal?.value || 0);
      return total;
    });

    const terminSummary = computed(() => {
      if (form.value.is_cash === 'termin') {
        return {
          total: totalTerminAmount.value,
          dp: totalTerminDP.value,
          pelunasan: totalTerminPelunasan.value
        };
      }
      return {
        total: 0,
        dp: 0,
        pelunasan: 0
      };
    });

    const handleSubmit = async () => {
      if (!validateForm()) return;
      if (anggaranProyek.value && totalInvoice.value > anggaranProyek.value) {
        Swal.fire({
          icon: 'warning',
          title: 'Anggaran Melebihi Batas!',
          text: 'Total invoice yang Anda input melebihi anggaran proyek. Silakan cek kembali.'
        });
        return;
      }

      try {
        const token = sessionStorage.getItem('token');
        if (!token) {
          window.location.href = '/login';
          return;
        }

        try {
          await axios.get('/api/user', {
            headers: { Authorization: `Bearer ${token}` }
          });
        } catch (error) {
          if (error.response?.status === 401) {
            sessionStorage.removeItem('token');
            window.location.href = '/login';
            return;
          }
        }

        const payload = {
          ...form.value,
          purchase_materials: form.value.purchase_materials.map(item => ({
            ...item,
            qty: Number(item.qty) || 0,
            harga: typeof item.harga === 'string' ? Number(item.harga.replace(/\./g, '')) : Number(item.harga) || 0
          })),
          use_ppn: form.value.use_ppn,
          use_pph_non_final: form.value.use_pph_non_final,
          use_pph_final: form.value.use_pph_final
        };

        if (form.value.is_cash) {
          delete payload.termins;
        } else {
          delete payload.termins;
        }

        // 1. Create invoice (this will also trigger PurchaseMaterial observers,
        // which will create associated expenses and update invoice total_amount)
        const response = await axios.post('/api/invoices', payload, {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        });

        if (response.data.status === 'error') {
          throw new Error(response.data.message || 'Gagal membuat invoice');
        }

        const invoiceId = response.data.data.id || response.data.id; // Get the ID from the response
        if (form.value.is_cash === false) {
          // Redirect to termin page if it's a termin-based invoice
          window.location.href = `/termin?invoice_id=${invoiceId}`;
        } else {
          Swal.fire({
            icon: 'success',
            title: 'Sukses',
            text: 'Invoice berhasil ditambahkan'
          });
          closeModal();
          loadInvoices(); // Reload the data table
        }
      } catch (error) {
        if (error.response?.status === 401) {
          sessionStorage.removeItem('token');
          window.location.href = '/login';
          return;
        }
        const errorMessage = error.response?.data?.message || error.message || 'Gagal membuat invoice';
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMessage
        });
      }
    };

    const loadInvoice = async (id) => {
      try {
        const token = sessionStorage.getItem('token');
        const response = await axios.get(`/api/invoices/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        const invoice = response.data.data;
        form.value = {
          proyek_id: String(invoice.proyek?.id || ''),
          invoice_date: invoice.invoice_date ? invoice.invoice_date.substring(0, 10) : '',
          status: invoice.status || 'unpaid',
          payment_method_id: String(invoice.payment_method?.id || ''), // Ambil ID dari objek payment_method
          notes: invoice.notes || '',
          use_ppn: !!invoice.use_ppn,
          use_pph_non_final: !!invoice.use_pph_non_final,
          use_pph_final: !!invoice.use_pph_final,
          purchase_materials: Array.isArray(invoice.purchase_materials) && invoice.purchase_materials.length > 0
            ? invoice.purchase_materials.map(item => ({
                item: item.item || '',
                type: item.type || '',
                qty: item.qty || 1,
                harga: item.harga || '',
                unit_id: item.unit && item.unit.id ? String(item.unit.id) : '',
                total_harga: item.total_harga || 0,
                spesifikasi: item.spesifikasi || '',
                deskripsi: item.deskripsi || '',
                category_id: item.category && item.category.id ? String(item.category.id) : '',
                category: item.category ? {
                  id: String(item.category.id),
                  unit_id: String(item.category.unit_id),
                  nama_kategori: item.category.nama_kategori
                } : null,
                service_category_id: item.service_category && item.service_category.id ? String(item.service_category.id) : '',
                service_category: item.service_category ? {
                  id: String(item.service_category.id),
                  unit_id: String(item.service_category.unit_id),
                  nama_kategori: item.service_category.nama_kategori,
                  harga: item.service_category.harga || item.service_category.price
                } : null,
                merek_id: item.merek && item.merek.id ? String(item.merek.id) : '',
                merek: item.merek ? {
                  id: String(item.merek.id),
                  name: item.merek.name
                } : null,
                expense_id: item.expense_id || null, // Ensure expense_id is loaded
                is_service: !!item.is_service,
                serviceCategories: item.serviceCategories || []
              }))
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
                category: null,
                service_category_id: '',
                service_category: null,
                merek_id: '',
                merek: null,
                expense_id: null,
                is_service: false,
                serviceCategories: []
              }],
        };

    // Validasi payment_method_id
    const validPaymentMethod = paymentMethods.value.find(
    (method) => String(method.id) === String(form.value.payment_method_id)
    );

    if (!validPaymentMethod) {
      Swal.fire({
        icon: 'warning',
        title: 'Peringatan',
        text: 'Metode pembayaran tidak valid, silakan pilih metode pembayaran yang tersedia.',
      });
      form.value.payment_method_id = ''; // Reset jika tidak valid
    } else {
      form.value.payment_method_id = String(validPaymentMethod.id);
    }

        if (Array.isArray(invoice.purchase_materials)) {
          invoice.purchase_materials.forEach(item => {
            if (item.category && !categories.value.some(c => String(c.id) === String(item.category.id))) {
              categories.value.push({
                ...item.category,
                id: String(item.category.id),
                unit_id: String(item.category.unit_id)
              });
            }
            if (item.service_category && !serviceCategories.value.some(sc => String(sc.id) === String(item.service_category.id))) {
              serviceCategories.value.push({
                ...item.service_category,
                id: String(item.service_category.id),
                unit_id: String(item.service_category.unit_id)
              });
            }
            // Tambahkan merek ke master jika belum ada
            if (item.merek && !mereks.value.some(m => String(m.id) === String(item.merek.id))) {
              mereks.value.push({
                ...item.merek,
                id: String(item.merek.id).trim(),
                name: item.merek.name
              });
              // Normalisasi seluruh id di mereks agar selalu string
              mereks.value = mereks.value.map(m => ({ ...m, id: String(m.id) }))
              // Force reactivity after push
              mereks.value = [...mereks.value];
              // Trigger reactivity
              nextTick(() => {});
            }
             // Validasi payment_method_id
          });

        }
        // Trigger handleUnitChange dan handleCategoryChange untuk setiap item agar dropdown sinkron
        nextTick(() => {
          form.value.purchase_materials.forEach(item => {
            handleUnitChange(item);
            handleCategoryChange(item);
          })
        })
      } catch (err) {
        let errorMsg = 'Gagal memuat data invoice';
        if (err.response && err.response.data && err.response.data.message) {
          errorMsg += ': ' + err.response.data.message;
        }
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: errorMsg,
        });

      }
    }

    const deleteInvoice = async (id) => {
      const result = await Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Invoice akan dihapus secara permanen! Ini juga akan menghapus semua item pembelian, termin, dan pengeluaran terkait.",
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
            text: 'Gagal menghapus invoice: ' + (err.response?.data?.message || err.message)
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

    const isServiceType = (item) => {
      const unit = units.value.find(u => String(u.id) === String(item.unit_id))
      return unit && ['jasa', 'transaksi', 'set'].includes(unit.unit_name.toLowerCase())
    }

    const getServiceCategoriesByUnit = (unitId, item = null) => {
  if (!unitId) return [];

  // Ambil dari data master
  let filtered = serviceCategories.value.filter(cat => String(cat.unit_id) === String(unitId));

  // Jika sedang edit dan kategori tidak ada di master, tambahkan kategori yang dipilih
  if (item && item.service_category_id && !filtered.some(cat => String(cat.id) === String(item.service_category_id))) {
    if (item.service_category) {
      filtered = [
        {
          id: String(item.service_category.id),
          unit_id: String(item.service_category.unit_id),
          nama_kategori: item.service_category.nama_kategori,
          harga: item.service_category.harga || item.service_category.price
        },
        ...filtered
      ];
    }
  }
  return filtered;
};

const getMaterialCategories = (unitId, item = null) => {
  if (!unitId) return [];

  // Ambil dari data master
  let filtered = categories.value.filter(cat => String(cat.unit_id) === String(unitId));

  // Jika sedang edit dan kategori tidak ada di master, tambahkan kategori yang dipilih
  if (item && item.category_id && !filtered.some(cat => String(cat.id) === String(item.category_id))) {
    if (item.category) {
      filtered = [
        {
          id: String(item.category.id),
          unit_id: String(item.category.unit_id),
          nama_kategori: item.category.nama_kategori
        },
        ...filtered
      ];
    }
  }
  return filtered;
};

    const handleCategoryChange = (item) => {
      const unit = units.value.find(u => String(u.id) === String(item.unit_id))
      const isService = unit && ['jasa', 'transaksi', 'set'].includes(unit.unit_name.toLowerCase())
      if (isService) {
        const categories = getServiceCategoriesByUnit(item.unit_id, item)
        const selectedCategory = categories.find(cat => String(cat.id) === String(item.service_category_id))
        if (selectedCategory) {
          item.noServiceCategory = false
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
        item.category_id = null
        item.noMaterialCategory = false
      } else {
        item.service_category_id = null
        if (item.category_id) {
          item.noMaterialCategory = false
        } else {
          const categories = getMaterialCategories(item.unit_id)
          item.noMaterialCategory = categories.length === 0
        }
      }
    }

    const onHargaInput = (event, item) => {
      if (!isServiceType(item)) {
        let value = event.target.value.replace(/[^\d]/g, '')
        value = value.replace(/^0+/, '')
        if (value) {
          item.harga = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".")
        } else {
          item.harga = ''
        }
        calculateTotalHarga(item);
      }
    }
    // Watcher: qty berubah, update total_harga
    watch(
      () => form.value.purchase_materials.map(item => item.qty),
      (newVals, oldVals) => {
        form.value.purchase_materials.forEach(item => {
          calculateTotalHarga(item);
        })
      },
      { deep: true }
    )

    watch(
      () => form.value.purchase_materials.map(item => [item.category_id, item.unit_id]),
      (newVals, oldVals) => {
        form.value.purchase_materials.forEach(item => {
          if (isServiceType(item) && item.service_category_id && item.unit_id) { // Changed category_id to service_category_id
            const selectedCategory = getServiceCategoriesByUnit(item.unit_id, item).find(cat => String(cat.id) === String(item.service_category_id)) // Changed category_id to service_category_id
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

    const pphNonFinalBarang = computed(() => {
      // With flat rate, PPH Non Final Barang is no longer calculated separately
      return 0;
    });

    const pphNonFinalJasa = computed(() => {
      // With flat rate, PPH Non Final Jasa is no longer calculated separately
      return 0;
    });


    // === Pajak & Grand Total: SAMA DENGAN BACKEND ===
    // Semua dihitung dari subtotal (totalInvoice)
    const ppnAmount = computed(() => form.value.use_ppn ? (totalInvoice.value * 0.11) : 0);
    const pphNonFinalTotal = computed(() => form.value.use_pph_non_final ? (totalInvoice.value * 0.025) : 0);
    const pphFinal = computed(() => form.value.use_pph_final ? (totalInvoice.value * 0.03) : 0);
    const grandTotal = computed(() => totalInvoice.value + ppnAmount.value - pphNonFinalTotal.value - pphFinal.value);
    const totalWithTax = grandTotal;

    const fetchPaymentMethods = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/payment-methods', {
      headers: { Authorization: `Bearer ${token}` }
    });
    if (Array.isArray(response.data.data)) {
      paymentMethods.value = response.data.data;
    } else if (Array.isArray(response.data)) {
      paymentMethods.value = response.data;
    } else {
      paymentMethods.value = [];
    }
  } catch (e) {
    paymentMethods.value = [];
    // console.error('Error fetching payment methods:', e);
  }
};

    // Tidak perlu mountCoreUIIcons, gunakan FontAwesomeIcon langsung di template
    const initDataTable = () => {
      if ($.fn.DataTable.isDataTable(invoiceTableRef.value)) {
        $(invoiceTableRef.value).DataTable().clear().destroy();
      }
      $(invoiceTableRef.value).DataTable({
        data: invoices.value,
        columns: [
          { title: 'No', data: null, render: (data, type, row, meta) => meta.row + 1 },
          { title: 'No Invoice', data: 'invoice_number' },
          { title: 'Tanggal', data: 'invoice_date', render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : '-' },
          { title: 'Total Amount', data: 'total_amount', render: (data) => `Rp ${new Intl.NumberFormat('id-ID').format(data)}` },
          { title: 'Amount Paid', data: 'amount_paid', render: (data) => `Rp ${new Intl.NumberFormat('id-ID').format(data)}` },
          { title: 'Jumlah Item', data: 'purchase_materials', render: (data) => data ? data.length : 0, className: 'text-center' },
          { title: 'Status', data: 'status', render: (data) => {
            const statusMap = {
              unpaid: '<span class="badge bg-danger">Belum Dibayar</span>',
              partially_paid: '<span class="badge bg-warning">Dibayar Sebagian</span>',
              paid: '<span class="badge bg-success">Lunas</span>',
              cancelled: '<span class="badge bg-secondary">Dibatalkan</span>'
            };
            return statusMap[data] || data;
          }},
          {
            title: 'Actions',
            data: null,
            render: (data, type, row) => `
          <button class="btn btn-sm btn-info view-btn" data-id="${row.id}"><span><i class='fa fa-eye'></i></span></button>
          <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}"><span><i class='fa fa-pen'></i></span></button>
          <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}"><span><i class='fa fa-trash'></i></span></button>
              <button class="btn btn-sm btn-primary status-btn" data-id="${row.id}">Update Status</button>
            `,
            orderable: false
          }
        ],
        scrollX: true,
        autoWidth: false,
        responsive: false
      });

      // Event listeners for action buttons
      $(invoiceTableRef.value).on('click', '.view-btn', function() {
        const id = $(this).data('id');
        openModal('view', id);
      });
      $(invoiceTableRef.value).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        openModal('edit', id);
      });
      $(invoiceTableRef.value).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteInvoice(id);
      });
    };

    const openBrandModal = (item) => {
      showBrandModal.value = true
      newBrandName.value = ''
      brandItemRef.value = item
    }

    const closeBrandModal = () => {
      showBrandModal.value = false
      newBrandName.value = ''
      brandItemRef.value = null
    }

    const handleMerekChange = (item) => {
      // Paksa string
      if (typeof item.merek_id === 'number') {
        item.merek_id = String(item.merek_id)
      }
      if (item.merek_id === 'new') {
        openBrandModal(item)
      }
    }

    const saveNewBrand = async () => {
      if (!newBrandName.value.trim()) {
        Swal.fire({ icon: 'error', title: 'Nama merek wajib diisi!' })
        return
      }
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.post('/api/mereks', { name: newBrandName.value }, {
          headers: { Authorization: `Bearer ${token}` }
        })
        mereks.value.push(response.data)
        if (brandItemRef.value) {
          brandItemRef.value.merek_id = response.data.id
        }
        closeBrandModal()
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Merek baru berhasil ditambahkan' })
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menambahkan merek baru' })
      }
    }

    const saveNewBrandInline = async (item) => {
      if (!newBrandName.value.trim()) {
        Swal.fire({ icon: 'error', title: 'Nama merek wajib diisi!' })
        return
      }
      try {
        const token = sessionStorage.getItem('token')
        const response = await axios.post('/api/mereks', { name: newBrandName.value }, {
          headers: { Authorization: `Bearer ${token}` }
        })
        mereks.value.push(response.data)
        item.merek_id = response.data.id
        newBrandName.value = ''
        Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Merek baru berhasil ditambahkan' })
      } catch (error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal menambahkan merek baru' })
      }
    }

    const cancelNewBrand = (item) => {
      item.merek_id = ''
      newBrandName.value = ''
    }

    const fetchProjectSummary = async () => {
      if (!selectedProject.value) {
        projectTotalIncome.value = 0;
        return;
      }
      try {
        const token = sessionStorage.getItem('token');
        const res = await axios.get(`/api/proyek/${selectedProject.value}/summary`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        projectTotalIncome.value = res.data.total_income || 0;
      } catch (e) {
        projectTotalIncome.value = 0;
      }
    };

    const fetchProjectBudget = async (projectId) => {
      try {
        const token = sessionStorage.getItem('token');
        const res = await axios.get(`/api/proyeks/${projectId}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        anggaranProyek.value = res.data.anggaran_kontrak || 0;
      } catch (e) {
        anggaranProyek.value = 0;
      }
    };

    watch(selectedProject, (newVal) => {
      if (newVal) {
        fetchProjectBudget(newVal);
        loadInvoices();
      }
    });

    onMounted(() => {
      loadMasterData()
      loadProjects()
      loadInvoices()
      fetchPaymentMethods()
      nextTick(initDataTable)
      const handler = () => loadInvoices()
      window.addEventListener('termin-updated', handler)
      onUnmounted(() => {
        window.removeEventListener('termin-updated', handler)
      })
      fetchProjectSummary();
      // Debug log dihapus
    })

    watch(selectedProject, (newValue) => {
      if (newValue) {
        loadInvoices()
      }
    })

    watch(
      () => route.fullPath,
      (newPath, oldPath) => {
        if (newPath.includes('/invoice')) {
          loadInvoices()
        }
      }
    )

    const totalIncome = computed(() => {
      if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
        return netProfit.value;
      }
      return invoices.value.reduce((sum, inv) => sum + parseFloat(inv.total_income || 0), 0);
    });

    const totalExpenses = computed(() => {
      if (showModal.value && (modalMode.value === 'tambah' || modalMode.value === 'edit')) {
        return 0;
      }
      return invoices.value.reduce((sum, inv) => sum + parseFloat(inv.total_expenses || 0), 0);
    });

    const profitLoss = computed(() => {
      return totalIncome.value - totalExpenses.value;
    });

    const profitLossPercentage = computed(() => {
      if (totalExpenses.value === 0) return 0;
      return (profitLoss.value / totalExpenses.value) * 100;
    });

    const goToTermin = () => {
      const invoiceData = {
        proyek_id: form.value.proyek_id,
        invoice_date: form.value.invoice_date,
        payment_method_id: form.value.payment_method_id,
        notes: form.value.notes,
        purchase_materials: form.value.purchase_materials
      };
      sessionStorage.setItem('tempInvoiceData', JSON.stringify(invoiceData));

      window.location.href = '/termin';
    };

    axios.interceptors.response.use(
      response => response,
      error => {
        if (error.response?.status === 401) {
          sessionStorage.removeItem('token');
          window.location.href = '/login';
        }
        return Promise.reject(error);
      }
    );


    const canUsePpn = computed(() => {
      return invoices.value.some(inv => inv.use_ppn)
    })
    const canUsePphNonFinal = computed(() => {
      return invoices.value.some(inv => inv.use_pph_non_final)
    })
    const canUsePphFinal = computed(() => {
      return invoices.value.some(inv => inv.use_pph_final)
    })

// Dinamis: label margin sesuai form
const profitMarginLabel = computed(() => {
  const margin = form.value?.profit_margin_percentage;
  if (margin !== undefined && margin !== null && margin !== '') {
    return `${parseFloat(margin)}%`;
  }
  return '30%';
});


// Dinamis: perhitungan laba bersih pakai margin dari form
const netProfit = computed(() => {
  let margin = 0.3;
  if (
    form.value &&
    form.value.profit_margin_percentage !== undefined &&
    form.value.profit_margin_percentage !== null &&
    form.value.profit_margin_percentage !== ''
  ) {
    const parsed = parseFloat(form.value.profit_margin_percentage);
    if (!isNaN(parsed)) margin = parsed / 100;
  }
  return totalInvoice.value * margin;
});

// Debugging logs dihapus

watch([subtotalBarang, subtotalJasa, filterPphNonFinal], () => {
});

// Total PPH Non Final dari result API untuk summary (bukan dari form)
const filteredPphNonFinalTotal = computed(() => {
  return filteredInvoices.value.reduce((sum, inv) => sum + (Number(inv.pph_non_final_amount) || 0), 0);

});

// Watcher untuk log perubahan filter pajak dan filteredInvoices
watch([filterPpn, filterPphNonFinal, filterPphFinal, filteredInvoices], ([newFilterPpn, newFilterPphNonFinal, newFilterPphFinal, newFilteredInvoices]) => {
});

// Ensure reactivity for the "Grand Total" calculation
watch([filterPpn, filterPphNonFinal, filterPphFinal, filteredInvoices], () => {
});
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
