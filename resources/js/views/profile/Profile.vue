<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
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
    const response = await axios.get("/api/profile", {
      headers: {
        Authorization: `Bearer ${localStorage.getItem("token")}`, // Pastikan token tersimpan saat login
      },
    });
    user.value = response.data;
  } catch (err) {
    error.value = "Gagal mengambil data user.";
  } finally {
    loading.value = false;
  }
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
          </div>
          <CForm>
            <CFormInput label="Username" v-model="user.username" readonly />
            <!-- <CFormInput label="Role" v-model="user.role" readonly /> -->
            <!-- <CFormInput label="Status" :value="user.status ? 'Aktif' : 'Nonaktif'" readonly /> -->
            <CButton color="primary" class="mt-3">Edit Profile</CButton>
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
