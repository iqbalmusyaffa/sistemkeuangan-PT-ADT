<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-dollar" /> Data Pengeluaran
            <CButton color="primary" class="float-end" @click="openModal('tambah')" :disabled="!selectedProject">
              Tambah Pengeluaran
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>

            <!-- Project Filter -->
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="project_filter">Pilih Project</CFormLabel>
                <CFormSelect
                  v-model="selectedProject"
                  id="project_filter"
                  @change="handleProjectChange"
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

            <!-- Alert when no project selected -->
            <div v-if="!selectedProject" class="alert alert-info">
              Silakan pilih project terlebih dahulu untuk melihat data pengeluaran
            </div>

            <!-- DataTable -->
            <div v-if="selectedProject" class="w-100">
              <table ref="dataTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Tambah/Edit -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle" size="lg">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Proyek</CFormLabel>
                <select v-model="form.proyek_id" class="form-select" required>
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
                </select>
              </CCol>
              <CCol md="6">
                <CFormLabel>Jenis Kategori</CFormLabel>
                <CFormSelect v-model="form.category_type" class="mb-3" required>
                  <option value="">Pilih Jenis Kategori</option>
                  <option value="material">Material</option>
                  <option value="service">Jasa</option>
                </CFormSelect>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Kategori</CFormLabel>
                <CFormSelect
                  v-model="selectedCategory"
                  required
                >
                  <option value="">Pilih Kategori</option>
                  <template v-if="form.category_type === 'material'">
                    <option v-for="category in categories"
                            :key="category.id"
                            :value="category.id">
                      {{ category.nama_kategori }}
                    </option>
                  </template>
                  <template v-else>
                    <option v-for="category in serviceCategories"
                            :key="category.id"
                            :value="category.id">
                      {{ category.nama_kategori }}
                    </option>
                  </template>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel>Tanggal Transaksi</CFormLabel>
                <CFormInput
                  type="date"
                  v-model="form.transaction_date"
                  required
                />
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Jumlah</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    v-model="form.displayAmount"
                    @input="handleAmountInput"
                    required
                    placeholder="0"
                  />
                </div>
              </CCol>
              <CCol md="6">
                <CFormLabel>Status</CFormLabel>
                <CFormSelect v-model="form.status" required>
                  <option value="Pending">Pending</option>
                  <option value="Approved">Approved</option>
                  <option value="Rejected">Rejected</option>
                </CFormSelect>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel>Metode Pembayaran</CFormLabel>
                <CFormSelect v-model="form.payment_method" required>
                  <option value="">Pilih Metode Pembayaran</option>
                  <option value="Cash">Cash</option>
                  <option value="Transfer">Transfer Bank</option>
                  <option value="Debit">Kartu Debit</option>
                  <option value="Credit">Kartu Kredit</option>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel>Dana Persiapan</CFormLabel>
                <div class="input-group">
                  <span class="input-group-text">Rp</span>
                  <CFormInput
                    type="text"
                    v-model="form.displayPreparedFund"
                    @input="handlePreparedFundInput"
                    placeholder="0"
                  />
                </div>
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="12">
                <CFormLabel>Deskripsi</CFormLabel>
                <CFormTextarea
                  v-model="form.description"
                  rows="3"
                  required
                  placeholder="Masukkan deskripsi pengeluaran..."
                />
              </CCol>
            </CRow>

            <CRow class="mb-3">
              <CCol md="12">
                <CFormSwitch
                  v-model="form.isManualKode"
                  label="Input kode transaksi manual?"
                />
              </CCol>
            </CRow>

            <CRow v-if="form.isManualKode" class="mb-3">
              <CCol md="6">
                <CFormLabel>Kode Transaksi</CFormLabel>
                <CFormInput
                  v-model="form.kode_transaksi"
                  placeholder="Contoh: EXP/2024/001"
                  :required="form.isManualKode"
                />
              </CCol>
            </CRow>

            <div class="d-flex justify-content-end gap-2">
              <CButton color="secondary" @click="closeModal">
                Batal
              </CButton>
              <CButton type="submit" color="primary">
                {{ modalButtonText }}
              </CButton>
            </div>
          </CForm>
        </CModalBody>
      </CModal>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted, nextTick, computed } from 'vue'
  import { useRouter } from 'vue-router'
  import axios from 'axios'
  import $ from 'jquery'
  import Swal from 'sweetalert2'
  import "datatables.net-dt/css/dataTables.dataTables.min.css"
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css"
  import "datatables.net-responsive-dt"

  const router = useRouter()
  const dataTableRef = ref(null)

  const projects = ref([])
  const categories = ref([])
  const expenses = ref([])

  const error = ref('')
  const loading = ref(false)

  const showModal = ref(false)
  const modalTitle = ref('Tambah Pengeluaran')
  const modalButtonText = ref('Simpan')
  const modalMode = ref('tambah')
  const editingId = ref(null)

  const form = ref({
    proyek_id: '',
    category_type: '',
    category_id: '',
    service_category_id: '',
    amount: 0,
    displayAmount: '',
    description: '',
    transaction_date: '',
    status: 'Pending',
    payment_method: '',
    prepared_fund: 0,
    displayPreparedFund: '',
    kode_transaksi: '',
    isManualKode: false
  })

  const serviceCategories = ref([])

  const groupedProjects = computed(() => {
    const grouped = {};
    projects.value.forEach(project => {
      if (!grouped[project.nama_customer]) {
        grouped[project.nama_customer] = [];
      }
      grouped[project.nama_customer].push(project);
    });
    return grouped;
  })

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID').format(value);
  }

  const unformatCurrency = (value) => {
    return Number(value.replace(/[^\d,-]/g, ''));
  }

  const handleAmountInput = (event) => {
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.amount = unformattedValue;
    form.value.displayAmount = formatCurrency(unformattedValue);
  }

  const handlePreparedFundInput = (event) => {
    const unformattedValue = unformatCurrency(event.target.value);
    form.value.prepared_fund = unformattedValue;
    form.value.displayPreparedFund = formatCurrency(unformattedValue);
  }

  const selectedCategory = computed({
    get: () => {
      return form.value.category_type === 'material'
        ? form.value.category_id
        : form.value.service_category_id;
    },
    set: (value) => {
      if (form.value.category_type === 'material') {
        form.value.category_id = value;
        form.value.service_category_id = null;
      } else {
        form.value.service_category_id = value;
        form.value.category_id = null;
      }
    }
  });

  const selectedProject = ref('');
  const selectedProjectDetails = ref(null);

  const fetchData = async () => {
    loading.value = true;
    try {
      const token = sessionStorage.getItem('token');
      console.log('Fetching projects data...');
      const [projectRes, categoryRes, serviceCategoryRes] = await Promise.all([
        axios.get('/api/proyeks', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/categories', { headers: { Authorization: `Bearer ${token}` } }),
        axios.get('/api/service-categories', { headers: { Authorization: `Bearer ${token}` } })
      ]);

      projects.value = projectRes.data.data || projectRes.data;
      console.log('Projects data structure:', JSON.stringify(projects.value, null, 2));
      console.log('First project:', projects.value[0]);
      categories.value = categoryRes.data.data || categoryRes.data;
      serviceCategories.value = serviceCategoryRes.data.data || serviceCategoryRes.data;

      if (selectedProject.value) {
        console.log('Selected project exists, filtering...');
        await filterByProject();
      } else {
        console.log('No selected project, clearing data');
        expenses.value = [];
        if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
          $(dataTableRef.value).DataTable().destroy();
          $(dataTableRef.value).empty();
        }
      }
    } catch (e) {
      console.error('Error fetching data:', e);
      error.value = 'Gagal memuat data';
    } finally {
      loading.value = false;
    }
  };

  const filterByProject = async () => {
    console.log('filterByProject called with selectedProject:', selectedProject.value);
    if (!selectedProject.value) {
      console.log('No project selected');
      expenses.value = [];
      if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
        $(dataTableRef.value).DataTable().destroy();
        $(dataTableRef.value).empty();
      }
      return;
    }

    try {
      loading.value = true;
      const token = sessionStorage.getItem('token');
      console.log('Selected Project ID:', selectedProject.value);

      const [expenseRes, projectDetailsRes] = await Promise.all([
        axios.get('/api/expenses', {
          headers: { Authorization: `Bearer ${token}` },
          params: { proyek_id: selectedProject.value }
        }),
        axios.get(`/api/proyeks/${selectedProject.value}`, {
          headers: { Authorization: `Bearer ${token}` }
        })
      ]);

      console.log('Expense API Response:', expenseRes.data);
      console.log('Project Details Response:', projectDetailsRes.data);

      if (!expenseRes.data || !expenseRes.data.data) {
        throw new Error('Invalid response format from expenses API');
      }

      selectedProjectDetails.value = projectDetailsRes.data;
      expenses.value = expenseRes.data.data;

      console.log('Expenses after setting:', expenses.value);

      // Pastikan data sudah ada sebelum DataTable diinisialisasi
      await nextTick();
      initDataTable();
    } catch (e) {
      console.error('Error in filterByProject:', e);
      error.value = 'Gagal memuat data pengeluaran proyek';
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Gagal memuat data pengeluaran. Silakan coba lagi.',
        confirmButtonText: 'OK'
      });
    } finally {
      loading.value = false;
    }
  };

  const changeProject = () => {
    selectedProject.value = '';
    selectedProjectDetails.value = null;
    expenses.value = [];
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().destroy();
      $(dataTableRef.value).empty();
    }
  };

  const initDataTable = () => {
    console.log('Initializing DataTable with data:', expenses.value);
    
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      console.log('Destroying existing DataTable');
      $(dataTableRef.value).DataTable().destroy();
    }

    $(dataTableRef.value).DataTable({
      data: expenses.value,
      columns: [
        {
          title: 'No',
          data: null,
          width: '5%',
          render: (data, type, row, meta) => meta.row + 1
        },
        {
          title: 'Tanggal',
          data: 'transaction_date',
          width: '10%',
          render: (data) => {
            const date = new Date(data);
            return date.toLocaleDateString('id-ID', {
              day: '2-digit',
              month: '2-digit',
              year: 'numeric'
            });
          }
        },
        {
          title: 'Kode',
          data: 'kode_transaksi',
          width: '10%',
          render: (data) => data || '-'
        },
        {
          title: 'Nama Proyek',
          data: 'proyek.nama_proyek',
          width: '15%',
          render: (data, type, row) => data || 'N/A'
        },
        {
          title: 'Kategori',
          data: null,
          width: '15%',
          render: (data, type, row) => {
            if (row.service_category_id) {
              return `Jasa - ${row.service_category?.nama_kategori || 'N/A'}`;
            }
            return `Material - ${row.category?.nama_kategori || 'N/A'}`;
          }
        },
        {
          title: 'Deskripsi',
          data: 'description',
          width: '20%',
          render: (data) => data || '-'
        },
        {
          title: 'Jumlah',
          data: 'amount',
          width: '10%',
          className: 'text-end',
          render: (data) => `Rp ${parseFloat(data).toLocaleString('id-ID')}`
        },
        {
          title: 'Status',
          data: 'status',
          width: '8%',
          render: (data) => {
            const statusClass = {
              'Pending': 'badge bg-warning',
              'Approved': 'badge bg-success',
              'Rejected': 'badge bg-danger'
            }[data] || 'badge bg-secondary';
            return `<span class="${statusClass}">${data}</span>`;
          }
        },
        {
          title: 'Sumber',
          data: 'source_type',
          width: '8%',
          render: (data) => {
            if (!data) return 'Manual';
            return data === 'purchase' ? 'Pembelian' : 'Termin';
          }
        },
        {
          title: 'Aksi',
          data: null,
          width: '10%',
          render: (data, type, row) => {
            if (row.source_type) {
              return '<button class="btn btn-sm btn-info view-btn" data-id="' + row.id + '">Detail</button>';
            }
            return `
              <button class="btn btn-sm btn-warning edit-btn" data-id="${row.id}">Edit</button>
              <button class="btn btn-sm btn-danger ms-1 delete-btn" data-id="${row.id}">Hapus</button>
            `;
          }
        },
      ],
      order: [[1, 'desc']], // Sort by date descending
      responsive: true,
      scrollX: true,
      language: {
        "emptyTable": "Tidak ada data yang tersedia",
        "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
        "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
        "infoFiltered": "(difilter dari _MAX_ total data)",
        "lengthMenu": "Tampilkan _MENU_ data",
        "loadingRecords": "Memuat...",
        "processing": "Memproses...",
        "search": "Cari:",
        "zeroRecords": "Tidak ditemukan data yang sesuai",
        "paginate": {
          "first": "Pertama",
          "last": "Terakhir",
          "next": "Selanjutnya",
          "previous": "Sebelumnya"
        }
      },
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"d-flex align-items-center"l><"d-flex"f>>rtip',
    });

    // Update event listeners
    $(dataTableRef.value).on('click', '.edit-btn', function () {
      const id = $(this).data('id');
      const expense = expenses.value.find(e => e.id === id);
      if (expense) openModal('edit', expense);
    });

    $(dataTableRef.value).on('click', '.delete-btn', function () {
      const id = $(this).data('id');
      handleDelete(id);
    });

    $(dataTableRef.value).on('click', '.view-btn', function () {
      const id = $(this).data('id');
      router.push(`/base/pengeluaran/${id}`);
    });
  }

  const openModal = (mode, expense = null) => {
    if (!selectedProject.value && mode === 'tambah') {
      Swal.fire({
        icon: 'warning',
        title: 'Perhatian',
        text: 'Silakan pilih project terlebih dahulu'
      });
      return;
    }

    modalMode.value = mode;
    if (mode === 'edit' && expense) {
      form.value = {
        proyek_id: expense.proyek_id,
        category_type: expense.service_category_id ? 'service' : 'material',
        category_id: expense.category_id || '',
        service_category_id: expense.service_category_id || '',
        amount: expense.amount,
        displayAmount: formatCurrency(expense.amount),
        description: expense.description,
        transaction_date: expense.transaction_date,
        status: expense.status,
        payment_method: expense.payment_method || '',
        prepared_fund: expense.prepared_fund || 0,
        displayPreparedFund: formatCurrency(expense.prepared_fund || 0),
        kode_transaksi: expense.kode_transaksi || '',
        isManualKode: !!expense.kode_transaksi
      };
      editingId.value = expense.id;
      modalTitle.value = 'Edit Pengeluaran';
      modalButtonText.value = 'Update';
    } else {
      form.value = {
        proyek_id: selectedProject.value,
        category_type: '',
        category_id: '',
        service_category_id: '',
        amount: 0,
        displayAmount: '',
        description: '',
        transaction_date: new Date().toISOString().split('T')[0],
        status: 'Pending',
        payment_method: '',
        prepared_fund: 0,
        displayPreparedFund: '',
        kode_transaksi: '',
        isManualKode: false
      };
      editingId.value = null;
      modalTitle.value = 'Tambah Pengeluaran';
      modalButtonText.value = 'Simpan';
    }
    showModal.value = true;
  }

  const closeModal = () => {
    showModal.value = false;
  }

  const handleSubmit = async () => {
    try {
      if (form.value.isManualKode && !form.value.kode_transaksi.trim()) {
        return Swal.fire('Peringatan', 'Kode transaksi harus diisi jika menggunakan kode manual', 'warning');
      }

      const token = sessionStorage.getItem('token');
      const payload = {
        proyek_id: form.value.proyek_id,
        category_id: form.value.category_type === 'material' ? form.value.category_id : null,
        service_category_id: form.value.category_type === 'service' ? form.value.service_category_id : null,
        amount: form.value.amount,
        description: form.value.description,
        transaction_date: form.value.transaction_date,
        status: form.value.status,
        payment_method: form.value.payment_method,
        prepared_fund: form.value.prepared_fund,
        kode_transaksi: form.value.isManualKode ? form.value.kode_transaksi : null
      };

      if (modalMode.value === 'edit') {
        await axios.put(`/api/expenses/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire('Berhasil', 'Data pengeluaran berhasil diperbarui', 'success');
      } else {
        await axios.post('/api/expenses', payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire('Berhasil', 'Data pengeluaran berhasil ditambahkan', 'success');
      }

      showModal.value = false;
      await fetchData();
    } catch (err) {
      console.error('Error submitting form:', err);
      Swal.fire('Error', 'Terjadi kesalahan saat menyimpan data', 'error');
    }
  }

  const handleDelete = async (id) => {
    const konfirmasi = await Swal.fire({
      title: 'Yakin ingin menghapus?',
      text: 'Data yang dihapus tidak bisa dikembalikan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Hapus',
      cancelButtonText: 'Batal'
    });

    if (konfirmasi.isConfirmed) {
      try {
        const token = sessionStorage.getItem('token');
        await axios.delete(`/api/expenses/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        });
        Swal.fire('Berhasil', 'Data berhasil dihapus', 'success');
        await fetchData();
      } catch (err) {
        Swal.fire('Gagal', 'Tidak dapat menghapus data', 'error');
      }
    }
  }

  const handleProjectChange = (event) => {
    console.log('Project change event:', event);
    console.log('Selected value:', event.target.value);
    selectedProject.value = event.target.value;
    filterByProject();
  };

  onMounted(() => {
    console.log('Component mounted');
    console.log('jQuery loaded:', typeof $);
    console.log('DataTables plugin:', typeof $.fn.DataTable);
    fetchData();
  })
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
