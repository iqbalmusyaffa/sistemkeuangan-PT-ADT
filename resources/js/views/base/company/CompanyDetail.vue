<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-factory" class="me-2" />
            Detail Perusahaan
          </CCardHeader>
          <CCardBody>
            <div v-if="loading" class="alert alert-info">Memuat data...</div>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>

            <div v-if="company">
              <h5>{{ company.nama_lengkap }}</h5>
              <p>Email: {{ company.email }}</p>
              <p>Telp: {{ company.no_telp }}</p>
              <p>Alamat: {{ company.alamat }}</p>

              <hr />

              <h6>Daftar Pemasukan</h6>
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Jumlah</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(income, index) in incomes" :key="income.id">
                    <td>{{ index + 1 }}</td>
                    <td>Rp {{ formatCurrency(income.jumlah) }}</td>
                    <td>{{ income.tanggal }}</td>
                    <td>{{ income.keterangan }}</td>
                  </tr>
                </tbody>
                <tfoot v-if="incomes.length > 0">
                  <tr>
                    <td colspan="1"><strong>Total</strong></td>
                    <td colspan="3"><strong>Rp {{ formatCurrency(totalIncome) }}</strong></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </template>

  <script setup>
  import { onMounted, ref } from 'vue'
  import { useRoute } from 'vue-router'
  import axios from 'axios'

  const route = useRoute()
  const company = ref(null)
  const incomes = ref([])
  const error = ref("")
  const loading = ref(false)

  const fetchData = async () => {
    loading.value = true
    try {
        const token = sessionStorage.getItem('token')
      const id = route.params.id

      const [companyRes, incomeRes] = await Promise.all([
        axios.get(`/api/companies/${id}`, {
          headers: { Authorization: `Bearer ${token}` }
        }),
        axios.get(`/api/companies/${id}/incomes`, {
          headers: { Authorization: `Bearer ${token}` }
        })
      ])

      company.value = companyRes.data
      incomes.value = incomeRes.data
    } catch (err) {
      error.value = "Gagal memuat data perusahaan atau pemasukan."
    } finally {
      loading.value = false
    }
  }

  const formatCurrency = (num) => {
    return new Intl.NumberFormat('id-ID').format(num)
  }

  const totalIncome = computed(() => {
    return incomes.value.reduce((total, income) => total + income.jumlah, 0)
  })

  onMounted(fetchData)
  </script>
