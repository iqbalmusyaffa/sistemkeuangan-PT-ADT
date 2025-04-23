<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-history" /> Activity Log
          </CCardHeader>
          <CCardBody>
            <div v-if="logError" class="alert alert-danger">{{ logError }}</div>
            <div v-if="logLoading" class="alert alert-info">Loading activity log...</div>

            <div class="w-100">
              <table ref="logTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted, nextTick } from "vue";
  import axios from "axios";
  import $ from "jquery";
  import Swal from "sweetalert2";
  import "datatables.net-dt/css/dataTables.dataTables.min.css";
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
  import "datatables.net-responsive-dt";
  import dayjs from 'dayjs'

  const logTableRef = ref(null);
  const activityLogs = ref([]);
  const logLoading = ref(false);
  const logError = ref("");
  const pagination = ref({});
  const classBasename = (modelType) => modelType?.split('\\').pop() || '';
  const formatTime = (datetime) => dayjs(datetime).format('DD MMMM YYYY [pukul] HH:mm');
  const fetchActivityLogs = (page = 1) => {
    logLoading.value = true;
    logError.value = "";
    try {
        const token = sessionStorage.getItem("token");

        axios.get(`/api/activity-log?page=${page}`, {
            headers: { Authorization: `Bearer ${token}` },
        })
        .then(response => {
            activityLogs.value = response.data.data;  // Data from paginated response
            pagination.value = {
                current_page: response.data.current_page,
                last_page: response.data.last_page,
            };

            nextTick(() => {
                initLogDataTable();
            });
        })
        .catch(err => {
            logError.value = "Gagal memuat activity log.";
            Swal.fire({ icon: "error", title: "Oops...", text: "Gagal memuat activity log." });
        })
        .finally(() => {
            logLoading.value = false;
        });
    } catch (err) {
        logError.value = "Gagal memuat activity log.";
        Swal.fire({ icon: "error", title: "Oops...", text: "Gagal memuat activity log." });
    }
};


  const initLogDataTable = () => {
  nextTick(() => {
    // console.log("Initializing DataTable with data:", activityLogs.value);  // Log untuk memeriksa data yang dikirimkan ke DataTable
    if (logTableRef.value && !$.fn.DataTable.isDataTable(logTableRef.value)) {
      $(logTableRef.value).DataTable({
        data: activityLogs.value,
        columns: [
  { title: "No", data: null, orderable: false, render: (data, type, row, meta) => meta.row + 1 },
  { title: "User", data: "user_name" },
  {
    title: "Activity",
    data: null,
    render: (data, type, row) => {
      return `
        ${row.user_name} melakukan <strong>${row.action}</strong> pada
        <strong>${classBasename(row.model_type)}</strong> ID ${row.model_id}
        pada ${formatTime(row.created_at)}
      `;
    }
  },
//   { title: "Model Type", data: "model_type" }, // opsional kalau masih ingin tampilkan
//   { title: "Changes", data: "changes" },
//   { title: "Timestamp", data: "created_at" }, // opsional kalau masih ingin tampilkan
],

        responsive: true,
        scrollX: true,
        destroy: true,
      });
    }
  });
};


  onMounted(() => {
    fetchActivityLogs();  // Fetch data when component mounts
  });
  </script>
