<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";
import { CCard, CCardBody, CCardHeader, CRow, CCol, CAvatar, CButton, CForm, CFormInput, CSpinner } from "@coreui/vue";

// State untuk menyimpan data user
const user = ref({
  name: "",
  email: "",
  username: "",
  profile_picture: "",
  role: "",
  status: "",
});

const loading = ref(true);
const error = ref(null);

// Fetch data user dari API Laravel
const fetchUserProfile = async () => {
  try {
    const token = sessionStorage.getItem("token");  // Using sessionStorage
    if (!token) {
      error.value = "Token tidak ditemukan, harap login kembali.";
      loading.value = false;
      return;
    }

    const response = await axios.get("/api/profile", {
      headers: {
        Authorization: `Bearer ${token}`,  // Token diambil dari sessionStorage
      },
    });

    // Set user data
    user.value = response.data;
  } catch (err) {
    error.value = "Gagal mengambil data user.";
  } finally {
    loading.value = false;
  }
};

// Edit Profile Function
const editProfile = () => {
  Swal.fire({
    title: 'Edit Profile',
    html: `
      <input type="text" id="name" class="swal2-input" value="${user.value.name}" placeholder="Name" />
      <input type="email" id="email" class="swal2-input" value="${user.value.email}" placeholder="Email" />
      <input type="text" id="username" class="swal2-input" value="${user.value.username}" placeholder="Username" />
    `,
    focusConfirm: false,
    preConfirm: () => {
      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const username = document.getElementById('username').value;

      // Validasi input
      if (!name || !email || !username) {
        Swal.showValidationMessage('Harap isi semua field');
        return false;
      }

      // Kirim request untuk memperbarui data
      return axios.put("/api/profile", {
        name: name,
        email: email,
        username: username,
      }, {
        headers: {
          Authorization: `Bearer ${sessionStorage.getItem("token")}`,
        },
      })
      .then((response) => {
        if (response.data.success) {
          Swal.fire('Profil berhasil diperbarui!', '', 'success');
          user.value = response.data.user;  // Update the local user data
        } else {
          Swal.fire('Gagal memperbarui profil', '', 'error');
        }
      })
      .catch((error) => {
        Swal.fire('Terjadi kesalahan', '', 'error');
      });
    }
  });
};

// Load data saat komponen dimount
onMounted(fetchUserProfile);
</script>

<template>
  <CRow>
    <CCol sm="12" md="8" lg="6" class="mx-auto">
      <CCard>
        <CCardHeader>
          <h4>Profil Pengguna</h4>
        </CCardHeader>
        <CCardBody v-if="loading">
          <CSpinner color="primary" />
        </CCardBody>
        <CCardBody v-else-if="error">
          <p class="text-danger">{{ error }}</p>
        </CCardBody>
        <CCardBody v-else>
          <div class="text-center">
            <CAvatar :src="`/storage/profile_pictures/${user.profile_picture}`" size="100" class="mb-3" />
            <h5>{{ user.name }}</h5>
            <p class="text-muted">{{ user.email }}</p>
            <p><strong>Role:</strong> {{ user.role }}</p>
            <p><strong>Status:</strong> {{ user.status === 'active' ? 'Aktif' : 'Nonaktif' }}</p>
          </div>
          <CForm>
            <CFormInput label="Username" v-model="user.username" readonly />
            <CButton color="primary" class="mt-3" @click="editProfile">Edit Profile</CButton>
          </CForm>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>

<style scoped>
.text-center {
  text-align: center;
}
</style>
