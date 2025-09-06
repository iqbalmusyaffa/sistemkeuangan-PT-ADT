<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader>
          <font-awesome-icon icon="dollar-sign" class="me-2" /> Pemasukan
        </CCardHeader>

        <CCardBody>
          <!-- ===== ALERTS ===== -->
          <div v-if="error"   class="alert alert-danger">{{ error }}</div>
          <div v-if="loading" class="alert alert-info">Loading…</div>

          <!-- ===== FILTER PROYEK ===== -->
          <CRow class="mb-3">
            <CCol md="6">
              <CFormLabel for="project_filter">Pilih Proyek</CFormLabel>
              <CFormSelect id="project_filter"
                           v-model="selectedProyekId"
                           @change="handleProyekChange">
                <option value="">-- Pilih Proyek --</option>
                <option v-for="p in proyeks" :key="p.id" :value="p.id">
                  {{ p.nama_proyek }}
                </option>
              </CFormSelect>
            </CCol>
            <CCol md="6" class="text-end">
              <CButton v-if="selectedProyekId"
                       color="secondary"
                       class="mt-2"
                       @click="() => { selectedProyekId = ''; fetchData(); }">
                Ganti Proyek
              </CButton>
            </CCol>
          </CRow>

          <!-- ===== ACTIONS ===== -->
          <div v-if="selectedProyekId"
               class="d-flex justify-content-end mb-3">
            <CButton color="primary" @click="openModal('tambah')">
              <font-awesome-icon icon="plus" class="me-1" />
              Tambah Data
            </CButton>
          </div>

          <!-- ===== INFO BOX (NO PROJECT) ===== -->
          <div v-if="!selectedProyekId"
               class="alert alert-info">
            Silakan pilih proyek terlebih dahulu untuk melihat data pemasukan
          </div>

          <!-- ===== DATATABLE ===== -->
          <div v-if="selectedProyekId" class="w-100">
            <table ref="dataTableRef" class="display nowrap"></table>
          </div>
        </CCardBody>
      </CCard>
    </CCol>

    <!-- =============== MODAL ADD / EDIT =============== -->
    <CModal :visible="showModal" @close="closeModal" :title="modalTitle">
      <CModalBody>
        <CForm @submit.prevent="handleSubmit">
   <!-- TERMIN (opsional) -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Termin (opsional)</CFormLabel>
    <select v-model="selectedTerminId"
            class="form-control"
            @change="onTerminChange">
      <option value="">-- Tanpa Termin (buat otomatis) --</option>
      <option v-for="t in termins" :key="t.id" :value="t.id">
        {{ getTerminLabel(t) }}
      </option>
    </select>
    <div v-if="!termins.length" class="small text-muted mt-1">
      Belum ada termin pada proyek ini
    </div>
  </CCol>
</CRow>
<!-- INVOICE (opsional, jika tanpa termin) -->
<CRow v-if="!selectedTerminId" class="mb-3">
  <CCol>
    <CFormLabel>Invoice (opsional)</CFormLabel>
    <select v-model="selectedInvoiceId" class="form-control">
      <option value="">-- Pilih Invoice --</option>
      <option v-for="inv in invoices" :key="inv.id" :value="inv.id">
        {{ inv.invoice_number }} - Rp {{ toIDR(inv.total_amount) }}
      </option>
    </select>
    <div v-if="!invoices.length" class="small text-muted mt-1">
      Tidak ada invoice tersedia
    </div>
  </CCol>
</CRow>

<!-- INVOICE dari TERMIN -->
<CRow v-if="terminInvoice" class="mb-3">
  <CCol>
    <CFormLabel>Invoice</CFormLabel>
    <CAlert color="info" class="py-1">
      No: {{ terminInvoice.invoice_number }}<br />
      Total: Rp {{ toIDR(terminInvoice.total_amount) }}
    </CAlert>
  </CCol>
</CRow>
<!-- TARGET PROGRESS (jika TANPA termin) -->
<CRow class="mb-3" v-if="!selectedTerminId">
  <CCol>
    <CFormLabel>Target Progress (%)</CFormLabel>
    <CFormInput type="number"
                v-model="targetProgress"
                min="0" max="100" step="1"
                placeholder="Masukkan target progress proyek (0-100)" />
    <small class="text-muted">Hanya digunakan jika membuat termin otomatis</small>
  </CCol>
</CRow>

<!-- TIPE DP / PELUNASAN (jika TANPA termin) -->
<CRow class="mb-3" v-if="!selectedTerminId">
  <CCol>
    <CFormLabel>Tipe Pembayaran</CFormLabel>
    <select v-model="selectedType" class="form-control" required>
      <option value="dp">DP Penuh</option>
      <option value="pelunasan">Pelunasan Penuh</option>
    </select>
  </CCol>
</CRow>

<!-- KATEGORI -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Kategori</CFormLabel>
    <select v-model="selectedKategoriId" class="form-control" required>
      <option value="">Pilih Kategori</option>
      <option v-for="c in categories" :key="c.id" :value="c.id">
        {{ c.nama_kategori }}
      </option>
    </select>
  </CCol>
</CRow>

<!-- METODE PEMBAYARAN -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Metode Pembayaran</CFormLabel>
    <select v-model="selectedPaymentMethodId" class="form-control" required>
      <option value="">Pilih Metode</option>
      <option v-for="m in paymentMethods" :key="m.id" :value="m.id">
        {{ m.nama_metode }}
      </option>
    </select>
  </CCol>
</CRow>

<!-- PROYEK -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Proyek</CFormLabel>
    <select v-model="formProyekId" class="form-control" :disabled="modalMode === 'edit'">
      <option value="">Pilih Proyek</option>
      <option v-for="p in proyeks" :key="p.id" :value="p.id">
        {{ p.nama_proyek }}
      </option>
    </select>
  </CCol>
</CRow>

<!-- JUMLAH -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Jumlah</CFormLabel>
    <CFormInput type="number"
                v-model="jumlah"
                :readonly="!!selectedTerminId"
                required />
  </CCol>
</CRow>

<!-- TANGGAL -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Tanggal</CFormLabel>
    <CFormInput v-model="tanggal" type="date" required />
  </CCol>
</CRow>

<!-- DESKRIPSI -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Deskripsi</CFormLabel>
    <CFormInput v-model="deskripsi" type="text" placeholder="Opsional" />
  </CCol>
</CRow>

<!-- STATUS (mode edit saja) -->
<CRow class="mb-3" v-if="modalMode === 'edit'">
  <CCol>
    <CFormLabel>Status</CFormLabel>
    <CFormInput v-model="status" disabled />
  </CCol>
</CRow>

<!-- BUKTI PEMBAYARAN -->
<CRow class="mb-3">
  <CCol>
    <CFormLabel>Bukti Pembayaran</CFormLabel>
    <CFormInput type="file" accept="image/*,.pdf" @change="handleFileUpload" />
    <small class="text-muted">Format: JPEG, PNG, PDF (Max 2MB)</small>
    <div v-if="buktiPreviewUrl" class="mt-2">
      <a :href="buktiPreviewUrl" target="_blank">Preview / Download</a>
    </div>
  </CCol>
</CRow>

<!-- SUBMIT -->
<CButton type="submit" color="primary">
  {{ modalButtonText }}
</CButton>

        </CForm>
      </CModalBody>
    </CModal>
  </CRow>
  <!-- === MODAL PREVIEW BUKTI === -->
<CModal :visible="showBuktiModal" @close="() => showBuktiModal = false" title="Preview Bukti Pembayaran">
  <CModalBody>
    <div v-if="previewType === 'pdf'">
      <iframe :src="previewBuktiUrl" width="100%" height="500px"></iframe>
    </div>
    <div v-else>
      <img :src="previewBuktiUrl" alt="Preview" class="img-fluid" />
    </div>
  </CModalBody>
</CModal>

</template>

<script setup>
/* ---------- Imports ---------- */
import { ref, onMounted, nextTick } from 'vue'
import axios       from 'axios'
import $           from 'jquery'
import Swal        from 'sweetalert2'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faDollarSign, faPlus } from '@fortawesome/free-solid-svg-icons'
import { library } from '@fortawesome/fontawesome-svg-core'

import 'datatables.net-dt/css/dataTables.dataTables.min.css'
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'
import 'datatables.net-responsive-dt'

library.add(faDollarSign, faPlus)

/* ---------- State ---------- */
const dataTableRef            = ref(null)
const categories              = ref([])
const paymentMethods          = ref([])
const proyeks                 = ref([])
const termins                 = ref([])
const incomes                 = ref([])

const error                   = ref('')
const loading                 = ref(false)

const showModal               = ref(false)
const modalTitle              = ref('Tambah Pemasukan')
const modalButtonText         = ref('Simpan')
const modalMode               = ref('tambah')
const editingId               = ref(null)

const selectedProyekId        = ref('')
const formProyekId            = ref('')
const selectedTerminId        = ref('')
const selectedType            = ref('dp')
const selectedKategoriId      = ref('')
const selectedPaymentMethodId = ref('')
const terminInvoice           = ref(null)
const targetProgress = ref(null)
const selectedInvoiceId = ref('')
const invoices = ref([])
const showBuktiModal    = ref(false)
const previewBuktiUrl   = ref(null)
const previewType       = ref('image') // 'pdf' atau 'image'
const jumlah                  = ref('')
const tanggal                 = ref('')
const deskripsi               = ref('')
const status                  = ref('pending')
const invoiceOptions = ref([])

const buktiPembayaran         = ref(null)
const buktiPreviewUrl         = ref(null)

/* ---------- Helpers ---------- */
const toIDR = n => parseFloat(n || 0).toLocaleString('id-ID')
// const fetchInvoices = async () => {
//   const token = sessionStorage.getItem('token')
//   try {
//     const res = await axios.get(`/api/invoices?proyek_id=${selectedProyekId.value}`, {
//       headers: {
//         Authorization: `Bearer ${token}`
//       }
//     })
//     invoices.value = res.data.data || res.data
//   } catch (err) {
//     console.error('Gagal ambil invoice', err)
//   }
// }
const fetchInvoices = async (proyekId) => {
  if (!proyekId) return
  try {
    const token = sessionStorage.getItem("token")
    const res = await axios.get(`/api/proyeks/${proyekId}/invoices`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    invoices.value = Array.isArray(res.data)
      ? res.data
      : (res.data.data || [])
  } catch (err) {
    console.error('Gagal fetch invoice:', err)
    invoices.value = []
  }
}

/* ---------- Fetch functions ---------- */
const fetchData = async () => {
  loading.value = true
  error.value   = ''
  const token   = sessionStorage.getItem('token')
  try {
    const params = new URLSearchParams()
if (selectedProyekId.value !== '') params.append('proyek_id', selectedProyekId.value)


    const [incomeRes, categoryRes, payRes, proyekRes] = await Promise.all([
      axios.get(`/api/incomes?${params.toString()}`, { headers: { Authorization: `Bearer ${token}` } }),
      axios.get('/api/kategori',            { headers: { Authorization: `Bearer ${token}` } }),
      axios.get('/api/payment-methods',     { headers: { Authorization: `Bearer ${token}` } }),
      axios.get('/api/proyeks',             { headers: { Authorization: `Bearer ${token}` } }),
    ])

    categories.value      = (categoryRes.data.data || categoryRes.data).filter(c => c.jenis === 'pemasukan')
    paymentMethods.value  = payRes.data.data || payRes.data
    proyeks.value         = proyekRes.data.data || proyekRes.data
    incomes.value         = incomeRes.data.data || incomeRes.data

    nextTick(initDataTable)
  } catch (e) {
    error.value = `Gagal memuat data: ${e.message}`
  } finally {
    loading.value = false
  }
}

const fetchTermins = async () => {
  if (!selectedProyekId.value) { termins.value = []; return }
  const token = sessionStorage.getItem('token')
  try {
    const res = await axios.get(`/api/termins?proyek_id=${selectedProyekId.value}`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    termins.value = res.data.data || res.data
  } catch (e) { console.error('Gagal memuat termin:', e) }
}

const fetchInvoiceForTermin = async () => {
  terminInvoice.value = null
  if (!selectedTerminId.value) return
  const token = sessionStorage.getItem('token')
  try {
    const [invoiceRes, terminRes] = await Promise.all([
      axios.get(`/api/termins/${selectedTerminId.value}/invoice`, { headers: { Authorization: `Bearer ${token}` } }),
      axios.get(`/api/termins/${selectedTerminId.value}`,         { headers: { Authorization: `Bearer ${token}` } })
    ])

    terminInvoice.value = invoiceRes.data.data || null
    const termin = terminRes.data.data
    if (termin?.proyek_id) formProyekId.value = termin.proyek_id
  } catch (e) { console.error('Gagal memuat invoice/termin:', e) }
}

/* ---------- Handlers ---------- */
const handleProyekChange = () => {
  selectedTerminId.value = ''
  terminInvoice.value    = null
  selectedInvoiceId.value = ''

  fetchData()
  fetchTermins()
  fetchInvoices(selectedProyekId.value)
}


const onTerminChange = async () => {
  if (selectedTerminId.value) {
    await fetchInvoiceForTermin()

    const t = termins.value.find(x => x.id === +selectedTerminId.value)

    if (t) {
      // Gunakan jumlah_pembayaran jika ada (akses dari backend Laravel)
      jumlah.value = t.jumlah_pembayaran
        ?? (t.jenis_termin === 'DP'
            ? t.nilai_dp
            : t.jenis_termin === 'Pelunasan'
              ? t.nilai_pelunasan
              : t.nilai_termin)
    } else {
      jumlah.value = ''
    }

  } else {
    terminInvoice.value = null
    jumlah.value = ''
  }
}


const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
    $(dataTableRef.value).DataTable().destroy()
  }
  $(dataTableRef.value).DataTable({
    data: incomes.value,
    columns: [
      { title: 'No', data: null, render: (_, __, ___, meta) => meta.row + 1 },
      { title: 'Kode Transaksi', data: 'kode_transaksi' },
      { title: 'Tanggal', data: 'tanggal', render: d => new Date(d).toLocaleDateString('id-ID') },
      { title: 'Kategori', data: 'kategori.nama_kategori' },
      { title: 'Metode Pembayaran', data: 'payment_method.nama_metode' },
      { title: 'Jumlah', data: 'jumlah', render: d => `Rp ${toIDR(d)}` },
      { title: 'Status', data: 'status',
        render: s => `<span class="badge bg-${getBadgeColor(s)}">${s}</span>` },
      { title: 'Aksi', data: null,
        render: (_, __, row) => {
          const approveBtn = row.status === 'pending'
            ? `<button class="btn btn-sm btn-success approve-btn" data-id="${row.id}">Approve</button>` : ''
          const statusBtn = row.status !== 'rejected'
            ? `<button class="btn btn-sm btn-info ms-1 status-btn" data-id="${row.id}">Ubah Status</button>` : ''
            const revokeBtn = row.status === 'approved'
  ? `<button class="btn btn-sm btn-secondary revoke-btn" data-id="${row.id}">Revoke</button>` : ''

          return `
            <button class="btn btn-sm btn-warning edit-btn"  data-id="${row.id}">Edit</button>
            <button class="btn btn-sm btn-danger  delete-btn"data-id="${row.id}">Hapus</button>
  ${approveBtn}${revokeBtn}`
        } },
{
  title: 'Bukti Pembayaran',
  data: 'bukti_pembayaran_url',
  defaultContent: 'Tidak ada',
  render: (url) => {
    if (!url) return 'Tidak ada'
    const ext = url.split('.').pop().toLowerCase()

    return `
      <button class="btn btn-outline-primary btn-sm bukti-btn" data-url="${safeUrl}">
        <i class="fa fa-eye me-1"></i>Lihat</button>

      <a href="${safeUrl}" class="btn btn-outline-secondary btn-sm ms-1" download target="_blank" rel="noopener noreferrer">
        <i class="fa fa-download me-1"></i>Unduh</a>
    `
  }

}
    ],
    responsive: true, scrollX: true, destroy: true,
  })

  $(dataTableRef.value)
    .off('click')
    .on('click', '.edit-btn',  e => { const id=$(e.currentTarget).data('id'); openModal('edit', incomes.value.find(i=>i.id===id)) })
    .on('click', '.delete-btn',e => handleDelete($(e.currentTarget).data('id')))
    .on('click', '.bukti-btn', e => {
  const url = $(e.currentTarget).data('url')
  const fileExt = url.split('.').pop().toLowerCase()
previewBuktiUrl.value = encodeURI(url)

  previewType.value     = fileExt === 'pdf' ? 'pdf' : 'image'
  showBuktiModal.value  = true
})
    .on('click', '.approve-btn',e => approveIncome($(e.currentTarget).data('id')))
    .on('click', '.revoke-btn', async e => {
  const id = $(e.currentTarget).data('id')
  const konfirmasi = await Swal.fire({
    title: 'Yakin ingin revoke approval?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Revoke',
    cancelButtonText: 'Batal',
  })
  if (!konfirmasi.isConfirmed) return

  try {
    const token = sessionStorage.getItem('token')
    await axios.post(`/api/incomes/${id}/revoke`, {}, {
      headers: { Authorization: `Bearer ${token}` }
    })
    Swal.fire('Berhasil', 'Approval berhasil di-revoke', 'success')
    fetchData()
  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Tidak dapat revoke', 'error')
  }
})

}

const getBadgeColor = s => ({
  pending:'warning','DP Sebagian':'warning','Pelunasan Sebagian':'warning',
  'DP Dibayar':'info','Belum Dibayar':'secondary',Lunas:'success',
  approved:'success',rejected:'danger'
}[s] || 'secondary')

const approveIncome = async id => {
  const token = sessionStorage.getItem('token')
  try {
    const r = await axios.post(`/api/incomes/${id}/approve`, {}, { headers:{Authorization:`Bearer ${token}`} })
    Swal.fire('Berhasil', r.data.message || 'Income di‑approve', 'success')
    fetchData()
  } catch (e) { Swal.fire('Gagal', e.response?.data?.message || 'Tidak dapat approve', 'error') }
}

const openModal = async (mode, income = null) => {
  modalMode.value = mode
  buktiPembayaran.value = null; buktiPreviewUrl.value = null
  await fetchTermins()     // pastikan list termin up to date

  if (mode === 'edit' && income) {
    editingId.value           = income.id
    selectedTerminId.value    = income.termin_id || ''
    await onTerminChange()    // isi invoice & jumlah bila ada
    selectedKategoriId.value  = income.kategori_id
    selectedPaymentMethodId.value = income.payment_method_id
    formProyekId.value        = income.proyek_id
    jumlah.value              = income.jumlah
    tanggal.value             = income.tanggal
    status.value              = income.status
    deskripsi.value           = income.deskripsi || ''
    modalTitle.value          = 'Edit Pemasukan'
    modalButtonText.value     = 'Update'
    if (income.bukti_pembayaran) {
if (income.bukti_pembayaran) {
  buktiPreviewUrl.value = income.bukti_pembayaran_url || null
}

    }
    if (!formProyekId.value && selectedTerminId.value) {
  const t = termins.value.find(t => t.id === +selectedTerminId.value)
  if (t?.proyek_id) formProyekId.value = t.proyek_id
}
if (!selectedTerminId && income.termin?.target_progress) {
  targetProgress.value = income.termin.target_progress
}
  await fetchInvoices(formProyekId.value) // <--- WAJIB INI


    if (income.type) {
      selectedType.value = income.type // dp | pelunasan
    } else {
      selectedType.value = 'dp' // default
    }
    modalTitle.value          = 'Edit Pemasukan'
    modalButtonText.value     = 'Update'
  } else {
    /* -------- mode tambah -------- */
    editingId.value           = null
    selectedTerminId.value    = ''
    selectedType.value        = 'dp'
    terminInvoice.value       = null
    formProyekId.value        = selectedProyekId.value || ''
    selectedKategoriId.value  = ''
    selectedPaymentMethodId.value = ''
    jumlah.value              = ''
      await fetchInvoices(formProyekId.value) // <---- WAJIB INI

    tanggal.value             = ''
    deskripsi.value           = ''
    modalTitle.value          = 'Tambah Pemasukan'
    modalButtonText.value     = 'Simpan'
  }
  showModal.value = true
}

const closeModal = () => { showModal.value = false; buktiPreviewUrl.value = null }

const handleFileUpload = e => {
  buktiPembayaran.value = e.target.files[0] || null
  buktiPreviewUrl.value = buktiPembayaran.value ? URL.createObjectURL(buktiPembayaran.value) : null
}

const handleSubmit = async () => {
  const token = sessionStorage.getItem('token')
  if (!selectedTerminId.value && !jumlah.value) {
  return Swal.fire('Validasi Gagal', 'Jumlah wajib diisi jika tanpa termin', 'error')
}

  const fd = new FormData()

  /* ===== field selalu dikirim ===== */
  fd.append('kategori_id',       selectedKategoriId.value)
  fd.append('payment_method_id', selectedPaymentMethodId.value)
  fd.append('proyek_id',         formProyekId.value || selectedProyekId.value)
  fd.append('tanggal',           tanggal.value)
  fd.append('deskripsi',         deskripsi.value || '')
  fd.append('target_progress', targetProgress.value || '')
  fd.append('invoice_id', selectedInvoiceId.value || '')

  /* ===== field kondisional ===== */
  if (selectedTerminId.value) {
    fd.append('termin_id', selectedTerminId.value)
    if (terminInvoice.value) fd.append('invoice_id', terminInvoice.value.id)
    /* jumlah & type sengaja tidak dikirim – controller ambil dari termin */
  } else {
    fd.append('type',   selectedType.value)  // dp | pelunasan
    fd.append('jumlah', jumlah.value)
  }

  if (buktiPembayaran.value) fd.append('bukti_pembayaran', buktiPembayaran.value)

  try {
    if (modalMode.value === 'tambah') {
      await axios.post('/api/incomes', fd, {
        headers:{ Authorization:`Bearer ${token}`, 'Content-Type':'multipart/form-data' }
      })
      Swal.fire('Berhasil', 'Data ditambahkan', 'success')
    } else {
      fd.append('_method', 'PUT')
      await axios.post(`/api/incomes/${editingId.value}`, fd, {
        headers:{ Authorization:`Bearer ${token}`, 'Content-Type':'multipart/form-data' }
      })
      Swal.fire('Berhasil', 'Data diperbarui', 'success')
    }
    closeModal(); fetchData()
  } catch (err) {
    Swal.fire('Gagal', err.response?.data?.message || 'Periksa kembali data', 'error')
  }
}

const handleDelete = async id => {
  const konfirmasi = await Swal.fire({
    title:'Yakin ingin menghapus?', text:'Data yang dihapus tidak bisa dikembalikan.',
    icon:'warning', showCancelButton:true,
    confirmButtonColor:'#d33', cancelButtonColor:'#6c757d',
    confirmButtonText:'Hapus', cancelButtonText:'Batal'
  })
  if (!konfirmasi.isConfirmed) return
  try {
    const token = sessionStorage.getItem('token')
    await axios.delete(`/api/incomes/${id}`, { headers:{Authorization:`Bearer ${token}`} })
    Swal.fire('Berhasil', 'Data berhasil dihapus', 'success')
    fetchData()
  } catch (e) { Swal.fire('Gagal', e.response?.data?.message || 'Tidak dapat menghapus data', 'error') }
}

const getTerminLabel = t => {
  const nominal =
    t.jenis_termin === 'DP'        ? (t.nilai_dp        ?? 0) :
    t.jenis_termin === 'Pelunasan' ? (t.nilai_pelunasan ?? 0) :
                                     (t.nilai_termin    ?? 0)
  return `${t.kode_termin} | ${t.nama_termin} - Rp ${toIDR(nominal)}`
}

/* ---------- Lifecycle ---------- */
onMounted(() => { fetchData(); fetchTermins() })
</script>

<style scoped>
.w-100 { width:100%; overflow-x:auto; }
.dataTables_wrapper { overflow-x:auto; }
table.display { width:100%!important; }
</style>
