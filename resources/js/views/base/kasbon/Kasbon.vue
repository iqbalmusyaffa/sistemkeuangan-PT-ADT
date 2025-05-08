<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-wallet" /> Kasbon
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah Kasbon
            </CButton>
            <CButton color="danger" class="me-2 float-end" @click="downloadKasbonPdf">
              Download PDF
            </CButton>
            <CButton color="success" class="me-2 float-end" @click="downloadKasbonExcel">
              Download Excel
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div style="overflow-x:auto;">
              <table ref="kasbonTableRef" class="display nowrap w-100"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Form -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CFormInput
              v-model="form.user_name"
              label="Nama Pengaju"
              placeholder="Masukkan nama pengaju"
              required
            />
            <CFormSelect
              v-model="form.proyek_id"
              label="Proyek"
              :options="projects.map((project) => ({
                label: project.nama_proyek,
                value: project.id,
              }))"
              required
            />
            <CFormSelect
              v-model="form.payment_method"
              label="Metode Pembayaran"
              :options="paymentMethods.map((method) => ({
                label: method.nama_metode,
                value: method.id,
              }))"
              required
            />
            <CFormInput
              v-model="form.bank_account"
              label="Rekening Bank"
              placeholder="Masukkan rekening bank"
            />
            <CFormInput
              v-model="form.amount"
              label="Jumlah"
              placeholder="Masukkan jumlah"
              type="number"
              required
            />
            <CFormTextarea
              v-model="form.description"
              label="Deskripsi"
              placeholder="Masukkan deskripsi"
              required
            />
            <CFormSelect
              v-model="form.status"
              label="Status"
              :options="['pending', 'approved', 'disbursed', 'settled'].map((status) => ({
                label: status.charAt(0).toUpperCase() + status.slice(1),
                value: status,
              }))"
              required
            />
            <CFormInput
              v-model="form.kasbon_date"
              label="Tanggal Permintaan"
              type="date"
              required
            />
            <CFormInput
              v-model="form.approval_date"
              label="Tanggal Persetujuan"
              type="date"
            />
            <CFormInput
              v-model="form.disbursement_date"
              label="Tanggal Pencairan"
              type="date"
            />
            <CFormInput
              v-model="form.settlement_date"
              label="Tanggal Pelunasan"
              type="date"
            />
            <CFormTextarea
              v-model="form.notes"
              label="Catatan"
              placeholder="Masukkan catatan"
            />
            <CFormInput
              type="file"
              label="Lampiran"
              multiple
              @change="handleFileChange"
            />
            <CButton type="submit" color="primary" class="float-end">
              {{ modalButtonText }}
            </CButton>
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



  const kasbonTableRef = ref(null);
  const showModal = ref(false);
  const modalTitle = ref("Tambah Kasbon");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah");
  const editingId = ref(null);
  const error = ref("");
  const loading = ref(false);
  const paymentMethods = ref([]);
  const projects = ref([]);
  const kasbons = ref([]);
  const attachments = ref([]);

  const form = ref({
    user_name: "",
    proyek_id: "",
    amount: 0,
    description: "",
    status: "pending",
    kasbon_date: "",
    due_date: null,
    approval_date: null,
    disbursement_date: null,
    settlement_date: null,
    payment_method: "",
    bank_account: "",
    bank_name: "",
    account_number: "",
    account_holder: "",
    notes: "",
  });

  // Fetch projects from API
  const fetchProjects = async () => {
    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/proyeks", {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (response.data.status === "success") {
        projects.value = response.data.data;
      } else {
        error.value = "Gagal memuat data proyek";
      }
    } catch (err) {
      error.value = "Gagal memuat data proyek: " + (err.response?.data?.message || err.message);
    }
  };

  // Fetch payment methods from API
  const fetchPaymentMethods = async () => {
    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/payment-methods", {
        headers: { Authorization: `Bearer ${token}` },
      });
      paymentMethods.value = response.data.data || [];
    } catch (err) {
      error.value = "Gagal memuat metode pembayaran: " + (err.response?.data?.message || err.message);
    }
  };

  // Fetch kasbons and initialize DataTable
  const fetchKasbons = async () => {
    loading.value = true;
    try {
      const token = sessionStorage.getItem("token");
      const response = await axios.get("/api/kasbons", {
        headers: { Authorization: `Bearer ${token}` },
      });
      kasbons.value = response.data.data || [];
    } catch (err) {
      error.value = "Gagal memuat data kasbon: " + (err.response?.data?.message || err.message);
    } finally {
      loading.value = false;
    }
  };

  // Initialize DataTable with proper clean up
  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(kasbonTableRef.value)) {
      $(kasbonTableRef.value).DataTable().clear().destroy();
    }

    $(kasbonTableRef.value).DataTable({
      data: kasbons.value,
      columns: [
        { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
        { title: "Nomor Kasbon", data: "nomor_kasbon" },
        { title: "Nama Pengaju", data: "user_name" },
        { title: "Proyek", data: "proyek", render: (data) => data ? `${data.nama_proyek}` : "-" },
        { title: "Jumlah (Rp)", data: "amount", render: (data) => `Rp ${new Intl.NumberFormat("id-ID").format(data)}` },
        { title: "Deskripsi", data: "description" },
        { title: "Status", data: "status_label" },
        { title: "Tanggal Permintaan", data: "kasbon_date", render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-" },
        { title: "Tanggal Jatuh Tempo", data: "due_date", render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-" },
        { title: "Tanggal Persetujuan", data: "approval_date", render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-" },
        { title: "Tanggal Pencairan", data: "disbursement_date", render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-" },
        { title: "Tanggal Pelunasan", data: "settlement_date", render: (data) => data ? new Date(data).toLocaleDateString('id-ID') : "-" },
        { title: "Metode Pembayaran", data: "payment_method", render: (data) => {
          if (data && typeof data === 'object' && data.nama_metode) {
            return data.nama_metode;
          }
          const method = paymentMethods.value.find(m => m.id == data);
          return method ? method.nama_metode : "-";
        } },
        { title: "Lampiran", data: "attachments", render: (data) => data && data.length > 0 ? data.map(a => `<a href='${a.file_url}' target='_blank'>${a.file_name}</a>`).join('<br>') : "-" },
        {
          title: "Aksi",
          data: null,
          render: (data, type, row) =>
            `<button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
              <i class="cil-pencil"></i>
            </button>
            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
              <i class="cil-trash"></i>
            </button>`,
        },
      ],
      scrollX: true,
      autoWidth: false,
      responsive: false,
    });

    // Add event handlers for edit and delete buttons
    $(kasbonTableRef.value).on('click', '.edit-btn', function() {
      const id = $(this).data('id');
      openModal('edit', id);
    });

    $(kasbonTableRef.value).on('click', '.delete-btn', function() {
      const id = $(this).data('id');
      confirmDelete(id);
    });
  };

  // Confirm delete dialog
  const confirmDelete = (id) => {
    Swal.fire({
      title: 'Apakah Anda yakin?',
      text: "Data kasbon akan dihapus permanen!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        deleteKasbon(id);
      }
    });
  };

  // Delete kasbon
  const deleteKasbon = async (id) => {
    try {
      const token = sessionStorage.getItem("token");
      await axios.delete(`/api/kasbons/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      Swal.fire("Sukses", "Kasbon berhasil dihapus", "success");
      await fetchKasbons();
    } catch (err) {
      console.error('Error deleting kasbon:', err);
      Swal.fire("Error", err.response?.data?.message || err.message, "error");
    }
  };

  // Open modal
  const openModal = (mode, id = null) => {
    modalMode.value = mode;
    modalTitle.value = mode === "edit" ? "Edit Kasbon" : "Tambah Kasbon";
    modalButtonText.value = mode === "edit" ? "Update" : "Simpan";

    if (mode === "edit") {
      editingId.value = id;
      const kasbonToEdit = kasbons.value.find((kasbon) => kasbon.id === id);
      if (kasbonToEdit) {
        // Format dates to YYYY-MM-DD
        const formatDate = (dateString) => {
          if (!dateString) return null;
          const date = new Date(dateString);
          return date.toISOString().split('T')[0];
        };

        form.value = {
          user_name: kasbonToEdit.user_name,
          proyek_id: kasbonToEdit.proyek_id,
          amount: kasbonToEdit.amount,
          description: kasbonToEdit.description,
          status: kasbonToEdit.status,
          kasbon_date: formatDate(kasbonToEdit.kasbon_date),
          due_date: formatDate(kasbonToEdit.due_date),
          approval_date: formatDate(kasbonToEdit.approval_date),
          disbursement_date: formatDate(kasbonToEdit.disbursement_date),
          settlement_date: formatDate(kasbonToEdit.settlement_date),
          payment_method: kasbonToEdit.payment_method,
          bank_account: kasbonToEdit.bank_account,
          bank_name: kasbonToEdit.bank_name,
          account_number: kasbonToEdit.account_number,
          account_holder: kasbonToEdit.account_holder,
          notes: kasbonToEdit.notes,
        };
      }
    } else {
      form.value = {
        user_name: "",
        proyek_id: "",
        amount: 0,
        description: "",
        status: "pending",
        kasbon_date: "",
        due_date: null,
        approval_date: null,
        disbursement_date: null,
        settlement_date: null,
        payment_method: "",
        bank_account: "",
        bank_name: "",
        account_number: "",
        account_holder: "",
        notes: "",
      };
    }

    showModal.value = true;
  };

  // Close modal
  const closeModal = () => {
    showModal.value = false;
    modalMode.value = "tambah";
    editingId.value = null;
  };

  // Handle file change
  const handleFileChange = (e) => {
    attachments.value = Array.from(e.target.files);
  };

  // Handle form submit
  const handleSubmit = async () => {
    try {
      const token = sessionStorage.getItem("token");
      const formData = new FormData();
      Object.entries(form.value).forEach(([key, value]) => {
        if (value !== null && value !== undefined) formData.append(key, value);
      });
      attachments.value.forEach(file => {
        formData.append('attachments[]', file);
      });

      let response;
      if (modalMode.value === "edit") {
        response = await axios.post(`/api/kasbons/${editingId.value}?_method=PUT`, formData, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire("Sukses", "Data kasbon berhasil diupdate", "success");
      } else {
        response = await axios.post("/api/kasbons", formData, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire("Sukses", "Data kasbon berhasil ditambahkan", "success");
      }

      closeModal();
      await fetchKasbons();
    } catch (err) {
      console.error('Error submitting form:', err);
      const errorMessage = err.response?.data?.message || err.message;
      const errorDetails = err.response?.data?.errors || err.response?.data?.error;

      if (err.response?.status === 422) {
        // Validation error
        const errorMessages = Object.entries(errorDetails)
          .map(([field, messages]) => `${field}: ${messages.join(', ')}`)
          .join('\n');
        Swal.fire("Validation Error", errorMessages, "error");
      } else {
        // Other errors
        Swal.fire("Error", `${errorMessage}\n${errorDetails || ''}`, "error");
      }
    }
  };

  // Initial fetch on component mount
  onMounted(() => {
    fetchPaymentMethods();
    fetchProjects();
    fetchKasbons();
  });

  // Watch for kasbon changes to reinitialize DataTable
  watch(kasbons, () => {
    nextTick(initDataTable);
  });

  const downloadKasbonPdf = async () => {
    const token = sessionStorage.getItem("token");
    if (!token) {
      Swal.fire('Error', 'Sesi anda telah berakhir. Silakan login kembali.', 'error');
      return;
    }

    try {
      const response = await axios.get('/api/kasbons/export-pdf', {
        responseType: 'blob',
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/pdf'
        }
      });

      if (response.data.size === 0) {
        throw new Error('File PDF kosong');
      }

      const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `kasbon_${new Date().toISOString().split('T')[0]}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error('Error downloading PDF:', err);
      if (err.response?.status === 401) {
        Swal.fire('Error', 'Sesi anda telah berakhir. Silakan login kembali.', 'error');
      } else {
        Swal.fire('Error', err.response?.data?.message || 'Gagal download PDF', 'error');
      }
    }
  };

  const downloadKasbonExcel = async () => {
    const token = sessionStorage.getItem("token");
    if (!token) {
      Swal.fire('Error', 'Sesi anda telah berakhir. Silakan login kembali.', 'error');
      return;
    }

    try {
      const response = await axios.get('/api/kasbons/export-excel', {
        responseType: 'blob',
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        }
      });

      if (response.data.size === 0) {
        throw new Error('File Excel kosong');
      }

      const url = window.URL.createObjectURL(new Blob([response.data], { 
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
      }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `kasbon_${new Date().toISOString().split('T')[0]}.xlsx`);
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (err) {
      console.error('Error downloading Excel:', err);
      if (err.response?.status === 401) {
        Swal.fire('Error', 'Sesi anda telah berakhir. Silakan login kembali.', 'error');
      } else {
        Swal.fire('Error', err.response?.data?.message || 'Gagal download Excel', 'error');
      }
    }
  };
  </script>

  <style scoped>
  table.display, table.dataTable {
    min-width: 1200px !important;
    width: 100% !important;
    table-layout: auto !important;
  }

  .dataTables_wrapper {
    width: 100%;
    overflow-x: auto;
  }

  table.dataTable tbody td {
    white-space: nowrap !important;
    padding: 8px;
  }

  .btn {
    margin: 0 2px;
  }
  </style>
