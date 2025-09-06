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

         <CButton
  color="primary"
  @click="openModal('tambah')"
  class="float-end"
  :disabled="!selectedProject || !selectedInvoice || totalPaid >= finalAmount"
>
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
  <CCol md="2">
    <CCard class="bg-primary text-white">
      <CCardBody>
        <h6>Total Nilai Termin</h6>
        <h3>{{ formatCurrency(finalAmount) }}</h3>
      </CCardBody>
    </CCard>
  </CCol>
  <CCol md="2">
    <CCard class="bg-success text-white">
      <CCardBody>
        <h6>Total DP Dibayar</h6>
        <h3>{{ formatCurrency(totalDpPaid) }}</h3>
      </CCardBody>
    </CCard>
  </CCol>
  <CCol md="2">
    <CCard class="bg-info text-white">
      <CCardBody>
        <h6>Total Pelunasan Dibayar</h6>
        <h3>{{ formatCurrency(totalPelunasanPaid) }}</h3>
      </CCardBody>
    </CCard>
  </CCol>
  <CCol md="2">
    <CCard class="bg-secondary text-white">
      <CCardBody>
        <h6>Total Dibayar</h6>
        <h3>{{ formatCurrency(totalPaid) }}</h3>
      </CCardBody>
    </CCard>
  </CCol>
  <CCol md="2">
    <CCard class="bg-warning text-white">
      <CCardBody>
        <h6>Sisa Belum Dibayar</h6>
        <h3>{{ formatCurrency(finalAmount - totalPaid) }}</h3>
      </CCardBody>
    </CCard>
  </CCol>
</CRow>

          <!-- DataTable -->
          <div style="width: 100%; overflow-x: auto;" v-if="selectedProject && selectedInvoice">
            <table id="terminTable" class="display" style="width:100%">
              <thead>
                <tr>
                    <th>No</th>
                  <th>Nama Termin</th>
                  <th>Jenis Termin</th>
                  <th>Termin Ke</th>
                  <th>Nilai Termin</th>
                  <th>Persentase DP</th>
                 <th>Progress Pekerjaan</th>
                  <th>Target Progress</th>
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
                  <th>Status Termin</th>
                  <th>Status Invoice</th>
                  <th>Status Expense</th>
                  <th>Status Income</th>
                </tr>
              </thead>
              <tbody></tbody>
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
            <CTableRow v-if="selectedInvoiceObj && selectedInvoiceObj.use_pph_non_final">
              <CTableDataCell>PPH Non Final</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(selectedInvoiceObj.pph_non_final_amount) }}</CTableDataCell>
            </CTableRow>
            <CTableRow v-if="selectedInvoiceObj && selectedInvoiceObj.use_pph_final">
              <CTableDataCell>PPH Final</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(selectedInvoiceObj.pph_final_amount) }}</CTableDataCell>
            </CTableRow>
            <CTableRow v-if="selectedInvoiceObj && selectedInvoiceObj.use_ppn">
              <CTableDataCell>PPN (11%)</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(selectedInvoiceObj.ppn_amount) }}</CTableDataCell>
            </CTableRow>
            <CTableRow class="fw-bold">
              <CTableDataCell>Nilai Akhir Setelah Pajak</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(finalAmount) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total Nilai Termin</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(finalAmount) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total DP Dibayar</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalDpPaid) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total Pelunasan Dibayar</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalPelunasanPaid) }}</CTableDataCell>
            </CTableRow>
            <CTableRow>
              <CTableDataCell>Total Dibayar</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(totalPaid) }}</CTableDataCell>
            </CTableRow>
            <CTableRow class="fw-bold">
              <CTableDataCell>Sisa Belum Dibayar</CTableDataCell>
              <CTableDataCell class="text-end">{{ formatCurrency(finalAmount - totalPaid) }}</CTableDataCell>
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
                  <option value="">Pilih Termin</option>
                <!-- <option value="Termin Bertahap">Termin Bertahap</option> -->
                <option value="DP">DP</option>
                <option value="Pelunasan">Pelunasan</option>
              </CFormSelect>
            </CCol>
            <CCol md="6">
              <CFormLabel for="target_progress">Target Progress (%)</CFormLabel>
              <CFormInput
                type="number"
                v-model.number="form.target_progress"
                id="target_progress"
                min="0"
                max="100"
                required
              />
              <small class="text-muted">
                Masukkan target progress fisik (%) agar termin ini bisa dicairkan.
              </small>
            </CCol>
            <CCol md="6">
              <CFormLabel for="termin_ke">Termin Ke</CFormLabel>
              <CFormInput type="number" v-model="form.termin_ke" id="termin_ke" min="1" />
            </CCol>
          </CRow>
          <CRow class="mb-3">
<CCol md="6">
 <CFormLabel for="nilai_termin">Total Nilai Termin (DP + Pelunasan)</CFormLabel>



  <!-- Jika BELUM ada pembayaran -->
  <div class="input-group align-items-center" v-if="!sudahAdaPembayaran">
    <span class="input-group-text">Rp</span>
    <CFormInput
      type="text"
       :value="formatCurrency(displayTerminValue)"
      id="nilai_termin"
      :readonly="modalMode === 'edit' && (form.total_paid > 0 || form.remaining_total < form.nilai_termin)"
      required
    />
    <!-- <CFormCheck
      v-if="modalMode === 'tambah'"
      v-model="manualNilaiTermin"
      class="ms-2"
      label="Input manual"
    /> -->
  </div>

  <!-- Jika SUDAH ada pembayaran -->
  <div class="input-group align-items-center" v-else>
    <span class="input-group-text">Rp</span>
    <CFormInput
      type="text"
      :value="formatCurrency(form.remaining_total || form.nilai_pelunasan || 0)"
      id="nilai_termin"
      readonly
    />
  </div>

  <small class="text-muted">
    <span v-if="!sudahAdaPembayaran">
      Masukkan nilai termin sesuai kontrak atau invoice.
    </span>
    <span v-else>
      Nilai termin sudah termasuk DP, sekarang menampilkan pelunasan yang tersisa.
    </span>
  </small>
</CCol>


            <CCol md="6">
              <CFormLabel for="dp_percentage">Persentase DP (%)</CFormLabel>
              <CFormInput type="number" v-model.number="form.persentase_dp" id="dp_percentage" required @input="calculateValues" min="0" max="100" />
            </CCol>
          </CRow>

       <!-- Ringkasan Pembayaran Termin -->
<CRow class="mb-3">
  <CCol md="6">
    <div class="alert alert-info mb-3" style="font-size: 0.95rem;">
      <strong class="d-block mb-1">📄 Ringkasan Invoice:</strong>
      <div><strong>Nomor Invoice:</strong> {{ modalInvoiceObj?.invoice_number || '-' }}</div>
      <div><strong>Total Setelah Pajak:</strong> {{ formatCurrency(modalTotalInvoiceAmount) }}</div>
      <div><strong>Total Termin Dibuat:</strong> {{ formatCurrency(totalNilaiTerminFiltered) }}</div>
      <div><strong>Total Sudah Dibayar:</strong> {{ formatCurrency(totalPaid) }}</div>
      <div>
        <strong class="text-danger">Sisa Termin Belum Dibayar:</strong>
        <span style="font-weight: 600;">{{ formatCurrency(modalTotalInvoiceAmount - totalPaid) }}</span>
      </div>
    </div>
  </CCol>

  <CCol md="6">
    <!-- Ringkasan Termin Saat Ini -->
    <div class="border rounded p-3 bg-white shadow-sm">
      <div style="font-weight:700; font-size:1.05rem;" class="mb-2">
        💰 Rencana Pembayaran Termin Ini
      </div>

      <div class="mb-1">
        <strong>Nilai Pelunasan ({{ 100 - Number(form.persentase_dp) }}%)</strong><br />
        Nilai: <span style="font-weight:600">{{ formatCurrency(form.nilai_pelunasan) }}</span>
      </div>
      <div class="mb-1">
        Sudah Dibayar: <span style="font-weight:600">{{ formatCurrency(form.total_pelunasan_paid || 0) }}</span>
      </div>
      <div>
        <span>Sisa Pelunasan: </span>
        <span
          :class="{ 'text-danger': (form.remaining_pelunasan || 0) < 0 }"
          style="font-weight:600"
        >
          {{ formatCurrency(form.remaining_pelunasan || 0) }}
        </span>
      </div>
    </div>
  </CCol>
</CRow>


          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="remaining_total">Sisa Termin Belum Dibayar</CFormLabel>
              <div class="input-group align-items-center">
                <span class="input-group-text">Rp</span>
                <CFormInput
                  type="text"
                  :value="formatCurrency(form.remaining_total || 0)"
                  id="remaining_total"
                  readonly
                />
              </div>
              <small class="text-muted">
                Sisa termin otomatis berkurang hanya oleh pembayaran yang sudah di-approve admin.
              </small>
            </CCol>
          </CRow>
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="tanggal_dp">Tanggal DP</CFormLabel>
              <CFormInput type="date" v-model="form.tanggal_dp" id="tanggal_dp" />
            </CCol>
            <CCol md="6">
              <CFormLabel for="tanggal_pelunasan">Tanggal Deadline Pembayaran</CFormLabel>
<CFormInput
  type="date"
  v-model="form.tanggal_pelunasan"
  id="tanggal_pelunasan"
  :class="{ 'is-invalid': isDateDisabled(form.tanggal_pelunasan) }"
/>
<small v-if="isDateDisabled(form.tanggal_pelunasan)" class="text-danger">
  Tanggal ini sudah digunakan oleh termin lain di invoice yang sama.
</small>

            </CCol>
          </CRow>
          <!-- Status Termin: readonly, hanya tampilkan status, tidak bisa diubah manual -->
          <!-- Status Termin: readonly, hanya tampilkan status, tidak bisa diubah manual -->
          <CRow class="mb-3">
            <CCol md="12">
              <CFormLabel for="status_termin">Status Termin</CFormLabel>
              <CFormInput id="status_termin" :value="form.status_termin" readonly />
              <small class="text-muted">Status termin akan otomatis berubah sesuai pembayaran yang di-approve admin.</small>
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
              <CFormLabel for="bukti_pembayaran">Bukti Pembayaran <span v-if="['DP Dibayar','Lunas'].includes(form.status_termin)" class="text-danger">*</span></CFormLabel>
              <CFormInput type="file" id="bukti_pembayaran" accept="image/*,.pdf" @change="handleBuktiPembayaranForm" :required="['DP Dibayar','Lunas'].includes(form.status_termin)" />
              <small class="text-muted">Format: JPEG, PNG, PDF (Max 2MB). <span v-if="['DP Dibayar','Lunas'].includes(form.status_termin)">Wajib diisi.</span></small>
              <div v-if="form.bukti_pembayaran" class="mt-2">
                <a :href="form.bukti_pembayaran" target="_blank">Lihat Bukti Pembayaran</a>
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
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
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
            <CButton color="success" v-if="isApprover && statusForm.status_approval === 'Pending' && ['DP Dibayar','Lunas'].includes(statusForm.status_termin)" @click.prevent="handleSetujui">Setujui</CButton>
            <CButton color="danger" v-if="isApprover && statusForm.status_approval === 'Pending' && ['DP Dibayar','Lunas'].includes(statusForm.status_termin)" @click.prevent="handleTolak">Tolak</CButton>
            <CButton color="warning" v-if="isApprover && statusForm.status_approval === 'Pending' && ['DP Dibayar','Lunas'].includes(statusForm.status_termin)" @click.prevent="handleCancel">Cancel</CButton>
            <CButton type="submit" color="primary" :disabled="statusForm.status_approval !== 'Approved'">Update Status</CButton>
          </div>
        </CForm>
      </CModalBody>
    </CModal>
<!-- Modal Preview Bukti Pembayaran -->
<!-- Modal Preview Bukti Pembayaran -->
<CModal :visible="previewModal.visible" @close="previewModal.visible = false" title="Preview Bukti Pembayaran" size="xl">
  <CModalBody>
    <div v-if="previewModal.url">
      <div class="mb-3 text-end">
        <a :href="previewModal.url" download target="_blank" class="btn btn-sm btn-success">
          <i class="bi bi-download"></i> Download
        </a>
      </div>

      <template v-if="isPDF(previewModal.url)">
        <iframe
          :src="previewModal.url"
          style="width: 100%; height: 600px; border: none;"
          @error="handlePreviewError"
        ></iframe>
      </template>

      <template v-else>
        <img
          :src="previewModal.url"
          alt="Bukti Pembayaran"
          style="max-width: 100%; max-height: 600px;"
          @error="handlePreviewError"
        />
      </template>
    </div>

    <div v-else class="text-muted">Tidak ada file untuk ditampilkan</div>
  </CModalBody>
</CModal>

<CButton
  color="info"
  class="btn-floating-help"
  @click="showTerminHelp"
>
<i class="fas fa-question-circle"></i>
</CButton>


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
import 'datatables.net';

// --- All refs and variables must be declared at the top ---
const selectedProject = ref("");
const selectedInvoice = ref("");
let dataTableInstance = null;
const validateProgressBeforeApproval = (termin) => {
  const projectProgress = termin?.proyek?.progress || 0;
  if (projectProgress < termin.target_progress) {
    Swal.fire({
      icon: 'warning',
      title: 'Progress Belum Mencapai Target',
      text: `Progress proyek saat ini (${projectProgress}%) belum mencapai target termin (${termin.target_progress}%)`
    });
    return false;
  }
  return true;
};
// --- DataTable initialization function ---
function initDataTable() {
  // Destroy previous instance if exists
  if (dataTableInstance) {
    dataTableInstance.clear().destroy();
    dataTableInstance = null;
  }
  // Only initialize if both filters are selected
  if (!selectedProject.value || !selectedInvoice.value) return;
  // Wait for DOM
  nextTick(() => {
    if (!document.getElementById('terminTable')) return;
    dataTableInstance = $('#terminTable').DataTable({
      processing: true,
      serverSide: true,
      destroy: true,
      ajax: {
        url: '/api/termins-datatables',
        type: 'GET',
        data: function (d) {
          d.proyek_id = selectedProject.value;
          d.invoice_id = selectedInvoice.value;
        },
        beforeSend: function (xhr) {
          const token = sessionStorage.getItem('token');
          if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`);
        }
      },
      columns: [
        {
          data: null,
          title: 'No',
          orderable: false,
          searchable: false,
          className: 'text-center',
          render: function (data, type, row, meta) {
            return meta.row + meta.settings._iDisplayStart + 1;
          }
        },
        { data: 'nama_termin', title: 'Nama Termin' },
        { data: 'jenis_termin', title: 'Jenis Termin' },
        { data: 'termin_ke', title: 'Termin Ke' },
        { data: 'nilai_termin', title: 'Nilai Termin', render: d => d === 0 ? '0' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) },
        { data: 'persentase_dp', title: 'Persentase DP', render: d => d ? d + '%' : '-' }, { data: 'proyek.progress', title: 'Progress Pekerjaan' }, // Kolom baru untuk Progress Pekerjaan
        { data: 'target_progress', title: 'Target Progress' }, // Kolom baru untuk Target Progress
        { data: 'nilai_dp', title: 'Nilai DP', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'nilai_pelunasan', title: 'Nilai Pelunasan', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'total_dp_paid', title: 'Total DP Dibayar', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'total_pelunasan_paid', title: 'Total Pelunasan Dibayar', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'total_paid', title: 'Total Dibayar', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'remaining_dp', title: 'Sisa DP', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'remaining_pelunasan', title: 'Sisa Pelunasan', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'remaining_total', title: 'Sisa Termin Belum Dibayar', render: d => d ? new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(d) : '-' },
        { data: 'tanggal_dp', title: 'Tanggal DP', render: d => d ? new Date(d).toLocaleDateString('id-ID') : '-' },
        { data: 'tanggal_pelunasan', title: 'Deadline Pembayaran', render: d => d ? new Date(d).toLocaleDateString('id-ID') : '-' },
       { data: 'status_termin', title: 'Status Termin', render: d => {
  const color = d === 'Lunas' ? 'success' : d === 'DP Dibayar' ? 'info' : 'warning';
  return `<span class="badge bg-${color}">${d}</span>`;
}},
{ data: 'status_approval', title: 'Approval', render: d => {
  const color = d === 'Approved' ? 'success' : d === 'Rejected' ? 'danger' : 'warning';
  return `<span class="badge bg-${color}">${d}</span>`;
}},

        { data: 'approved_by_name', title: 'Disetujui Oleh', render: d => d || '-' },
        { data: 'approved_at', title: 'Waktu Disetujui', render: d => d ? new Date(d).toLocaleString('id-ID') : '-' },
        { data: 'tanggal_dp_dibayar', title: 'Tgl DP Dibayar', render: d => d ? new Date(d).toLocaleDateString('id-ID') : '-' },
        { data: 'tanggal_pelunasan_dibayar', title: 'Tgl Pelunasan Dibayar', render: d => d ? new Date(d).toLocaleDateString('id-ID') : '-' },
        { data: 'keterangan', title: 'Keterangan', render: d => d || '-' },
{
  data: 'bukti_pembayaran_url',
  title: 'Bukti Pembayaran',
  render: function (data, type, row) {
    if (data) {
      return `<button class="btn btn-sm btn-outline-primary view-bukti-btn" data-url="${data}">Lihat</button>`;
    }
    return '-';
  }
}
,
        { data: 'created_at', title: 'Dibuat', render: d => d ? new Date(d).toLocaleString('id-ID') : '-' },
        { data: 'updated_at', title: 'Diupdate', render: d => d ? new Date(d).toLocaleString('id-ID') : '-' },
        {
          data: null,
          title: 'Aksi',
          orderable: false,
          render: function (data, type, row) {
            const disabled = row.status_approval !== 'Approved' ? 'disabled style="pointer-events:none;opacity:0.5;"' : '';
            return `
            <button class="btn btn-sm btn-info status-btn" data-id="${row.id}" ${disabled}>Update Status</button>
            <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">Edit</button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
            <button class="btn btn-sm btn-success approve-btn" data-id="${row.id}">Setujui</button>
            <button class="btn btn-sm btn-danger reject-btn" data-id="${row.id}">Tolak</button>
            <button class="btn btn-sm btn-secondary cancel-btn" data-id="${row.id}">Cancel</button>
            `;
          }
        },
        { data: 'status_termin', title: 'Status Termin' },
        { data: 'invoice_status', title: 'Status Invoice' },
        { data: 'expense_status', title: 'Status Expense' },
        { data: 'income_status', title: 'Status Income' },
      ],
      language: {
        processing: "Memproses...",
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
        infoFiltered: "(difilter dari _MAX_ total data)",
        zeroRecords: "Tidak ditemukan data yang sesuai",
        // paginate: {
        //   first: "Pertama",
        //   last: "Terakhir",
        //   next: "Selanjutnya",
        //   previous: "Sebelumnya"
        // }
      },
      scrollX: true
    });
    // Button event listeners (edit/delete/status)
    $('#terminTable').off('click', '.edit-btn').on('click', '.edit-btn', function () {
      const rowData = dataTableInstance.row($(this).parents('tr')).data();
      if (rowData) openModal('edit', rowData);
    });
    $('#terminTable').off('click', '.delete-btn').on('click', '.delete-btn', async function () {
      const rowData = dataTableInstance.row($(this).parents('tr')).data();
      if (!rowData) return;
      const result = await Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data termin yang dihapus tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
      });
      if (result.isConfirmed) {
  try {
    const token = sessionStorage.getItem('token');
    await axios.delete(`/api/termins/${rowData.id}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    Swal.fire('Berhasil', 'Termin berhasil dihapus', 'success');
    await fetchTermins();
    if (dataTableInstance) {
      dataTableInstance.ajax.reload(); // tambahkan ini
    }
  } catch (err) {
    Swal.fire('Gagal', err.response?.data?.message || 'Tidak dapat menghapus termin', 'error');
  }
}

    });
    $('#terminTable').off('click', '.status-btn').on('click', '.status-btn', function () {
      const rowData = dataTableInstance.row($(this).parents('tr')).data();
      if (rowData) openStatusModal(rowData);
    });
    // Approval buttons
    $('#terminTable').off('click', '.approve-btn').on('click', '.approve-btn', async function () {
      const rowData = dataTableInstance.row($(this).parents('tr')).data();
      if (!rowData) return;
      await updateTerminApproval(rowData, 'Approved');
      dataTableInstance.ajax.reload();
    });
    $('#terminTable').off('click', '.reject-btn').on('click', '.reject-btn', async function () {
      const rowData = dataTableInstance.row($(this).parents('tr')).data();
      if (!rowData) return;
      await updateTerminApproval(rowData, 'Pending');
      dataTableInstance.ajax.reload();
    });
    $('#terminTable').off('click', '.cancel-btn').on('click', '.cancel-btn', async function () {
      const rowData = dataTableInstance.row($(this).parents('tr')).data();
      if (!rowData) return;
      await updateTerminApproval(rowData, 'Pending');
      dataTableInstance.ajax.reload();
    });
   $('#terminTable').off('click', '.view-bukti-btn').on('click', '.view-bukti-btn', function () {
  const url = $(this).data('url'); // Pastikan ini berasal dari bukti_pembayaran_url
  if (url) {
    previewModal.value.url = url;
    previewModal.value.visible = true;
  }
});


  });
}

function handlePreviewError(e) {
  console.error('Preview error:', e);
  previewModal.value.url = '';
  previewModal.value.visible = false;
  alert('Gagal memuat file. Pastikan file masih tersedia atau tidak rusak.');
}

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
  remaining_total: 0,
  displayNilaiDP: '',
  displayNilaiPelunasan: '',
  displayNilaiTermin: '',
  target_progress: 0,

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

const previewModal = ref({
  visible: false,
  url: ''
});

const isPDF = (url) => {
  return url.toLowerCase().endsWith('.pdf');
};

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

// Manual mode state
// const manualNilaiTermin = ref(false);
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

// Menggunakan data dari backend untuk summary
const displayNilaiTermin = computed(() => {
  const dpPaid = Number(form.value.total_dp_paid) || 0;
  const pelunasanPaid = Number(form.value.total_pelunasan_paid) || 0;

  const nilaiTermin = Number(form.value.nilai_termin) || 0;
  const nilaiDP = Number(form.value.nilai_dp) || 0;
  const nilaiPelunasan = Number(form.value.nilai_pelunasan) || 0;

  const totalDibayar = dpPaid + pelunasanPaid;

  // Jika belum ada pembayaran sama sekali
  if (totalDibayar === 0) {
    return formatCurrency(nilaiTermin);
  }

  // Jika sudah lunas
  if (dpPaid >= nilaiDP && pelunasanPaid >= nilaiPelunasan) {
    return formatCurrency(0);
  }

  // Jika hanya DP sebagian/lengkap, tampilkan pelunasan
  return formatCurrency(nilaiPelunasan - pelunasanPaid);
});




// --- Custom summary calculations for correct reactivity and business logic ---

// --- Gunakan data dari backend (API) untuk summary, agar selalu akurat dan lengkap ---
const filteredTermins = computed(() => {
  // Only termins for the selected invoice
  return termins.value.filter(t => `${t.invoice_id}` === `${selectedInvoice.value}`);
});

// Ambil summary dari API (field sudah diisi oleh backend, bukan hitung manual di frontend)
const totalNilaiTerminFiltered = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.nilai_termin) || 0), 0));
const totalDP = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.nilai_dp) || 0), 0));
const totalPelunasan = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.nilai_pelunasan) || 0), 0));
const totalDpPaid = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.total_dp_paid) || 0), 0));
const totalPelunasanPaid = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.total_pelunasan_paid) || 0), 0));
const totalPaid = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.total_paid) || 0), 0)); // Gunakan hanya satu deklarasi
const totalRemainingDP = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.remaining_dp) || 0), 0));
const totalRemainingPelunasan = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.remaining_pelunasan) || 0), 0));
const totalRemaining = computed(() => filteredTermins.value.reduce((sum, t) => sum + (Number(t.remaining_total) || 0), 0));

// Untuk summary card, gunakan field di atas
// Untuk informasi lengkap, bisa juga expose financial_summary dari salah satu termin (jika ingin summary invoice/project)
const selectedInvoiceObj = computed(() => {
  return invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
});
// Total DP dibayar untuk invoice saat ini (semua termin)
const totalDpPaidForInvoice = computed(() => {
  return termins.value
    .filter(t =>
      `${t.invoice_id}` === `${form.value.invoice_id}` &&
      (t.status_termin === 'DP Dibayar' || t.status_termin === 'Lunas') &&
      t.id !== editingId.value
    )
    .reduce((sum, t) => sum + (Number(t.nilai_dp) || 0), 0);
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

// Hapus deklarasi duplikat totalPaid

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

// === HAPUS: Semua perhitungan manual sisa pembayaran, gunakan field dari API ===

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
// const handleNilaiTerminInput = (event) => {
//   const inputValue = event.target.value;
//   form.displayNilaiTermin = inputValue;
//   // Konversi ke angka (hilangkan karakter selain digit)
//   const cleanValue = Number(String(inputValue).replace(/[^\d]/g, '')) || 0;
//   form.value.nilai_termin = cleanValue;

//   // Adjust dynamically based on payment status
//   switch (form.status_termin) {
//     case 'Belum Dibayar':
//       form.displayNilaiTermin = formatCurrency(modalTotalInvoiceAmount);
//       form.value.nilai_termin = modalTotalInvoiceAmount;
//       break;
//     case 'DP Dibayar':
//       const dpVal = modalTotalInvoiceAmount - (form.persentase_dp / 100 * modalTotalInvoiceAmount);
//       form.displayNilaiTermin = formatCurrency(dpVal);
//       form.value.nilai_termin = dpVal;
//       break;
//     case 'Lunas':
//       form.displayNilaiTermin = formatCurrency(0);
//       form.value.nilai_termin = 0;
//       break;
//     default:
//       // Sudah di atas
//       break;
//   }
// };

// const handleNilaiDPInput = (event) => {
//   if (!manualNilaiDP.value) return;
//   const value = event.target.value.replace(/[^\d]/g, '');
//   const newValue = Number(value) || 0;
//   if (newValue > form.value.nilai_termin) {
//     Swal.fire({
//       icon: 'warning',
//       title: 'Peringatan',
//       text: 'Nilai DP tidak boleh melebihi nilai termin'
//     });
//     return;
//   }
//   form.value.nilai_dp = newValue;
//   form.value.displayNilaiDP = formatCurrency(form.value.nilai_dp);
//   if (form.value.nilai_termin > 0) {
//     form.value.persentase_dp = Math.round((form.value.nilai_dp / form.value.nilai_termin) * 100);
//     if (!manualNilaiTermin.value) {
//       form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
//       form.value.displayNilaiPelunasan = formatCurrency(form.value.nilai_pelunasan);
//     }
//   }
// };
// const handleNilaiTerminInput = (event) => {
//   if (!manualNilaiTermin.value) return;
//   const inputValue = event.target.value;
//   const cleanValue = Number(String(inputValue).replace(/[^\d]/g, '')) || 0;
//   form.value.nilai_termin = cleanValue;
// };


// Perhitungan Nilai Pelunasan
// const displayNilaiPelunasan = computed(() => {
//   const nilaiPelunasan = form.value.nilai_termin - form.value.nilai_dp;
//   return formatCurrency(nilaiPelunasan >= 0 ? nilaiPelunasan : 0);
// });

// Perhitungan Sisa Termin Belum Dibayar
const displaySisaTerminBelumDibayar = computed(() => {
  const nilaiPelunasan = form.value.nilai_termin - form.value.nilai_dp;
  const totalApprovedPayments = form.value.total_pelunasan_paid || 0;
  const sisaTermin = nilaiPelunasan - totalApprovedPayments;
  return formatCurrency(sisaTermin >= 0 ? sisaTermin : 0);
});

// Computed property for Nilai Pelunasan (selalu sama dengan Sisa Termin)
// Nilai Pelunasan selalu sama dengan Sisa Termin (remaining_total)
const displayNilaiPelunasan = computed(() => {
  return formatCurrency(form.value.nilai_pelunasan || 0);
});

// Perhitungan DP dan Pelunasan fleksibel sesuai sistem keuangan konstruksi swasta
const calculateValues = () => {
  const nilaiTermin = Number(form.value.nilai_termin) || 0;
  const persenDP = Number(form.value.persentase_dp) || 0;
  const nilaiDP = (persenDP / 100) * nilaiTermin;
  const nilaiPelunasan = nilaiTermin - nilaiDP;

  // Hitung langsung sesuai input user, tanpa batas 20%
  form.value.nilai_dp = Math.max(nilaiDP, 0);
  form.value.nilai_pelunasan = Math.max(nilaiPelunasan, 0);
  form.value.remaining_dp = Math.max(nilaiDP, 0);
  form.value.remaining_pelunasan = Math.max(nilaiPelunasan, 0);
  form.value.remaining_total = Math.max(nilaiPelunasan, 0);

  form.value.displayNilaiDP = formatCurrency(nilaiDP);
  form.value.displayNilaiPelunasan = formatCurrency(nilaiPelunasan);
  form.value.displayNilaiTermin = formatCurrency(nilaiTermin);
};



const nilaiPelunasan = computed(() => { // Ini variabel baru 'nilaiPelunasan' (computed)
  return form.value.nilai_termin - nilaiDP.value; // Menggunakan computed 'nilaiDP'
});


// Keep displayNilaiDP in sync if not manual
watch(() => form.value.nilai_dp, (val) => {
  if (!manualNilaiDP.value) {
    form.value.displayNilaiDP = formatCurrency(val);
  }
});

// Keep nilai_dp in sync with nilai_termin and persentase_dp if not manual
const nilaiDP = computed(() => (form.value.persentase_dp / 100) * form.value.nilai_termin);
watch([
  () => form.value.nilai_termin,
  () => form.value.persentase_dp
], ([nilai_termin, persentase_dp]) => {
  if (!manualNilaiDP.value) {
    form.value.nilai_dp = nilaiDP.value;
  }
});

watch(() => form.jenis_termin, (newValue) => {
  if (newValue === 'Lunas') {
    form.nilai_termin = 0;
    form.displayNilaiTermin = formatCurrency(0);
  }
});

watch(() => form.nilai_dp, (newValue) => {
  if (newValue < modalTotalInvoiceAmount) {
    form.nilai_termin = modalTotalInvoiceAmount - newValue;
    form.displayNilaiTermin = formatCurrency(form.nilai_termin);
  }
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
  nilai_termin: termin.nilai_termin,
  persentase_dp: termin.persentase_dp,
  nilai_dp: termin.nilai_dp,
  nilai_pelunasan: termin.nilai_pelunasan,
  tanggal_dp: formatDate(termin.tanggal_dp),
  tanggal_pelunasan: formatDate(termin.tanggal_pelunasan),
  tanggal_dp_dibayar: formatDate(termin.tanggal_dp_dibayar),
  tanggal_pelunasan_dibayar: formatDate(termin.tanggal_pelunasan_dibayar),
  status_termin: termin.status_termin,
  keterangan: termin.keterangan || "",
  displayNilaiTermin: formatCurrency(termin.nilai_termin),
  displayNilaiDP: formatCurrency(termin.nilai_dp),
  displayNilaiPelunasan: formatCurrency(termin.nilai_pelunasan),
  bukti_pembayaran: null,
  bukti_pembayaran_url: termin.bukti_pembayaran_url || null,
  remaining_dp: (termin.nilai_dp || 0) - (termin.total_dp_paid || 0),
  remaining_pelunasan: (termin.nilai_pelunasan || 0) - (termin.total_pelunasan_paid || 0),
  remaining_total: (termin.nilai_pelunasan || 0) - (termin.total_pelunasan_paid || 0),
  total_dp_paid: termin.total_dp_paid || 0, // ✅ gunakan data dari termin yang diedit
  total_pelunasan_paid: termin.total_pelunasan_paid || 0,
};

      calculateValues();

    editingId.value = termin.id;
    modalTitle.value = "Edit Termin";
    modalButtonText.value = "Update";
} else {
  const selectedInvoiceObj = invoices.value.find(inv => `${inv.id}` === `${selectedInvoice.value}`);
  const today = new Date().toISOString().split('T')[0];

  const invoiceId = selectedInvoice.value;
  const filteredTermins = termins.value.filter(t => `${t.invoice_id}` === `${invoiceId}`);

  // ✅ Hitung total DP, pelunasan, dan semua pembayaran sebelumnya
  const totalDPdibayar = filteredTermins.reduce((sum, t) =>
    (t.status_termin === 'DP Dibayar' || t.status_termin === 'Lunas')
      ? sum + (Number(t.nilai_dp) || 0) : sum, 0);
  const totalPelunasanDibayar = filteredTermins.reduce((sum, t) =>
    t.status_termin === 'Lunas' ? sum + (Number(t.nilai_pelunasan) || 0) : sum, 0);
  const totalTerminDibayar = totalDPdibayar + totalPelunasanDibayar;

  // ✅ Hitung nilai akhir setelah pajak
  let nilaiAkhirSetelahPajak = 0;
  if (selectedInvoiceObj) {
    let total = Number(selectedInvoiceObj.total_amount) || 0;
    if (selectedInvoiceObj.use_ppn) total += Number(selectedInvoiceObj.ppn_amount) || 0;
    if (selectedInvoiceObj.use_pph_non_final) total -= Number(selectedInvoiceObj.pph_non_final_amount) || 0;
    if (selectedInvoiceObj.use_pph_final) total -= Number(selectedInvoiceObj.pph_final_amount) || 0;
    nilaiAkhirSetelahPajak = total;
  }

  // ✅ Set form untuk tambah termin
  form.value = {
    proyek_id: selectedProject.value,
    invoice_id: `${selectedInvoice.value || ""}`,
    nama_termin: "",
    nilai_termin: Math.max(nilaiAkhirSetelahPajak - totalTerminDibayar, 0),
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
    remaining_total: Math.max(nilaiAkhirSetelahPajak - totalTerminDibayar, 0),
    total_dp_paid: totalDPdibayar,
    total_pelunasan_paid: totalPelunasanDibayar,
  };

  editingId.value = null;
  modalTitle.value = "Tambah Termin";
  modalButtonText.value = "Simpan";

  calculateValues();

  if (totalDPdibayar >= nilaiAkhirSetelahPajak * 0.99) {
    form.value.persentase_dp = 0;
    calculateValues(); // hitung ulang
  }
}

  showModal.value = true;
};
const sudahAdaPembayaran = computed(() => {
  return (form.value.total_dp_paid || 0) + (form.value.total_pelunasan_paid || 0) > 0
})
const displayTerminValue = computed(() => {
  if (!sudahAdaPembayaran.value) {
    return form.value.nilai_termin;
  }
  // Jika sudah ada pembayaran → hanya tunjukkan sisa pelunasan
  const sisaPelunasan = form.value.nilai_pelunasan - (form.value.total_pelunasan_paid || 0);
  return Math.max(sisaPelunasan, 0);
});


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
const calculateValues = () => {
  if (!manualNilaiDP.value && form.value.persentase_dp) {
    form.value.nilai_dp = form.value.nilai_termin * (form.value.persentase_dp / 100);
    form.value.nilai_pelunasan = form.value.nilai_termin - form.value.nilai_dp;
  }
}
// Di dalam setup()
const dpValue = computed(() => {
  return form.value.nilai_termin * (form.value.persentase_dp / 100);
});

const pelunasanValue = computed(() => {
  return form.value.nilai_termin - dpValue.value;
});

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
      target_progress: form.value.target_progress || 0,
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
      await fetchSummary(); // Memperbarui summary termin
      await fetchDashboardSummary(); // Memperbarui data dashboard
      window.dispatchEvent(new Event('termin-updated'));
    } else {
      throw new Error(response.data.message || "Terjadi kesalahan saat menyimpan data");
    }

  } catch (err) {
    const status = err.response?.status;
    const errorMessage = err.response?.data?.message || err.message || "Terjadi kesalahan saat menyimpan data";
    const validationErrors = err.response?.data?.errors;
    if (status === 422 && errorMessage.includes('Anggaran')) {
      Swal.fire({
        icon: "warning",
        title: "Anggaran Tidak Cukup",
        text: errorMessage
      });
    } else if (validationErrors) {
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
  if (statusForm.value.status_approval !== 'Approved') {
    Swal.fire('Tidak Bisa Update', 'Status hanya bisa diupdate jika sudah disetujui (Approved) oleh admin.', 'warning');
    return;
  }
  if ((['DP Dibayar', 'Lunas'].includes(statusForm.value.status_termin)) && !statusForm.value.bukti_pembayaran && !statusForm.value.bukti_pembayaran_url) {
    Swal.fire('Peringatan', 'Bukti pembayaran wajib diupload', 'warning');
    return;
  }
  const confirmResult = await Swal.fire({
    title: `Konfirmasi Update Status`,
    text: `Anda yakin ingin mengupdate status termin ini?`,
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
    await fetchSummary(); // Memperbarui summary termin
    await fetchDashboardSummary(); // Memperbarui data dashboard
  } catch (err) {
    Swal.fire('Error', err.response?.data?.message || 'Gagal memperbarui status termin', 'error');
  }
};
/**
 * Mengecek apakah tanggal pelunasan yang dipilih bertabrakan dengan termin lain.
 * @param {string} date - tanggal dalam format YYYY-MM-DD
 * @returns {boolean} true jika tanggal sudah dipakai termin lain
 */
const isDateDisabled = (date) => {
  if (!date || !selectedInvoice.value) return false;

  return termins.value.some(t => {
    const isSameInvoice = `${t.invoice_id}` === `${selectedInvoice.value}`;
    const isSameDate = t.tanggal_pelunasan === date;
    const isDifferentId = editingId.value ? t.id !== editingId.value : true;
    return isSameInvoice && isSameDate && isDifferentId;
  });
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
watch(() => form.value.persentase_dp, (newVal) => {
  if (newVal === null || newVal === undefined) return;

  const nilaiAwal = modalTotalInvoiceAmount.value;

  // Pastikan nilai_termin selalu dikunci pada sisa nilai invoice (finalAmount - total termin existing)
  const invoice = invoices.value.find(inv => `${inv.id}` === `${form.value.invoice_id}`);
  let nilaiAkhir = Number(invoice?.total_amount) || 0;
  if (invoice?.use_ppn) nilaiAkhir += Number(invoice.ppn_amount || 0);
  if (invoice?.use_pph_non_final) nilaiAkhir -= Number(invoice.pph_non_final_amount || 0);
  if (invoice?.use_pph_final) nilaiAkhir -= Number(invoice.pph_final_amount || 0);

  const sisaTermin = nilaiAkhir - totalPaid.value;

  // Tetapkan nilai_termin = sisaTermin dan hitung ulang DP dan pelunasan
  form.value.nilai_termin = Math.max(sisaTermin, 0);

  const dp = (newVal / 100) * form.value.nilai_termin;
  const pelunasan = form.value.nilai_termin - dp;

  form.value.nilai_dp = Math.max(dp, 0);
  form.value.nilai_pelunasan = Math.max(pelunasan, 0);

  form.value.remaining_dp = Math.max(dp - (form.value.total_dp_paid || 0), 0);
  form.value.remaining_pelunasan = Math.max(pelunasan - (form.value.total_pelunasan_paid || 0), 0);
  form.value.remaining_total = Math.max(pelunasan - (form.value.total_pelunasan_paid || 0), 0);

  form.value.displayNilaiTermin = formatCurrency(form.value.nilai_termin);
  form.value.displayNilaiDP = formatCurrency(dp);
  form.value.displayNilaiPelunasan = formatCurrency(pelunasan);
});



// Watch for invoice selection changes
watch(selectedInvoice, async (newValue) => {
  if (newValue && selectedProject.value) {
    await fetchTermins();
  } else {
    termins.value = [];
  }
});


const fetchTerminSummary = async () => {
  if (!form.proyek_id || !form.invoice_id) return;

  try {
    const response = await axios.get('/api/termins/summary', {
      params: {
        proyek_id: form.proyek_id,
        invoice_id: form.invoice_id,
      },
    });

    const summaryData = response.data;
    summary.total_termin = summaryData.total_termin;
    summary.total_dp = summaryData.total_dp;
    summary.total_pelunasan = summaryData.total_pelunasan;
    summary.sisa_belum_dibayar = summaryData.sisa_belum_dibayar;
  } catch (error) {
    console.error('Failed to fetch termin summary:', error);
  }
};

watch(() => form.invoice_id, fetchTerminSummary);

onMounted(async () => {
  await fetchProjects();
  await fetchTermins();
  initDataTable();
});


onUnmounted(() => {
  // Remove old/undefined table variable reference
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
  invoiceOptions.value = [];
  invoices.value = [];
  termins.value = [];
  summary.value = { ...defaultSummary };
  await fetchInvoices(selectedProject.value);
};


const handleInvoiceChange = async () => {
  if (selectedInvoice.value) {
    await fetchTermins();
    await fetchSummary();
    if (dataTableInstance) {
      dataTableInstance.ajax.reload();
    } else {
      nextTick(() => initDataTable());
    }
  } else {
    termins.value = [];
    summary.value = { ...defaultSummary };
  }
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
const showTerminHelp = () => {
  Swal.fire({
    title: 'Apa itu Termin?',
    html: `
      <p><strong>Termin</strong> adalah sistem pembayaran proyek secara bertahap.</p>
      <ul>
        <li><strong>DP</strong> (Down Payment): Pembayaran awal proyek.</li>
        <li><strong>Pelunasan</strong>: Sisa pembayaran setelah DP.</li>
      </ul>
      <p>Anda dapat menentukan persentase DP untuk otomatis membagi termin.</p>
    `,
    icon: 'info'
  });
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
    if (!selectedProject.value) {
      Swal.fire('Pilih proyek terlebih dahulu!');
      return;
    }
    let url = `/api/termins/export-pdf/${selectedProject.value}`;
    // Jika backend sudah support invoice_id, tambahkan query param
    if (selectedInvoice.value) {
      url += `?invoice_id=${selectedInvoice.value}`;
    }
    const response = await axios.get(url, {
      headers: {
        Authorization: `Bearer ${token}`
      },
      responseType: 'blob'
    });
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.setAttribute('download', 'termin-report.pdf');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(link.href);
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: err.response?.data?.message || 'Gagal mengekspor ke PDF'
    });
  }
};

const exportToExcel = async () => {
  try {
    const token = sessionStorage.getItem('token');
    if (!selectedProject.value) {
      Swal.fire('Pilih proyek terlebih dahulu!');
      return;
    }
    let url = `/api/termins/export-excel/${selectedProject.value}`;
    if (selectedInvoice.value) {
      url += `?invoice_id=${selectedInvoice.value}`;
    }
    const response = await axios.get(url, {
      headers: {
        Authorization: `Bearer ${token}`
      },
      responseType: 'blob'
    });
    const blob = new Blob([response.data], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.setAttribute('download', 'termin-report.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(link.href);
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: err.response?.data?.message || 'Gagal mengekspor ke Excel'
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
  if (newProject) {
    await fetchInvoices(newProject); // refresh invoice saat proyek berubah
  }

  if (newProject && newInvoice) {
    await fetchTermins();            // ambil data termin sesuai proyek + invoice
    await fetchSummary();            // ambil ringkasan
    if (dataTableInstance) {
      dataTableInstance.ajax.reload(); // reload DataTable jika sudah ada
    } else {
      nextTick(() => initDataTable()); // inisialisasi DataTable jika belum
    }
  } else {
    termins.value = [];
    summary.value = { ...defaultSummary };
  }
});


const fetchDashboardSummary = async () => {
  try {
    const token = sessionStorage.getItem('token');
    const response = await axios.get('/api/dashboard/summary', {
      headers: { Authorization: `Bearer ${token}` }
    });
    // Update dashboard-related data here if needed
    console.log('Dashboard summary updated:', response.data);
  } catch (err) {
    console.error('Failed to fetch dashboard summary:', err);
  }
};
// Handler untuk tombol Setujui, Tolak, Cancel pada modal status termin
const handleSetujui = async () => {
  statusForm.value.status_approval = 'Approved';
  Swal.fire('Disetujui', 'Termin telah disetujui. Silakan klik Update Status untuk melanjutkan.', 'success');
};

const handleTolak = async () => {
  statusForm.value.status_approval = 'Pending';
  Swal.fire('Ditolak', 'Termin ditandai sebagai Pending. Tidak dapat update status.', 'info');
};

const handleCancel = async () => {
  statusForm.value.status_approval = 'Pending';
  Swal.fire('Dibatalkan', 'Approval dibatalkan. Status tetap Pending.', 'info');
};
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
    await fetchDashboardSummary(); // Trigger dashboard summary update
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
  background: linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0.1));
}
.btn-floating-help {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 1050;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  padding: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

</style>
