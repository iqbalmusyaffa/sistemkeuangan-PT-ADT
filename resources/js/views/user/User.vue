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
              <CFormLabel for="profile_picture">Profile Picture</CFormLabel>
              <div v-if="modalMode === 'edit' && currentProfilePicture" class="mb-2">
                <img :src="currentProfilePicture" width="80" class="img-thumbnail" />
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
        title: "Profile Picture",
        data: "profile_picture",
        render: (data) => {
          // Assuming the `profile_picture` field only stores the file name
          const profileImageUrl = data ? `/storage/profile_pictures/${data}` : '/default-avatar.png'; // Use default image if not available
          return `<img src="${profileImageUrl}" width="50" height="50" />`;
        },
      },
      {
        title: "Aksi",
        data: null,
        render: (data, type, row) => `<button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">Edit</button> <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">Hapus</button>`,
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
  try {
    const token = localStorage.getItem("token");
    const formData = new FormData();

    formData.append('name', name.value);
    formData.append('email', email.value);
    formData.append('username', username.value);
    formData.append('role', role.value);

    if (modalMode.value === 'tambah') {
      formData.append('password', password.value);
      if (!profilePictureFile.value) {
        throw new Error('Foto profil wajib diisi');
      }
    }

    if (password.value) {
      formData.append('password', password.value);
    }

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
    } else {
      await axios.post("/api/users", formData, config);
    }

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: `User berhasil ${modalMode.value === 'edit' ? 'diperbarui' : 'ditambahkan'}`,
    });

    closeModal();
    await fetchUsers();
  } catch (err) {
    handleError(`Gagal ${modalMode.value === 'edit' ? 'mengupdate' : 'menambahkan'} user`, err);
  } finally {
    loading.value = false;
  }
};

const deleteUser = async (id) => {
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
      const token = localStorage.getItem("token");
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

const handleError = (message, error) => {
  console.error(error);
  const errorMessage = error.response?.data?.message || error.message || 'Terjadi kesalahan';
  Swal.fire({
    icon: 'error',
    title: 'Oops...',
    text: `${message}: ${errorMessage}`,
  });
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
