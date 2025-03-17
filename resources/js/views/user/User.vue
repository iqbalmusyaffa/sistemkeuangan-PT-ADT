<template>
    <CRow>
      <CCol>
        <CCard>
          <CCardHeader>
            <CIcon icon="cil-list" /> Users
            <CButton color="primary" @click="openModal('tambah')" class="float-end">
              Tambah User
            </CButton>
          </CCardHeader>
          <CCardBody>
            <div v-if="error" class="alert alert-danger">{{ error }}</div>
            <div v-if="loading" class="alert alert-info">Loading...</div>
            <div class="w-100">
              <table ref="dataTableRef" class="display nowrap"></table>
            </div>
          </CCardBody>
        </CCard>
      </CCol>

      <!-- Modal Form -->
      <CModal :visible="showModal" @close="closeModal" :title="modalTitle">
        <CModalBody>
          <CForm @submit.prevent="handleSubmit">
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="name">Name</CFormLabel>
                <CFormInput v-model="name" id="name" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="email">Email</CFormLabel>
                <CFormInput v-model="email" id="email" type="email" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="username">Username</CFormLabel>
                <CFormInput v-model="username" id="username" required />
              </CCol>
              <CCol md="6">
                <CFormLabel for="password">Password</CFormLabel>
                <CFormInput v-model="password" id="password" type="password" v-if="modalMode === 'tambah'" required />
              </CCol>
            </CRow>
            <CRow class="mb-3">
              <CCol md="6">
                <CFormLabel for="role">Role</CFormLabel>
                <CFormSelect v-model="role" id="role">
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                  <option value="stafkeuangan">Staf Keuangan</option>
                  <option value="owner">Owner</option>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel for="profile_picture">Profile Picture URL</CFormLabel>
                <CFormInput v-model="profile_picture" id="profile_picture" />
              </CCol>
            </CRow>
            <CButton type="submit" color="primary">{{ modalButtonText }}</CButton>
          </CForm>
        </CModalBody>
      </CModal>
    </CRow>
  </template>

  <script setup>
  import { ref, onMounted, nextTick } from "vue";
  import axios from "axios";
  import $ from "jquery";
  import Swal from 'sweetalert2';
  import "datatables.net-dt/css/dataTables.dataTables.min.css";
  import "datatables.net-responsive-dt/css/responsive.dataTables.min.css";
  import "datatables.net-responsive-dt";
  import "datatables.net-buttons-dt";

  const dataTableRef = ref(null);
  const name = ref("");
  const email = ref("");
  const username = ref("");
  const password = ref("");
  const role = ref("user");
  const profile_picture = ref("");
  const users = ref([]);
  const error = ref("");
  const loading = ref(false);
  const showModal = ref(false);
  const modalTitle = ref("Tambah User");
  const modalButtonText = ref("Simpan");
  const modalMode = ref("tambah"); // 'tambah' or 'edit'
  const editingId = ref(null);

  const fetchUsers = async () => {
    loading.value = true;
    error.value = "";
    try {
      const token = localStorage.getItem("token");
      const response = await axios.get("/api/users", {
        headers: { Authorization: `Bearer ${token}` },
      });
      users.value = response.data;
      nextTick(() => initDataTable());
    } catch (err) {
      error.value = "Gagal memuat pengguna.";
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Gagal memuat pengguna.',
      });
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
    if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
      $(dataTableRef.value).DataTable().destroy(); // Hapus instance DataTables yang lama
    }

    $(dataTableRef.value).DataTable({
      data: users.value,
      columns: [
        {
          title: "No",
          data: null,
          orderable: false,
          render: function (data, type, row, meta) {
            return meta.row + 1; // Nomor urut dimulai dari 1
          },
        },
        { title: "Name", data: "name" },
        { title: "Email", data: "email" },
        { title: "Username", data: "username" },
        { title: "Role", data: "role" },
        {
          title: "Aksi",
          data: null,
          orderable: false,
          render: function (data, type, row) {
            return `
              <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button>
              <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>
            `;
          },
        },
      ],
      responsive: true,
      scrollX: true,
      destroy: true,
    });

    // Event listener for edit button
    $(dataTableRef.value).on("click", ".edit-btn", function () {
      const id = $(this).data("id");
      const user = users.value.find((usr) => usr.id == id);
      if (user) openModal('edit', user);
    });

    // Event listener for delete button
    $(dataTableRef.value).on("click", ".delete-btn", function () {
      const id = $(this).data("id");
      deleteUser(id);
    });
  };

  const openModal = (mode, user = null) => {
    modalMode.value = mode;
    if (mode === 'edit' && user) {
      // Editing an existing user
      name.value = user.name;
      email.value = user.email;
      username.value = user.username;
      role.value = user.role;
      profile_picture.value = user.profile_picture;
      editingId.value = user.id;
      modalTitle.value = "Edit User";
      modalButtonText.value = "Update";
    } else {
      // Adding a new user
      name.value = "";
      email.value = "";
      username.value = "";
      password.value = "";
      role.value = "user";
      profile_picture.value = "";
      editingId.value = null;
      modalTitle.value = "Tambah User";
      modalButtonText.value = "Simpan";
    }
    showModal.value = true;
  };

  const closeModal = () => {
    showModal.value = false;
  };

  const handleSubmit = async () => {
    loading.value = true;
    error.value = "";

    try {
      const token = localStorage.getItem("token");
      const payload = {
        name: name.value,
        email: email.value,
        username: username.value,
        role: role.value,
        profile_picture: profile_picture.value,
      };

      if (modalMode.value === 'edit') {
        if (password.value) payload.password = password.value; // Only send password if editing
        await axios.put(`/api/users/${editingId.value}`, payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'User berhasil diperbarui.',
        });
      } else {
        payload.password = password.value; // Send password for new user
        await axios.post("/api/users", payload, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'User berhasil ditambahkan.',
        });
      }
      showModal.value = false;
      await fetchUsers();
      initDataTable(); // Reinitialize DataTables after data changes
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Terjadi kesalahan, silakan coba lagi.',
      });
    } finally {
      loading.value = false;
    }
  };

  const deleteUser = async (id) => {
    const result = await Swal.fire({
      title: 'Apakah Anda yakin?',
      text: "Anda tidak dapat mengembalikan data ini!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, hapus!',
      cancelButtonText: 'Batal',
    });

    if (result.isConfirmed) {
      try {
        const token = localStorage.getItem("token");
        await axios.delete(`/api/users/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          text: 'User berhasil dihapus.',
        });
        fetchUsers();
      } catch (err) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Gagal menghapus user.',
        });
      }
    }
  };

  onMounted(fetchUsers);
  </script>

  <style scoped>
  .w-100 {
    width: 100%;
    overflow-x: auto; /* Memungkinkan tabel di-scroll horizontal */
  }

  .dataTables_wrapper {
    overflow-x: auto; /* Memastikan wrapper DataTables dapat di-scroll */
  }

  table.display {
    width: 100% !important; /* Pastikan tabel mengambil lebar penuh */
  }
  </style>
