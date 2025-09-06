<template>
  <CRow>
    <CCol>
      <CCard>
        <CCardHeader class="d-flex align-items-center">
          <font-awesome-icon icon="history" class="me-2 text-primary" />
          <h5 class="mb-0">Riwayat Aktivitas</h5>
        </CCardHeader>
        <CCardBody>
          <div v-if="logError" class="alert alert-danger">{{ logError }}</div>
          <div v-if="logLoading" class="text-center py-3 text-muted">
            <font-awesome-icon icon="spinner" spin /> Memuat aktivitas...
          </div>

          <div class="w-100">
            <table ref="logTableRef" class="display nowrap w-100"></table>
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
import dayjs from "dayjs";
import 'bootstrap/dist/css/bootstrap.min.css'

const logTableRef = ref(null);
const activityLogs = ref([]);
const logLoading = ref(false);
const logError = ref("");
const pagination = ref({});

// Fungsi bantu
const classBasename = (modelType) => modelType?.split("\\").pop() || "";
const formatTime = (datetime) =>
  dayjs(datetime).format("DD MMMM YYYY [pukul] HH:mm");

const actionBadgeColor = (action) => {
  switch (action.toLowerCase()) {
    case "create":
      return "success";
    case "update":
      return "warning";
    case "delete":
      return "danger";
    default:
      return "secondary";
  }
};

// Fetch logs
const fetchActivityLogs = (page = 1) => {
  logLoading.value = true;
  logError.value = "";

  const token = sessionStorage.getItem("token");
  axios
    .get(`/api/activity-log?page=${page}`, {
      headers: { Authorization: `Bearer ${token}` },
    })
    .then((res) => {
      activityLogs.value = res.data.data;
      pagination.value = {
        current_page: res.data.current_page,
        last_page: res.data.last_page,
      };
      nextTick(() => {
        initLogDataTable();
      });
    })
    .catch(() => {
      logError.value = "Gagal memuat activity log.";
      Swal.fire({
        icon: "error",
        title: "Oops...",
        text: "Gagal memuat activity log.",
      });
    })
    .finally(() => {
      logLoading.value = false;
    });
};

// Init DataTable
const initLogDataTable = () => {
  if (logTableRef.value && $.fn.DataTable.isDataTable(logTableRef.value)) {
    $(logTableRef.value).DataTable().clear().rows.add(activityLogs.value).draw();
    return;
  }

  $(logTableRef.value).DataTable({
    data: activityLogs.value,
    columns: [
      {
        title: "#",
        data: null,
        orderable: false,
        render: (data, type, row, meta) => meta.row + 1,
        className: "text-center",
      },
      {
        title: "Aktivitas",
        data: null,
        render: (data, type, row) => {
        const badge = `<span class="badge text-bg-${actionBadgeColor(
  row.action
)} text-uppercase">${row.action}</span>`;

          return `
            <div class="d-flex flex-column">
              <small><i class="fas fa-user text-secondary me-1"></i> ${row.user_name}</small>
              <div>
                ${badge} <strong>${classBasename(row.model_type)}</strong> ID ${row.model_id}
              </div>
              <small class="text-muted"><i class="fas fa-clock me-1"></i> ${formatTime(
                row.created_at
              )}</small>
            </div>
          `;
        },
      },
    ],
    responsive: true,
    scrollX: true,
    destroy: true,
    language: {
      emptyTable: "Belum ada aktivitas",
      search: "Cari:",
      paginate: { next: "→", previous: "←" },
    },
  });
};

onMounted(() => {
  fetchActivityLogs();
});
  </script>
<style>
.badge.bg-success { background-color: #198754 !important; color: white; }
.badge.bg-warning { background-color: #ffc107 !important; color: black; }
.badge.bg-danger  { background-color: #dc3545 !important; color: white; }
.badge.bg-secondary { background-color: #6c757d !important; color: white; }
</style>
