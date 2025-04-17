<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-info" class="me-2" />
            Detail Pengeluaran
          </CCardHeader>
          <CCardBody>
            <div class="w-100">
              <table ref="tableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted, nextTick } from 'vue'
  import { useRoute } from 'vue-router'
  import axios from 'axios'
  import $ from 'jquery'
  import 'datatables.net-dt/css/dataTables.dataTables.min.css'
  import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'
  import 'datatables.net-responsive-dt'

  const route = useRoute()
  const tableRef = ref(null)
  const expense = ref(null)

  const initDataTable = () => {
    $(tableRef.value).DataTable({
      data: [expense.value],
      columns: [
        { title: 'Kode Transaksi', data: 'kode_transaksi' },
        { title: 'Perusahaan', data: 'company.nama_lengkap' },
        { title: 'Kategori', data: 'category.nama_kategori' },
        {
          title: 'Jumlah',
          data: 'amount',
          render: (data) => `Rp ${parseFloat(data).toLocaleString('id-ID')}`
        },
        { title: 'Tanggal Transaksi', data: 'transaction_date' },
        {
          title: 'Status',
          data: 'status',
          render: (data) => data ? 'Lunas' : 'Pending'
        },
        {
          title: 'Deskripsi',
          data: 'description',
          render: (data) => data || '-'
        }
      ],
      responsive: true,
      scrollX: true,
      searching: false,
      paging: false,
      info: false,
      ordering: false
    })
  }

  onMounted(async () => {
    const token = sessionStorage.getItem('token')
    const id = route.params.id

    try {
      const res = await axios.get(`/api/expenses/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      expense.value = res.data.data
      await nextTick()
      initDataTable()
    } catch (err) {
      console.error('Gagal mengambil detail pengeluaran:', err)
    }
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
