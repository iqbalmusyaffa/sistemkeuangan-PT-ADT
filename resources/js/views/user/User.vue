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
                  <option value="admin">Admin</option>
                  <option value="superadmin">Super Admin</option>
                </CFormSelect>
              </CCol>
              <CCol md="6">
                <CFormLabel for="profile_picture">Profile Picture</CFormLabel>
                <div v-if="modalMode === 'edit' && currentProfilePicture" class="mb-2">
                  <img :src="currentProfilePicture" width="80" height="80" class="rounded-circle img-thumbnail" @error="$event.target.src='/default-avatar.png'" />
                </div>
                <CFormInput
                  type="file"
                  id="profile_picture"
                  @change="handleFileUpload"
                  accept="image/*"
                  :required="modalMode === 'tambah'"
                />
                <small class="text-muted">Max size 2MB (JPEG, PNG, JPG)</small>
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
const users = ref([]);
const error = ref("");
const loading = ref(false);
const showModal = ref(false);
const modalTitle = ref("Tambah User");
const modalButtonText = ref("Simpan");
const modalMode = ref("tambah");
const editingId = ref(null);
const profilePictureFile = ref(null);
const currentProfilePicture = ref("");

  const fetchUsers = async () => {
    loading.value = true;
    error.value = "";
    try {
      const token = sessionStorage.getItem("token");
      const userRole = sessionStorage.getItem("role");

      if (userRole !== 'superadmin') {
        Swal.fire({
          icon: 'error',
          title: 'Akses Ditolak',
          text: 'Anda tidak memiliki akses untuk melihat daftar pengguna.',
        });
        return;
      }

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
        text: err.response?.data?.message || 'Gagal memuat pengguna.',
      });
    } finally {
      loading.value = false;
    }
  };

  const initDataTable = () => {
  if ($.fn.DataTable.isDataTable(dataTableRef.value)) {
    $(dataTableRef.value).DataTable().destroy();
  }

  $(dataTableRef.value).DataTable({
    data: users.value,
    columns: [
      { title: "No", data: null, render: (data, type, row, meta) => meta.row + 1 },
      { title: "Name", data: "name" },
      { title: "Email", data: "email" },
      { title: "Username", data: "username" },
      { title: "Role", data: "role" },
      {
        title: "Status",
        data: "status",
        render: (data, type, row) => {
          let statusClass = 'secondary';
          switch(data) {
            case 'active':
              statusClass = 'success';
              break;
            case 'inactive':
              statusClass = 'warning';
              break;
            case 'suspended':
              statusClass = 'danger';
              break;
          }
          return `<span class="badge bg-${statusClass}">${data}</span>`;
        }
      },
      {
        title: "Profile Picture",
        data: "profile_picture",
        render: (data) => {
          if (!data) {
            return '<img src="/default-avatar.png" width="50" height="50" class="rounded-circle" />';
          }
          return `<img src="/storage/profile_pictures/${data}" width="50" height="50" class="rounded-circle" onerror="this.src='/default-avatar.png'" />`;
        },
      },
      {
        title: "Aksi",
        data: null,
        render: (data, type, row) => {
          const statusButtons = `
            <div class="btn-group">
              <button class="btn btn-sm btn-success status-btn" data-id="${row.id}" data-status="active">Aktif</button>
              <button class="btn btn-sm btn-warning status-btn" data-id="${row.id}" data-status="inactive">Tidak Aktif</button>
              <button class="btn btn-sm btn-danger status-btn" data-id="${row.id}" data-status="suspended">Suspend</button>
            </div>
          `;
          return `${statusButtons} <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button> <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>`;
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

    // Event listener for status buttons
    $(dataTableRef.value).on("click", ".status-btn", function () {
      const id = $(this).data("id");
      const newStatus = $(this).data("status");
      updateUserStatus(id, newStatus);
    });
  };

  const handleFileUpload = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  if (!file.type.match(/^image\/(jpeg|png|jpg)$/)) {
    Swal.fire({
      icon: 'error',
      title: 'Format file tidak valid',
      text: 'Hanya file JPEG, PNG, atau JPG yang diizinkan',
    });
    event.target.value = '';
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    Swal.fire({
      icon: 'error',
      title: 'Ukuran file terlalu besar',
      text: 'Maksimal ukuran file 2MB',
    });
    event.target.value = '';
    return;
  }

  profilePictureFile.value = file;
  currentProfilePicture.value = URL.createObjectURL(file);
};

const openModal = (mode, user = null) => {
  modalMode.value = mode;
  profilePictureFile.value = null;

  if (mode === 'edit' && user) {
    name.value = user.name;
    email.value = user.email;
    username.value = user.username;
    role.value = user.role;
    password.value = ''; // Reset password saat edit
    currentProfilePicture.value = user.profile_picture
      ? `/storage/profile_pictures/${user.profile_picture}`
      : '/default-avatar.png';
    editingId.value = user.id;
    modalTitle.value = "Edit User";
    modalButtonText.value = "Update";
  } else {
    resetForm();
    modalTitle.value = "Tambah User";
    modalButtonText.value = "Simpan";
  }
  showModal.value = true;
};

const resetForm = () => {
  name.value = "";
  email.value = "";
  username.value = "";
  password.value = "";
  role.value = "user";
  currentProfilePicture.value = "";
  profilePictureFile.value = null;
  editingId.value = null;
  if (document.getElementById('profile_picture')) {
    document.getElementById('profile_picture').value = '';
  }
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = "";

  // Validasi form
  if (!name.value.trim()) {
    error.value = "Nama wajib diisi!";
    loading.value = false;
    return;
  }

  if (!email.value.trim()) {
    error.value = "Email wajib diisi!";
    loading.value = false;
    return;
  }

  if (!username.value.trim()) {
    error.value = "Username wajib diisi!";
    loading.value = false;
    return;
  }

  if (modalMode.value === 'tambah' && !password.value) {
    error.value = "Password wajib diisi!";
    loading.value = false;
    return;
  }

  try {
    const token = sessionStorage.getItem("token");
    const userRole = sessionStorage.getItem("role");

    if (userRole !== 'superadmin') {
      Swal.fire({
        icon: 'error',
        title: 'Akses Ditolak',
        text: 'Anda tidak memiliki akses untuk mengelola pengguna.',
      });
      return;
    }

    const formData = new FormData();
    formData.append('name', name.value.trim());
    formData.append('email', email.value.trim());
    formData.append('username', username.value.trim());
    formData.append('role', role.value);
    formData.append('status', 'active');

    // Hanya tambahkan password jika diisi (opsional saat edit)
    if (password.value) {
      formData.append('password', password.value);
    }

    // Hanya tambahkan profile picture jika ada file baru
    if (profilePictureFile.value) {
      formData.append('profile_picture', profilePictureFile.value);
    }

    const config = {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'multipart/form-data'
      }
    };

    if (modalMode.value === 'edit') {
      await axios.put(`/api/users/${editingId.value}`, formData, config);
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data user berhasil diperbarui',
      });
    } else {
      // Validasi tambahan untuk mode tambah
      if (!profilePictureFile.value) {
        error.value = "Foto profil wajib diisi!";
        loading.value = false;
        return;
      }

      await axios.post("/api/users", formData, config);
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'User baru berhasil ditambahkan',
      });
    }

    closeModal();
    await fetchUsers();
  } catch (err) {
    // console.error("Error submitting form:", err);
    const errorMessage = err.response?.data?.message || err.message || "Terjadi kesalahan saat menyimpan data.";
    Swal.fire({
      icon: "error",
      title: "Oops...",
      text: errorMessage
    });
  } finally {
    loading.value = false;
  }
};

const deleteUser = async (id) => {
  const userRole = sessionStorage.getItem("role");

  if (userRole !== 'superadmin') {
    Swal.fire({
      icon: 'error',
      title: 'Akses Ditolak',
      text: 'Anda tidak memiliki akses untuk menghapus pengguna.',
    });
    return;
  }

  const result = await Swal.fire({
    title: 'Apakah Anda yakin?',
    text: "Data yang dihapus tidak dapat dikembalikan!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, hapus!'
  });

  if (result.isConfirmed) {
    try {
      const token = sessionStorage.getItem("token");
      await axios.delete(`/api/users/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
      });
      Swal.fire('Berhasil!', 'User telah dihapus.', 'success');
      fetchUsers();
    } catch (err) {
      handleError('Gagal menghapus user', err);
    }
  }
};

const updateUserStatus = async (id, newStatus) => {
  const statusMessages = {
    active: 'mengaktifkan',
    inactive: 'menonaktifkan',
    suspended: 'menangguhkan'
  };

  const result = await Swal.fire({
    title: `Ubah Status User?`,
    text: `Anda akan ${statusMessages[newStatus]} user ini`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Ubah Status!'
  });

  if (result.isConfirmed) {
    try {
      const token = sessionStorage.getItem("token");
      const userRole = sessionStorage.getItem("role");

      if (userRole !== 'superadmin') {
        Swal.fire({
          icon: 'error',
          title: 'Akses Ditolak',
          text: 'Anda tidak memiliki akses untuk mengubah status pengguna.',
        });
        return;
      }

      const formData = new FormData();
      formData.append('status', newStatus);

      await axios.put(`/api/users/${id}`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          'Content-Type': 'multipart/form-data'
        }
      });

      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: `Status user berhasil diubah menjadi ${newStatus}`,
      });

      fetchUsers();
    } catch (err) {
      handleError('Gagal mengubah status user', err);
    }
  }
};

const handleError = (message, error) => {
  console.error(error);
  const errorMessage = error.response?.data?.message || error.message || 'Terjadi kesalahan';
  Swal.fire({
    icon: 'error',
    title: 'Oops...',
    text: `${message}: ${errorMessage}`,
  });
};

onMounted(() => {
  const userRole = sessionStorage.getItem("role");
  if (userRole === 'superadmin') {
    fetchUsers();
  } else {
    Swal.fire({
      icon: 'error',
      title: 'Akses Ditolak',
      text: 'Anda tidak memiliki akses untuk halaman ini.',
    });
  }
});
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
