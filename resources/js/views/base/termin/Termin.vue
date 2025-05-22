<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <CIcon icon="cil-money" /> Manajemen Termin
          <div class="float-end btn-group">
            <!-- Tambah Tombol Export/Import -->
            <CButton color="secondary" @click="exportToPDF" class="me-2">
              <CIcon icon="cil-cloud-download" /> PDF
            </CButton>
            <CButton color="success" @click="exportToExcel" class="me-2">
              <CIcon icon="cil-spreadsheet" /> Excel
            </CButton>
            <CButton color="warning" @click="$refs.fileInput.click()">
              <CIcon icon="cil-cloud-upload" /> Import
            </CButton>
            <input
              type="file"
              ref="fileInput"
              @change="handleExcelImport"
              style="display: none"
              accept=".xlsx, .xls"
            >
          </div>

          <CButton color="primary" @click="openModal('tambah')" class="float-end" :disabled="!selectedProject || !selectedInvoice">
            Tambah Termin
          </CButton>
        </CCardHeader>

        <CCardBody>
          <!-- Project Selection -->
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel>Pilih Proyek</CFormLabel>
              <CFormSelect
                v-model="selectedProject"
                :options="[{ value: '', label: '-- Pilih Proyek --' }, ...projectOptions]"
                @change="handleProjectChange"
              />
            </CCol>
            <CCol md="6">
              <CFormLabel>Pilih Invoice</CFormLabel>
              <CFormSelect
                v-model="selectedInvoice"
                :options="[{ value: '', label: '-- Pilih Invoice --' }, ...invoiceOptions]"
                :disabled="!selectedProject"
                @change="handleInvoiceChange"
              />
            </CCol>
          </CRow>

          <!-- Summary Cards -->
          <CRow class="mb-4" v-if="selectedProject && selectedInvoice">
            <CCol md="3">
              <CCard class="bg-primary text-white">
                <CCardBody>
  <h6>Total Termin</h6>
  <h3>{{ formatCurrency(summary.total_termin) }}</h3>
</CCardBody>
              </CCard>
            </CCol>
            <CCol md="3">
              <CCard class="bg-success text-white">
                <CCardBody>
                  <h6>Total DP</h6>
                  <h3>{{ formatCurrency(summary.total_dp) }}</h3>
                </CCardBody>
              </CCard>
            </CCol>
            <CCol md="3">
              <CCard class="bg-info text-white">
                <CCardBody>
                  <h6>Total Pelunasan</h6>
                  <h3>{{ formatCurrency(summary.total_pelunasan) }}</h3>
                </CCardBody>
              </CCard>
            </CCol>
            <CCol md="3">
              <CCard class="bg-warning text-white">
                <CCardBody>
                  <h6>Sisa Belum Dibayar</h6>
                  <h3>{{ formatCurrency(summary.sisa_belum_dibayar) }}</h3>
                </CCardBody>
              </CCard>
            </CCol>
          </CRow>

          <!-- DataTable -->
          <div class="w-100" v-if="selectedProject && selectedInvoice">
            <table id="terminTable" class="display" style="width:100%">
              <thead>
                <tr>
                  <th>Nama Termin</th>
                  <th>Jenis Termin</th>
                  <th>Termin Ke</th>
                  <th>Nilai Termin</th>
                  <th>Persentase DP</th>
                  <th>Nilai DP</th>
                  <th>Nilai Pelunasan</th>
                  <th>Tanggal DP</th>
                  <th>Deadline Pembayaran</th>
                  <th>Status</th>
                  <th>Status Approval</th>
                  <th>Disetujui Oleh</th>
                  <th>Waktu Disetujui</th>
                  <th>Tgl DP Dibayar</th>
                  <th>Tgl Pelunasan Dibayar</th>
                  <th>Keterangan</th>
                  <th>Bukti Pembayaran</th>
                  <th>Dibuat</th>
                  <th>Diupdate</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in termins" :key="item.id">
                  <td>{{ item.nama_termin }}</td>
                  <td>{{ item.jenis_termin }}</td>
                  <td>{{ item.termin_ke }}</td>
                  <td>{{ formatCurrency(item.nilai_termin) }}</td>
                  <td>{{ item.persentase_dp }}%</td>
                  <td>{{ formatCurrency(item.nilai_dp) }}</td>
                  <td>{{ formatCurrency(item.nilai_pelunasan) }}</td>
                  <td>{{ item.tanggal_dp }}</td>
                  <td>{{ item.tanggal_pelunasan }}</td>
                  <td>
                    <CBadge :color="getStatusColor(item.status_termin)">
                      {{ item.status_termin }}
                    </CBadge>
                  </td>
                  <td>
                    <CBadge :color="getApprovalColor(item.status_approval)">
                      {{ item.status_approval }}
                    </CBadge>
                  </td>
                  <td>{{ item.approved_by_name }}</td>
                  <td>{{ item.approved_at }}</td>
                  <td>{{ item.tanggal_dp_dibayar }}</td>
                  <td>{{ item.tanggal_pelunasan_dibayar }}</td>
                  <td>{{ item.keterangan }}</td>
                  <td>
                    <a v-if="item.bukti_pembayaran_url" :href="item.bukti_pembayaran_url" target="_blank" class="btn btn-sm btn-info">
                      <CIcon icon="cil-file" /> Lihat
                    </a>
                  </td>
                  <td>{{ formatDate(item.created_at) }}</td>
                  <td>{{ formatDate(item.updated_at) }}</td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-info" @click="openStatusModal(item)">
                        <CIcon icon="cil-sync" /> Update Status
                      </button>
                      <template v-if="isApprover && item.status_approval === 'Pending' && ['DP Dibayar','Lunas'].includes(item.status_termin)">
                        <button class="btn btn-sm btn-success" @click="approveTermin(item)">
                          <CIcon icon="cil-check-circle" /> Approve
                        </button>
                        <button class="btn btn-sm btn-danger" @click="rejectTermin(item)">
                          <CIcon icon="cil-x-circle" /> Reject
                        </button>
                      </template>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CCardBody>
      </CCard>
<CCard v-if="selectedProject && selectedInvoice" class="mt-3">
  <CCardHeader>
    <strong>Ringkasan Termin</strong>
  </CCardHeader>
  <CCardBody>
    <CRow>
      <CCol md="6">
        <CTable>
          <CTableBody>
            <CTableRow v-if="taxLabel">
              <CTableDataCell>{{ taxLabel }}</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(taxValue) }}</CTableDataCell>
            </CTableRow>
            <CTableRow v-if="selectedInvoiceObj && selectedInvoiceObj.pph_final_amount">
  <CTableDataCell>PPH Final</CTableDataCell>
  <CTableDataCell class="text-end">{{ formatCurrency(selectedInvoiceObj.pph_final_amount) }}</CTableDataCell>
</CTableRow>
            <CTableRow v-if="ppnLabel">
              <CTableDataCell>{{ ppnLabel }}</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(ppnValue) }}</CTableDataCell>
            </CTableRow>
            <CTableRow v-if="!taxLabel && !ppnLabel">
              <CTableDataCell>Tanpa Pajak</CTableDataCell>
              <CTableDataCell class="text-end">0</CTableDataCell>
            </CTableRow>

          </CTableBody>
        </CTable>
      </CCol>
      <CCol md="6">
        <CTable>
          <CTableBody>
            <CTableRow>
              <CTableDataCell>Total Belanja (Invoice)</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalInvoiceAmount) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total Nilai Termin</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalTermin) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total DP</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalDP) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total Pelunasan</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalPelunasan) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total Dibayar</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalPaid) }}</CTableDataCell>
            </CTableRow>
            <CTableRow class="fw-bold">
              <CTableDataCell>Sisa Belum Dibayar</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalRemaining) }}</CTableDataCell>
            </CTableRow>
            <CTableRow class="fw-bold">
              <CTableDataCell>Nilai Akhir Setelah Pajak</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(finalAmount) }}</CTableDataCell>
            </CTableRow>
          </CTableBody>
        </CTable>
      </CCol>
    </CRow>
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
                          :value="String(project.id)">
                    {{ project.nama_proyek }}
                  </option>
                </optgroup>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="nama_termin">Nama Termin</CFormLabel>
              <CFormInput v-model="form.nama_termin" id="nama_termin" required />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="jenis_termin">Jenis Termin</CFormLabel>
              <CFormSelect v-model="form.jenis_termin" id="jenis_termin" required>
                <option value="Termin Bertahap">Termin Bertahap</option>
                <option value="DP">DP</option>
                <option value="Pelunasan">Pelunasan</option>
              </CFormSelect>
            </CCol>
            <CCol md="6">
              <CFormLabel for="termin_ke">Termin Ke</CFormLabel>
              <CFormInput type="number" v-model="form.termin_ke" id="termin_ke" min="1" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="nilai_termin">Nilai Termin</CFormLabel>
              <div class="input-group align-items-center">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="form.displayNilaiTermin"
                  @input="handleNilaiTerminInput"
                  id="nilai_termin"
                  required
                  :readonly="!manualNilaiTermin"
                />
                <CFormCheck v-model="manualNilaiTermin" class="ms-2" label="Input manual" />
              </div>
              <small class="text-muted">
                Maksimal nilai termin:  {{ formatCurrency(modalTotalInvoiceAmount) }}
              </small>
            </CCol>
            <CCol md="6">
              <CFormLabel for="dp_percentage">Persentase DP (%)</CFormLabel>
              <CFormInput type="number" v-model.number="form.persentase_dp" id="dp_percentage" required @input="calculateValues" min="0" max="100" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="nilai_dp">Nilai DP</CFormLabel>
              <div class="input-group align-items-center">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="form.displayNilaiDP"
                  @input="handleNilaiDPInput"
                  id="nilai_dp"
                  :readonly="!manualNilaiDP"
                />
                <CFormCheck v-model="manualNilaiDP" class="ms-2" label="Input manual" />
              </div>
            </CCol>
            <CCol md="6">
              <CFormLabel for="remaining_dp">Sisa DP (Remaining DP)</CFormLabel>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  readonly
                  :value="formatCurrency(sisaDP)"
                />
              </div>
            </CCol>
            <CCol md="6">
              <CFormLabel for="nilai_pelunasan">Nilai Pelunasan</CFormLabel>
              <div class="input-group">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="form.displayNilaiPelunasan"
                  @input="handleNilaiPelunasanInput"
                  id="nilai_pelunasan"
                  readonly
                />
              </div>
            </CCol>
          <CCol md="6">
            <CFormLabel for="remaining_pelunasan">Sisa Pelunasan</CFormLabel>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <CFormInput
                type="text"
                :value="formatCurrency(form.remaining_pelunasan !== undefined ? form.remaining_pelunasan : 0)"
                id="remaining_pelunasan"
                readonly
              />
            </div>
          </CCol>
          <CCol md="6">
            <CFormLabel for="remaining_total">Sisa Termin Belum Dibayar</CFormLabel>
            <div class="input-group">
              <span class="input-group-text">Rp</span>
              <CFormInput
                type="text"
                :value="formatCurrency(sisaTerminBelumDibayar)"
                id="remaining_total"
                readonly
              />
            </div>
          </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="tanggal_dp">Tanggal DP</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_dp" id="tanggal_dp" />
            </CCol>
            <CCol md="6">
              <CFormLabel for="tanggal_pelunasan">Tanggal Deadline Pembayaran</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_pelunasan" id="tanggal_pelunasan" />
              <small class="text-muted">Tanggal jatuh tempo pembayaran termin ini.</small>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="status_termin">Status Termin</CFormLabel>
              <CFormSelect v-model="form.status_termin" id="status_termin" required>
                <option value="Belum Dibayar">Belum Dibayar</option>
                <option value="DP Dibayar">DP Dibayar</option>
                <option value="Lunas">Lunas</option>
              </CFormSelect>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="keterangan">Keterangan</CFormLabel>
              <CFormTextarea v-model="form.keterangan" id="keterangan" rows="3" />
            </CCol>
          </CRow>
          <!-- Field Bukti Pembayaran (opsional, hanya saat create/edit termin) -->
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="bukti_pembayaran">Bukti Pembayaran (Opsional)</CFormLabel>
              <CFormInput type="file" id="bukti_pembayaran" accept="image/*,.pdf" @change="handleBuktiPembayaranForm" />
              <small class="text-muted">Format: JPEG, PNG, PDF (Max 2MB). Tidak wajib diisi.</small>
              <div v-if="form.bukti_pembayaran_url" class="mt-2">
                <a :href="form.bukti_pembayaran_url" target="_blank">Lihat Bukti Pembayaran</a>
              </div>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="invoice_id">Invoice</CFormLabel>
              <CFormSelect v-model="form.invoice_id" id="invoice_id" required :disabled="modalMode === 'edit'">
                <option value="">Pilih Invoice</option>
                <option v-for="inv in invoiceOptions" :key="inv.value" :value="inv.value">
                  {{ inv.label }}
                </option>
              </CFormSelect>
              <small v-if="modalMode === 'edit'" class="text-muted">Invoice terkunci. Hanya bisa diganti jika invoice dihapus/hilang.</small>
            </CCol>
          </CRow>
          <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
        </CForm>
      </CModalBody>
    </CModal>

    <!-- Status Update Modal -->
    <CModal :visible="showStatusModal" @close="closeStatusModal" title="Update Status Termin">
      <CModalBody>
        <CForm @submit.prevent="handleStatusSubmit">

          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel>Status Termin</CFormLabel>
              <CFormSelect v-model="statusForm.status_termin" required>
                <option value="Belum Dibayar">Belum Dibayar</option>
                <option value="DP Dibayar">DP Dibayar</option>
                <option value="Lunas">Lunas</option>
              </CFormSelect>
            </CCol>
          </CRow>

          <CRow class="mb-3" v-if="statusForm.status_termin === 'DP Dibayar'">
            <CCol md="12">
              <CFormLabel>Tanggal DP Dibayar</CFormLabel>
              <CFormInput type="date" v-model="statusForm.tanggal_dp_dibayar" required />
            </CCol>
          </CRow>

          <CRow class="mb-3" v-if="statusForm.status_termin === 'Lunas'">
            <CCol md="12">
              <CFormLabel>Tanggal Pelunasan Dibayar</CFormLabel>
              <CFormInput type="date" v-model="statusForm.tanggal_pelunasan_dibayar" required />
            </CCol>
          </CRow>

          <CRow class="mb-3" v-if="['DP Dibayar', 'Lunas'].includes(statusForm.status_termin)">
            <CCol md="12">
              <CFormLabel>Bukti Pembayaran</CFormLabel>
              <CFormInput type="file" @change="handleBuktiPembayaran" accept="image/*,.pdf" :required="!statusForm.bukti_pembayaran_url" />
              <small class="text-muted">Format: JPEG, PNG, PDF (Max 2MB)</small>
              <div v-if="statusForm.bukti_pembayaran_url" class="mt-2">
                <a :href="statusForm.bukti_pembayaran_url" target="_blank">Lihat Bukti Pembayaran</a>
              </div>
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel>Keterangan</CFormLabel>
              <CFormTextarea v-model="statusForm.keterangan" rows="3" />
            </CCol>
          </CRow>

          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel>Status Approval</CFormLabel>
              <CFormSelect v-model="statusForm.status_approval" required
                :disabled="statusForm.status_termin === 'Belum Dibayar'">
                <option value="Pending">Pending</option>
                <option value="Approved">Disetujui</option>
                <option value="Rejected">Ditolak</option>
              </CFormSelect>
              <small v-if="statusForm.status_termin === 'Belum Dibayar'" class="text-muted">Approval hanya bisa dilakukan jika termin sudah DP Dibayar atau Lunas.</small>
            </CCol>
            <CCol md="6">
              <CFormLabel>Disetujui/Ditolak Oleh</CFormLabel>
              <CFormInput :value="statusForm.approved_by_name" readonly />
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel>Waktu Disetujui/Ditolak</CFormLabel>
              <CFormInput :value="statusForm.approved_at" readonly />
            </CCol>
          </CRow>

          <div class="d-flex justify-content-end gap-2">
            <CButton color="secondary" @click="closeStatusModal">Batal</CButton>
            <CButton type="submit" color="primary">Simpan</CButton>
            <CButton v-if="isApprover && statusForm.status_approval === 'Pending' && ['DP Dibayar','Lunas'].includes(statusForm.status_termin)" color="success" @click.prevent="confirmApproveStatus">Approve</CButton>
            <CButton v-if="isApprover && statusForm.status_approval === 'Pending' && ['DP Dibayar','Lunas'].includes(statusForm.status_termin)" color="danger" @click.prevent="confirmRejectStatus">Reject</CButton>
          </div>
        </CForm>
      </CModalBody>
    </CModal>
  </CRow>
</template>

<script setup>
import { ref, onMounted, nextTick, watch, computed, onUnmounted } from "vue";
import axios from "axios";
import $ from "jquery";
import Swal from "sweetalert2";
import "datatables.net-dt/css/dataTables.dataTables.min.css";
import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
import "datatables.net-responsive-dt";

// State declarations
const form = ref({
  proyek_id: '',
  invoice_id: '',
  nama_termin: '',
  jenis_termin: 'Termin Bertahap',
  termin_ke: 1,
  nilai_termin: 0,
  persentase_dp: 0,
  nilai_dp: 0,
  nilai_pelunasan: 0,
  tanggal_dp: '',
  tanggal_pelunasan: '',
  tanggal_dp_dibayar: '',
  tanggal_pelunasan_dibayar: '',
  status_termin: 'Belum Dibayar',
  keterangan: '',
  bukti_pembayaran: null,
  dibayar_oleh: '',
  expense_id: null,
  remaining_dp: 0,
  remaining_pelunasan: 0,
  remaining_total: 0
});

const statusForm = ref({
  status_termin: '',
  tanggal_dp_dibayar: '',
  tanggal_pelunasan_dibayar: '',
  keterangan: '',
  bukti_pembayaran: null,
  bukti_pembayaran_url: '',
  status_approval: '',
  approved_by_name: '',
  approved_at: ''
});

const projects = ref([]);
const projectOptions = ref([]);
const invoices = ref([]);
const invoiceOptions = ref([]);
const purchases = ref([]);
const termins = ref([]);
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const showStatusModal = ref(false);
const modalTitle = ref("Tambah Termin");
const modalButtonText = ref("Simpan");
const modalMode = ref("tambah");
const editingId = ref(null);
const updatingStatusId = ref(null);
const selectedProject = ref("");
const selectedInvoice = ref("");

// Manual mode state
const manualNilaiTermin = ref(false);
const manualNilaiDP = ref(false);

const totalDpSudahDibayar = ref(0);


// Default summary object to avoid undefined error
const defaultSummary = {
  total_termin: 0,
  total_dp: 0,
  total_pelunasan: 0,
  total_pembelian_material: 0,
  total_dp_sudah_dibayar: 0,
  sisa_belum_dibayar: 0
};
const summary = ref({ ...defaultSummary });

// Computed Properties
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

const totalTermin = computed(() => {
  return termins.value.reduce((sum, termin) => sum + (Number(termin.nilai_termin) || 0), 0);
});

const totalDP = computed(() => {
  return termins.value.reduce((sum, termin) => sum + (Number(termin.nilai_dp) || 0), 0);
});

const totalPelunasan = computed(() => {
  return termins.value.reduce((sum, termin) => sum + (Number(termin.nilai_pelunasan) || 0), 0);
});

const totalPurchases = computed(() => {
  return purchases.value.reduce((sum, purchase) => sum + Number(purchase.total_harga), 0);
});

const totalKeseluruhan = computed(() => {
  return totalPurchases.value;
});

const selectedInvoiceObj = computed(() => {
  return invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
});

const totalInvoiceAmount = computed(() => {
  const inv = invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
  return inv ? Number(inv.total_amount) : 0;
});

const totalInvoicePaid = computed(() => {
  const inv = invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
  return inv ? Number(inv.amount_paid) : 0;
});

const totalInvoiceUnpaid = computed(() => {
  const inv = invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
  return inv ? Number(inv.total_amount) - Number(inv.amount_paid) : 0;
});

const modalInvoiceObj = computed(() => {
  return invoices.value.find(inv => `${inv.id}` === `${form.value.invoice_id}`);
});

const modalTotalInvoiceAmount = computed(() => {
  return modalInvoiceObj.value && modalInvoiceObj.value.total_amount ? Number(modalInvoiceObj.value.total_amount) : 0;
});

const totalPaid = computed(() => {
  return termins.value.reduce((sum, termin) => {
    if (termin.status_termin === 'DP Dibayar') {
      return sum + (Number(termin.nilai_dp) || 0);
    } else if (termin.status_termin === 'Lunas') {
      return sum + (Number(termin.nilai_termin) || 0);
    }
    return sum;
  }, 0);
});

const totalRemaining = computed(() => {
  return totalTermin.value - (totalPurchases.value + totalDpSudahDibayar.value);
});

const taxLabel = computed(() => {
  const inv = selectedInvoiceObj.value;
  if (!inv) return null;
  if (inv.use_pph_non_final) return 'PPH Non Final';
  if (inv.use_pph_final) return 'PPH Final';
  return null;
});
const taxValue = computed(() => {
  const inv = selectedInvoiceObj.value;
  if (!inv) return 0;
  if (inv.use_pph_non_final) return inv.pph_non_final_amount || 0;
  if (inv.use_pph_final) return inv.pph_final_amount || 0;
  return 0;
});
const ppnLabel = computed(() => {
  const inv = selectedInvoiceObj.value;
  if (inv && inv.use_ppn) return 'PPN (11%)';
  return null;
});
const ppnValue = computed(() => {
  const inv = selectedInvoiceObj.value;
  if (inv && inv.use_ppn) return inv.ppn_amount || 0;
  return 0;
});
const finalAmount = computed(() => {
  const inv = selectedInvoiceObj.value;
  if (!inv) return 0;
  // Nilai akhir = total_amount + ppn - pph
  let total = Number(inv.total_amount) || 0;
  if (inv.use_ppn) total += Number(inv.ppn_amount) || 0;
  if (inv.use_pph_non_final) total -= Number(inv.pph_non_final_amount) || 0;
  if (inv.use_pph_final) total -= Number(inv.pph_final_amount) || 0;
  return total;
});

// Tambahkan computed properties untuk menghitung nilai DP, pelunasan, dan sisa pembayaran secara dinamis
const nilaiDP = computed(() => {
  return (form.value.persentase_dp / 100) * form.value.nilai_termin;
});

const nilaiPelunasan = computed(() => {
  return form.value.nilai_termin - nilaiDP.value;
});

const sisaDP = computed(() => {
  return form.value.status_termin === 'Belum Dibayar' ? nilaiDP.value : 0;
});

const sisaPelunasan = computed(() => {
  return form.value.status_termin === 'Belum Dibayar' ? nilaiPelunasan.value : 0;
});

const sisaTerminBelumDibayar = computed(() => {
  return sisaDP.value + sisaPelunasan.value;
});

// Helper Methods
const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR'
  }).format(value);
};

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toISOString().split('T')[0];
};

// Methods
const handleNilaiTerminInput = (event) => {
  if (!manualNilaiTermin.value) return;
  const value = event.target.value.replace(/[^\d]/g, '');
  const newValue = Number(value) || 0;
  if (newValue > modalTotalInvoiceAmount.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Peringatan',
      text: 'Nilai termin tidak boleh melebihi total invoice'
    });
    return;
  }
  form.value.nilai_termin = newValue;
  form.value.displayNilaiTermin = formatCurrency(form.value.nilai_termin);
  if (!manualNilaiDP.value) calculateValues();
};

const handleNilaiDPInput = (event) => {
  if (!manualNilaiDP.value) return;
  const value = event.target.value.replace(/[^\d]/g, '');
  const newValue = Number(value) || 0;
  if (newValue > form.value.nilai_termin) {
    Swal.fire({
      icon: 'warning',
      title: 'Peringatan',
      text: 'Nilai DP tidak boleh melebihi nilai termin'
    });
    return;
  }
  form.value.nilai_dp = newValue;
  form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
  if (form.value.nilai_termin > 0) {
    form.value.persentase_dp = Math.round((form.value.nilai_dp / form.value.nilai_termin) * 100);
    if (!manualNilaiTermin.value) {
      form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
      form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
    }
  }
};

const handleNilaiPelunasanInput = (event) => {
  const value = event.target.value.replace(/[^\d]/g, '');
  const newValue = Number(value) || 0;
  if (newValue > form.value.nilai_termin) {
    Swal.fire({
      icon: 'warning',
      title: 'Peringatan',
      text: 'Nilai pelunasan tidak boleh melebihi nilai termin'
    });
    return;
  }
  form.value.nilai_pelunasan = newValue;
  form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  if (form.value.nilai_termin > 0 && !manualNilaiDP.value) {
    form.value.nilai_dp = form.value.nilai_termin - form.value.nilai_pelunasan;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
    form.value.persentase_dp = Math.round((form.value.nilai_dp / form.value.nilai_termin) * 100);
  }
};

const calculateValues = () => {
  if (!manualNilaiDP.value && form.value.persentase_dp) {
    form.value.nilai_dp = form.value.nilai_termin * (form.value.persentase_dp / 100);
    form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
    form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
    form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
  }
};

watch(() => form.value.persentase_dp, (val) => {
  if (!manualNilaiDP.value) calculateValues();
});

watch(() => form.value.nilai_termin, (val) => {
  if (!manualNilaiDP.value) calculateValues();
});

// API Calls
const fetchProjects = async () => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get("/api/proyeks", {
      headers: { Authorization: `Bearer ${token}` }
    });
    projects.value = response.data.data || [];
    projectOptions.value = projects.value.map(proyek => ({
      value: proyek.id,
      label: (proyek.nama_customer || '-') + ' - ' + (proyek.nama_proyek || '-')
    }));
  } catch (err) {
    projects.value = [];
    projectOptions.value = [];
  }
};

const fetchTermins = async () => {
  try {
    const token = sessionStorage.getItem("token");
    let url = "/api/termins";
    const params = {};

    if (selectedProject.value) {
      params.proyek_id = selectedProject.value;
    }
    if (selectedInvoice.value) {
      params.invoice_id = selectedInvoice.value;
    }

    const response = await axios.get(url, {
      params,
      headers: { Authorization: `Bearer ${token}` }
    });

    termins.value = response.data.data;
  } catch (err) {
    console.error("Error fetching termins:", err);
    Swal.fire({
      icon: "error",
      title: "Error",
      text: "Gagal mengambil data termin"
    });
  }
};

const fetchInvoices = async (proyekId) => {
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get(`/api/proyeks/${proyekId}/invoices`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    let invoiceArr = Array.isArray(response.data)
      ? response.data
      : (response.data.data || []);
    invoices.value = invoiceArr;
    invoiceOptions.value = invoiceArr.map(inv => ({
      value: inv.id,
      label: inv.invoice_number
    }));
  } catch (err) {
    invoices.value = [];
    invoiceOptions.value = [];
  }
};

const fetchPurchases = async (proyekId, invoiceId) => {
  if (!proyekId || !invoiceId) {
    purchases.value = [];
    return;
  }

  try {
    const token = sessionStorage.getItem("token");
    const invoiceResponse = await axios.get(`/api/invoices/${invoiceId}`, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });

    if (!invoiceResponse.data) {
      throw new Error("Data invoice tidak ditemukan");
    }

    const response = await axios.get("/api/purchasematerials", {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      params: { proyek_id: proyekId }
    });

    let allPurchases = [];
    if (response.data && response.data.status === 'success' && Array.isArray(response.data.data)) {
      allPurchases = response.data.data;
    }

    purchases.value = allPurchases.filter(p => `${p.invoice_id}` === `${invoiceId}`);

    const invoiceTotal = Number(invoiceResponse.data.total_amount) || 0;
    form.value.nilai_termin = invoiceTotal;
    form.value.displayNilaiTermin = formatCurrency(invoiceTotal);

    if (form.value.persentase_dp) {
      calculateValues();
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat data pembelian';
    purchases.value = [];
    Swal.fire({
      icon: "error",
      title: "Error",
      text: error.value
    });
  }
};

// Fungsi utilitas untuk menghitung total DP, pelunasan, dan termin yang dibayar
const hitungTerminDibayar = (list, excludeId = null) => {
  const filtered = excludeId ? list.filter(t => t.id !== excludeId) : list;
  let dp = 0, pelunasan = 0, total = 0;
  filtered.forEach(t => {
    const nilaiDP = Number(t.nilai_dp) || 0;
    const nilaiPelunasan = Number(t.nilai_pelunasan) || 0;
    if (t.status_termin === 'DP Dibayar') {
      dp += nilaiDP;
      total += nilaiDP;
    } else if (t.status_termin === 'Lunas') {
      dp += nilaiDP;
      pelunasan += nilaiPelunasan;
      total += nilaiDP + nilaiPelunasan;
    }
  });
  return { dp, pelunasan, total };
};

// Event Handlers
const filterByProject = async () => {
  await fetchInvoices(String(selectedProject.value));
  purchases.value = [];
  selectedInvoice.value = "";
  termins.value = [];
  nextTick(() => {
  });
};

const openModal = async (mode, termin = null) => {
  // Defensive: hanya boleh buka modal tambah jika syarat terpenuhi
  if (mode === 'tambah' && (!selectedProject.value || !selectedInvoice.value)) {
    Swal.fire({ icon: 'warning', title: 'Pilih proyek dan invoice terlebih dahulu!' });
    return;
  }
  modalMode.value = mode;
  if (mode === "edit" && termin) {
    // Hitung sisa DP, pelunasan, total (kecuali termin yang sedang diedit)
    const invoiceId = termin.invoice_id || selectedInvoice.value;
    const filteredTermins = termins.value.filter(t => `${t.invoice_id}` === `${invoiceId}` && t.id !== termin.id);
    const totalDPdibayar = filteredTermins.reduce((sum, t) => {
      if (t.status_termin === 'DP Dibayar') {
        return sum + (Number(t.nilai_dp) || 0);
      } else if (t.status_termin === 'Lunas') {
        return sum + (Number(t.nilai_dp) || 0);
      }
      return sum;
    }, 0);
    const totalPelunasanDibayar = filteredTermins.reduce((sum, t) => sum + (t.status_termin === 'Lunas' ? Number(t.nilai_pelunasan) || 0 : 0), 0);
    const totalTerminDibayar = filteredTermins.reduce((sum, t) => {
      if (t.status_termin === 'DP Dibayar') {
        return sum + (Number(t.nilai_dp) || 0);
      } else if (t.status_termin === 'Lunas') {
        return sum + (Number(t.nilai_dp) || 0) + (Number(t.nilai_pelunasan) || 0);
      }
      return sum;
    }, 0);
    const invoice = invoices.value.find(inv => `${inv.id}` === `${invoiceId}`);
    // Logic: jika ada pajak (ppn/pph), ambil dari finalAmount, jika tidak ada pajak ambil dari total_amount
    let nilaiAkhirSetelahPajak = 0;
    if (invoice) {
      const usePpn = invoice.use_ppn;
      const usePphNonFinal = invoice.use_pph_non_final;
      const usePphFinal = invoice.use_pph_final;
      if (usePpn || usePphNonFinal || usePphFinal) {
        // Hitung manual sesuai logic finalAmount
        let total = Number(invoice.total_amount) || 0;
        if (usePpn) total += Number(invoice?.ppn_amount ?? 0);
        if (usePphNonFinal) total -= Number(invoice.pph_non_final_amount) || 0;
        if (usePphFinal) total -= Number(invoice.pph_final_amount) || 0;
        nilaiAkhirSetelahPajak = total;
      } else {
        nilaiAkhirSetelahPajak = Number(invoice.total_amount) || 0;
      }
    }
    form.value = {
      proyek_id: termin.proyek_id || selectedProject.value,
      invoice_id: `${invoiceId || ""}`,
      nama_termin: termin.nama_termin,
      nilai_termin: nilaiAkhirSetelahPajak,
      persentase_dp: termin.persentase_dp,
      nilai_dp: termin.nilai_dp,
      nilai_pelunasan: termin.nilai_pelunasan,
      tanggal_dp: termin.tanggal_dp ? new Date(termin.tanggal_dp).toISOString().split('T')[0] : "",
      tanggal_pelunasan: termin.tanggal_pelunasan ? new Date(termin.tanggal_pelunasan).toISOString().split('T')[0] : "",
      tanggal_dp_dibayar: termin.tanggal_dp_dibayar ? new Date(termin.tanggal_dp_dibayar).toISOString().split('T')[0] : "",
      tanggal_pelunasan_dibayar: termin.tanggal_pelunasan_dibayar ? new Date(termin.tanggal_pelunasan_dibayar).toISOString().split('T')[0] : "",
      status_termin: termin.status_termin,
      keterangan: termin.keterangan || "",
      displayNilaiTermin: formatCurrency(nilaiAkhirSetelahPajak),
      displayNilaiDP: formatCurrency(termin.nilai_dp),
      displayNilaiPelunasan: formatCurrency(termin.nilai_pelunasan),
      bukti_pembayaran: null,
      bukti_pembayaran_url: termin.bukti_pembayaran_url || null,
      remaining_dp: Math.max(totalDPdibayar - (Number(termin.nilai_dp) || 0), 0),
      remaining_pelunasan: Math.max(totalPelunasanDibayar - (Number(termin.nilai_pelunasan) || 0), 0),
      remaining_total: Math.max(nilaiAkhirSetelahPajak - totalTerminDibayar, 0)
    };
    editingId.value = termin.id;
    modalTitle.value = "Edit Termin";
    modalButtonText.value = "Update";
  } else {
    const selectedInvoiceObj = invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
    const today = new Date().toISOString().split('T')[0];
    // Hitung sisa DP, pelunasan, total dari termin yang sudah dibayar pada invoice ini
    const invoiceId = selectedInvoice.value;
    const { dp: totalDPdibayar, pelunasan: totalPelunasanDibayar, total: totalTerminDibayar } =
      hitungTerminDibayar(termins.value.filter(t => `${t.invoice_id}` === `${invoiceId}`));

    // Logic: jika ada pajak (ppn/pph), ambil dari finalAmount, jika tidak ada pajak ambil dari total_amount
    let nilaiAkhirSetelahPajak = 0;
    if (selectedInvoiceObj) {
      const usePpn = selectedInvoiceObj.use_ppn;
      const usePphNonFinal = selectedInvoiceObj.use_pph_non_final;
      const usePphFinal = selectedInvoiceObj.use_pph_final;
      if (usePpn || usePphNonFinal || usePphFinal) {
        let total = Number(selectedInvoiceObj.total_amount) || 0;
        if (usePpn) total += Number(selectedInvoiceObj.ppn_amount) || 0;
        if (usePphNonFinal) total -= Number(selectedInvoiceObj.pph_non_final_amount) || 0;
        if (usePphFinal) total -= Number(selectedInvoiceObj.pph_final_amount) || 0;
        nilaiAkhirSetelahPajak = total;
      } else {
        nilaiAkhirSetelahPajak = Number(selectedInvoiceObj.total_amount) || 0;
      }
    }
    form.value = {
      proyek_id: selectedProject.value,
      invoice_id: `${selectedInvoice.value || ""}`,
      nama_termin: "",
      nilai_termin: nilaiAkhirSetelahPajak,
      persentase_dp: 0,
      nilai_dp: 0,
      nilai_pelunasan: 0,
      tanggal_dp: today,
      tanggal_pelunasan: "",
      tanggal_dp_dibayar: "",
      tanggal_pelunasan_dibayar: "",
      status_termin: "Belum Dibayar",
      keterangan: "",
      displayNilaiTermin: formatCurrency(nilaiAkhirSetelahPajak),
      displayNilaiDP: formatCurrency(0),
      displayNilaiPelunasan: formatCurrency(0),
      bukti_pembayaran: null,
      bukti_pembayaran_url: null,
      remaining_dp: 0,
      remaining_pelunasan: 0,
      remaining_total: Math.max(nilaiAkhirSetelahPajak - totalTerminDibayar, 0)
    };
    editingId.value = null;
    modalTitle.value = "Tambah Termin";
    modalButtonText.value = "Simpan";
  }
  showModal.value = true;
};

const openStatusModal = (termin) => {
  statusForm.value = {
    status_termin: termin.status_termin,
    tanggal_dp_dibayar: termin.tanggal_dp_dibayar ? new Date(termin.tanggal_dp_dibayar).toISOString().split('T')[0] : '',
    tanggal_pelunasan_dibayar: termin.tanggal_pelunasan_dibayar ? new Date(termin.tanggal_pelunasan_dibayar).toISOString().split('T')[0] : '',
    keterangan: termin.keterangan || '',
    bukti_pembayaran: null,
    bukti_pembayaran_url: termin.bukti_pembayaran_url || '',
    status_approval: termin.status_approval || '',
    approved_by_name: termin.approved_by_name || '',
    approved_at: termin.approved_at || ''
  };
  updatingStatusId.value = termin.id;
  showStatusModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
};

const closeStatusModal = () => {
  showStatusModal.value = false;
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = "";

  try {
    // Basic validation
    if (!form.value.proyek_id) {
      throw new Error("Proyek wajib dipilih!");
    }
    if (!form.value.invoice_id) {
      throw new Error("Invoice wajib dipilih!");
    }

    if (!form.value.nama_termin?.trim()) {
      throw new Error("Nama termin wajib diisi!");
    }

    if (!form.value.nilai_termin || form.value.nilai_termin <= 0) {
      throw new Error("Nilai termin harus lebih dari 0!");
    }

    if (!form.value.persentase_dp || form.value.persentase_dp < 0 || form.value.persentase_dp > 100) {
      throw new Error("Persentase DP harus antara 0-100%!");
    }

    // Recalculate values
    calculateValues();

    // Ensure total DP + Pelunasan = Nilai Termin
    const total = form.value.nilai_dp + form.value.nilai_pelunasan;
    if (Math.abs(total - form.value.nilai_termin) > 0.01) {
      throw new Error("Total DP dan Pelunasan harus sama dengan Nilai Termin");
    }

    const token = sessionStorage.getItem("token");
    if (!token) {
      throw new Error("Token tidak ditemukan. Silakan login kembali.");
    }

    // Format data for API
    const formattedData = {
      proyek_id: Number(form.value.proyek_id),
      invoice_id: Number(form.value.invoice_id),
      nama_termin: form.value.nama_termin.trim(),
      jenis_termin: form.value.jenis_termin,
      termin_ke: form.value.termin_ke ? Number(form.value.termin_ke) : null,
      nilai_termin: Number(form.value.nilai_termin),
      persentase_dp: Number(form.value.persentase_dp),
      nilai_dp: Number(form.value.nilai_dp),
      nilai_pelunasan: Number(form.value.nilai_pelunasan),
      tanggal_dp: form.value.tanggal_dp || null,
      tanggal_pelunasan: form.value.tanggal_pelunasan || null,
      tanggal_dp_dibayar: form.value.tanggal_dp_dibayar || null,
      tanggal_pelunasan_dibayar: form.value.tanggal_pelunasan_dibayar || null,
      status_termin: form.value.status_termin,
      keterangan: form.value.keterangan?.trim() || null
    };

    console.log('Sending data to API:', formattedData);

    let response;
    const headers = {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json'
    };

    if (form.value.bukti_pembayaran) {
      const formData = new FormData();
      Object.entries(formattedData).forEach(([key, value]) => {
        if (value !== null) {
          formData.append(key, value);
        }
      });
      formData.append('bukti_pembayaran', form.value.bukti_pembayaran);
      headers['Content-Type'] = 'multipart/form-data';

      if (modalMode.value === "edit") {
        response = await axios.post(`/api/termins/${editingId.value}?_method=PUT`, formData, { headers });
      } else {
        response = await axios.post("/api/termins", formData, { headers });
      }
    } else {
      if (modalMode.value === "edit") {
        response = await axios.put(`/api/termins/${editingId.value}`, formattedData, { headers });
      } else {
        response = await axios.post("/api/termins", formattedData, { headers });
      }
    }

    console.log('Response from API:', response.data);

    if (response.data.status === 'success') {
      Swal.fire({
        icon: "success",
        title: "Berhasil!",
        text: modalMode.value === "edit" ? "Data termin diperbarui." : "Data termin ditambahkan."
      });
      closeModal();
      await fetchTermins();
      window.dispatchEvent(new Event('termin-updated'));
    } else {
      throw new Error(response.data.message || "Terjadi kesalahan saat menyimpan data");
    }

  } catch (err) {
    console.error('Error in handleSubmit:', err);
    const errorMessage = err.response?.data?.message || err.message || "Terjadi kesalahan saat menyimpan data";
    const validationErrors = err.response?.data?.errors;

    console.error('Error details:', {
      message: errorMessage,
      validationErrors,
      response: err.response?.data,
      status: err.response?.status
    });

    if (validationErrors) {
      // Format validation errors into a readable message
      const errorList = Object.entries(validationErrors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n');

      Swal.fire({
        icon: "error",
        title: "Validasi Gagal",
        html: `<pre style="text-align: left">${errorList}</pre>`
      });
    } else {
      Swal.fire({
        icon: "error",
        title: "Error",
        text: errorMessage
      });
    }
  } finally {
    loading.value = false;
  }
};

const handleStatusSubmit = async () => {
  // Validasi dasar
  if (!statusForm.value.status_termin) {
    Swal.fire({ icon: 'error', title: 'Status termin wajib dipilih!' });
    return;
  }
  if (!statusForm.value.status_approval) {
    Swal.fire({ icon: 'error', title: 'Status approval wajib dipilih!' });
    return;
  }
  if ((['DP Dibayar', 'Lunas'].includes(statusForm.value.status_termin)) && !statusForm.value.bukti_pembayaran && !statusForm.value.bukti_pembayaran_url) {
    Swal.fire('Peringatan', 'Bukti pembayaran wajib diupload', 'warning');
    return;
  }
  const actionText = statusForm.value.status_approval === 'Approved' ? 'Menyetujui' : (statusForm.value.status_approval === 'Rejected' ? 'Menolak' : 'Menunda');
  const confirmResult = await Swal.fire({
    title: `Konfirmasi`,
    text: `Anda yakin ingin ${actionText.toLowerCase()} termin ini?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya',
    cancelButtonText: 'Batal'
  });
  if (!confirmResult.isConfirmed) return;

  try {
    const token = sessionStorage.getItem('token');
    const user = JSON.parse(sessionStorage.getItem('user') || '{}');
    const formData = new FormData();
    formData.append('status_termin', statusForm.value.status_termin);
    formData.append('status_approval', statusForm.value.status_approval);
    formData.append('approved_by', user.id);
    formData.append('approved_at', new Date().toISOString());
    formData.append('tanggal_dp_dibayar', statusForm.value.tanggal_dp_dibayar || '');
    formData.append('tanggal_pelunasan_dibayar', statusForm.value.tanggal_pelunasan_dibayar || '');
    formData.append('keterangan', statusForm.value.keterangan || '');
    if (statusForm.value.bukti_pembayaran) {
      formData.append('bukti_pembayaran', statusForm.value.bukti_pembayaran);
    }
    await axios.post(`/api/termins/${updatingStatusId.value}/update-status`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });
    Swal.fire('Berhasil', 'Status termin berhasil diperbarui', 'success');
    closeStatusModal();
    await fetchTermins();
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || 'Gagal memperbarui status termin', 'error');
  }
};

const handleBuktiPembayaran = (event) => {
  statusForm.value.bukti_pembayaran = event.target.files[0];
};

const user = JSON.parse(sessionStorage.getItem('user') || '{}');
const isApprover = computed(() => ['admin', 'superadmin'].includes(user.role));

const confirmApproveStatus = async () => {
  const result = await Swal.fire({
    title: 'Setujui Termin?',
    text: 'Apakah Anda yakin ingin menyetujui termin ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Setujui',
    cancelButtonText: 'Batal'
  });
  if (result.isConfirmed) {
    handleApproveStatus();
  }
};

const confirmRejectStatus = async () => {
  const result = await Swal.fire({
    title: 'Tolak Termin?',
    text: 'Apakah Anda yakin ingin menolak termin ini?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Tolak',
    cancelButtonText: 'Batal'
  });
  if (result.isConfirmed) {
    handleRejectStatus();
  }
};

// Watch for project selection changes
watch(selectedProject, async (newValue) => {
  if (newValue) {
    await fetchInvoices(newValue);
    termins.value = [];
  } else {
    invoices.value = [];
    termins.value = [];
  }
});

// Watch for invoice selection changes
watch(selectedInvoice, async (newValue) => {
  if (newValue && selectedProject.value) {
    await fetchTermins();
  } else {
    termins.value = [];
  }
});

// Setelah fetchTermins selesai, inisialisasi DataTable
let dataTableInstance = null;
watch(termins, async (newVal) => {
  await nextTick();
  if (dataTableInstance) {
    dataTableInstance.clear().destroy();
    dataTableInstance = null;
  }
  dataTableInstance = $('#terminTable').DataTable({
    destroy: true,
    responsive: true,
    autoWidth: false,
    ordering: true,
    pageLength: 10,
    language: {
    //   url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
    }
  });
});

onMounted(async () => {
  await fetchProjects();
  // DataTable akan diinisialisasi otomatis oleh watcher
});

onUnmounted(() => {
  if (dataTableInstance) {
    dataTableInstance.clear().destroy();
    dataTableInstance = null;
  }
});

const fields = [
  { key: 'nama_termin', label: 'Nama Termin' },
  { key: 'jenis_termin', label: 'Jenis Termin' },
  { key: 'termin_ke', label: 'Termin Ke' },
  { key: 'nilai_termin', label: 'Nilai Termin' },
  { key: 'nilai_dp', label: 'Nilai DP' },
  { key: 'nilai_pelunasan', label: 'Nilai Pelunasan' },
  { key: 'status_termin', label: 'Status' },
  { key: 'tanggal_dp', label: 'Tanggal DP' },
  { key: 'tanggal_pelunasan', label: 'Deadline Pembayaran' },
  { key: 'keterangan', label: 'Keterangan' }
];

const getStatusColor = (status) => {
  switch (status) {
    case 'Lunas':
      return 'success';
    case 'DP Dibayar':
      return 'info';
    case 'Belum Dibayar':
      return 'warning';
    default:
      return 'secondary';
  }
};

const getApprovalColor = (status) => {
  switch (status) {
    case 'Approved':
      return 'success';
    case 'Rejected':
      return 'danger';
    case 'Pending':
      return 'warning';
    default:
      return 'secondary';
  }
};

const handleProjectChange = async () => {
  selectedInvoice.value = '';
  await fetchInvoices(selectedProject.value);
};

const handleInvoiceChange = async () => {
  await fetchTermins();
};

const approveTermin = async (item) => {
  await updateTerminApproval(item, 'Approved');
};
const rejectTermin = async (item) => {
  await updateTerminApproval(item, 'Rejected');
};
const pendingTermin = async (item) => {
  await updateTerminApproval(item, 'Pending');
};

const updateTerminApproval = async (item, status) => {
  const token = sessionStorage.getItem('token');
  const user = JSON.parse(sessionStorage.getItem('user') || '{}');
  const formData = new FormData();
  // Kirim semua field yang diwajibkan backend
  formData.append('status_termin', item.status_termin);
  formData.append('status_approval', status);
  formData.append('approved_by', user.id);
  formData.append('approved_at', new Date().toISOString());
  // Optional: keterangan jika ada
  if (item.keterangan) formData.append('keterangan', item.keterangan);
  // Optional: tanggal_dp_dibayar dan tanggal_pelunasan_dibayar jika ada
  if (item.tanggal_dp_dibayar) formData.append('tanggal_dp_dibayar', item.tanggal_dp_dibayar);
  if (item.tanggal_pelunasan_dibayar) formData.append('tanggal_pelunasan_dibayar', item.tanggal_pelunasan_dibayar);
  // Optional: bukti_pembayaran jika ada
  if (item.bukti_pembayaran instanceof File) {
    formData.append('bukti_pembayaran', item.bukti_pembayaran);
  }
  try {
    await axios.post(`/api/termins/${item.id}/update-status`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    });
    Swal.fire('Berhasil', `Status termin diubah menjadi ${status}`, 'success');
    await fetchTermins();
  } catch (err) {
    // Tampilkan error validasi detail jika ada
    const validationErrors = err.response?.data?.errors;
    if (validationErrors) {
      const errorList = Object.entries(validationErrors)
        .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
        .join('\n');
      Swal.fire({
        icon: 'error',
        title: 'Validasi Gagal',
        html: `<pre style="text-align: left">${errorList}</pre>`
      });
    } else {
      Swal.fire('Error', err.response?.data?.message || 'Gagal memperbarui status termin', 'error');
    }
  }
};

const handleBuktiPembayaranForm = (event) => {
  form.value.bukti_pembayaran = event.target.files[0];
};

const exportToPDF = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/termins/export/pdf', {
      params: {
        proyek_id: selectedProject.value,
        invoice_id: selectedInvoice.value
      },
      headers: {
        Authorization: `Bearer ${token}`
      },
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'termin-report.pdf');
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal mengekspor ke PDF'
    });
  }
};

const exportToExcel = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/termins/export/excel', {
      params: {
        proyek_id: selectedProject.value,
        invoice_id: selectedInvoice.value
      },
      headers: {
        Authorization: `Bearer ${token}`
      },
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'termin-report.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Gagal mengekspor ke Excel'
    });
  }
};

const handleExcelImport = (event) => {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const data = e.target.result;
      // Handle the imported data
    };
    reader.readAsBinaryString(file);
  }
};

const fetchTotalDpSudahDibayar = async () => {
  if (!selectedProject.value || !selectedInvoice.value) {
    totalDpSudahDibayar.value = 0;
    return;
  }
  try {
    const token = sessionStorage.getItem("token");
    const response = await axios.get('/api/incomes/total-dp-paid', {
      params: {
        proyek_id: selectedProject.value,
        invoice_id: selectedInvoice.value
      },
      headers: { Authorization: `Bearer ${token}` }
    });
    totalDpSudahDibayar.value = Number(response.data.total_dp_paid) || 0;
  } catch (err) {
    totalDpSudahDibayar.value = 0;
  }
};

watch([selectedProject, selectedInvoice], async ([newProject, newInvoice]) => {
  if (newProject && newInvoice) {
    await fetchTotalDpSudahDibayar();
  }
});

const fetchSummary = async () => {
  if (!selectedProject.value || !selectedInvoice.value) {
    summary.value = {
      total_termin: 0,
      total_dp: 0,
      total_pelunasan: 0,
      total_pembelian_material: 0,
      total_dp_sudah_dibayar: 0,
      sisa_belum_dibayar: 0
    };
    return;
  }
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/termins/summary', {
      params: {
        proyek_id: selectedProject.value,
        invoice_id: selectedInvoice.value
      },
      headers: { Authorization: `Bearer ${token}` }
    });
      summary.value = {
        ...defaultSummary,
        ...(response.data || {})
      };
  } catch (err) {
    summary.value = {
      total_termin: 0,
      total_dp: 0,
      total_pelunasan: 0,
      total_pembelian_material: 0,
      total_dp_sudah_dibayar: 0,
      sisa_belum_dibayar: 0
    };
  }
};

watch([selectedProject, selectedInvoice], async ([newProject, newInvoice]) => {
  if (newProject && newInvoice) {
    await fetchSummary();
  }
});

// Expose necessary properties and methods to template
defineExpose({
  selectedProject,
  selectedInvoice,
  projects,
  invoices,
  termins,
  showModal,
  showStatusModal,
  modalTitle,
  modalButtonText,
  form,
  statusForm,
  editingId,
  updatingStatusId,
  modalMode,
  invoiceOptions,
  handleSubmit,
  handleStatusSubmit,
  openModal,
  openStatusModal,
  closeModal,
  closeStatusModal,
  handleBuktiPembayaranForm,
  exportToPDF,
  exportToExcel,
  handleExcelImport
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

table.dataTable tbody td {
  white-space: nowrap;
  padding: 8px;
}

.btn {
  margin: 0 2px;
}

.badge {
  padding: 0.5em 0.75em;
  font-size: 0.875em;
}

.table .btn-group .btn i {
  font-size: 1rem;
  vertical-align: middle;
}

.table .btn-group .btn {
  padding: 2px 6px;
  line-height: 1;
}
</style>

<style>
.dataTables_wrapper {
  margin: 1rem 0;
  padding: 0;
  width: 100%;
}

table.dataTable thead th {
  padding: 10px 8px;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  white-space: nowrap;
  vertical-align: middle;
  background-color: #fff;
}

table.dataTable tbody td {
  padding: 8px;
  vertical-align: middle;
  border-bottom: 1px solid #dee2e6;
  white-space: nowrap;
  background-color: #fff;
}

.DTFC_RightWrapper {
  right: 0 !important;
}

.DTFC_RightWrapper table.dataTable {
  margin-right: 0 !important;
}

.DTFC_RightWrapper .DTFC_RightHeadWrapper,
.DTFC_RightWrapper .DTFC_RightBodyWrapper {
  background-color: #fff;
}

.DTFC_RightWrapper thead th,
.DTFC_RightWrapper tbody td {
  border-left: 1px solid #dee2e6;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
  line-height: 1.2;
  border-radius: 0.2rem;
  min-width: 40px;
}

.badge {
  padding: 0.35em 0.65em;
  font-size: 0.75em;
  font-weight: 600;
  white-space: nowrap;
}

.dataTables_scroll {
  margin-bottom: 1rem;
}

.dataTables_scrollBody {
  min-height: 200px;
}

.dataTables_length,
.dataTables_filter {
  margin-bottom: 1rem;
}

.dataTables_length select {
  min-width: 80px;
}

.dataTables_paginate {
  margin-top: 1rem;
}

.text-end {
  text-align: right !important;
}

.text-center {
  text-align: center !important;
}

.align-middle {
  vertical-align: middle !important;
}

.d-flex.justify-content-center {
  flex-wrap: nowrap;
  gap: 4px;
}

.dataTables_scrollBody::-webkit-scrollbar {
  height: 8px;
  width: 8px;
}

.dataTables_scrollBody::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
  background: #555;
}

.DTFC_RightWrapper::before {
  content: '';
  position: absolute;
  top: 0;
  left: -6px;
  bottom: 0;
  width: 6px;
  pointer-events: none;
  background: linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,0.1));
}
</style>


