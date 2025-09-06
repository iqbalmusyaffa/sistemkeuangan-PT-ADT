<template>
    <CRow>
      <CCol sm="12" md="8" lg="6" class="mx-auto">
        <CCard>
          <CCardHeader class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Profil Pengguna</h4>
           <CButton color="primary" size="sm" @click="openEditModal">
  <font-awesome-icon icon="pencil-alt" class="me-2" />
  Edit Profil
</CButton>

            
          </CCardHeader>

          <!-- Loading -->
          <CCardBody v-if="loading">
            <div class="text-center py-4">
    <font-awesome-icon icon="spinner" spin size="2x" class="text-primary" />
            </div>
          </CCardBody>

          <!-- Error -->
          <CCardBody v-else-if="error">
            <div class="alert alert-danger" role="alert">
              {{ error }}
            </div>
          </CCardBody>

          <!-- Data User -->
          <CCardBody v-else>
            <!-- <div class="text-center mb-4">
              <div class="position-relative d-inline-block mb-3">
                <CAvatar :src="profilePictureUrl" size="xl" class="profile-avatar" />
                <div class="position-absolute bottom-0 end-0">
                 <label for="profile-upload" class="btn btn-sm btn-primary rounded-circle" style="width: 32px; height: 32px">
  <font-awesome-icon icon="pencil-alt" size="sm" />
</label>

                  <input
                    type="file"
                    id="profile-upload"
                    class="d-none"
                    accept="image/*"
                    @change="handleQuickImageUpload"
                  />
                </div>
              </div>
              <h4 class="mb-1">{{ user.name }}</h4>
              <p class="text-muted mb-1">{{ user.email }}</p>
              <CBadge :color="user.role === 'superadmin' ? 'danger' : 'primary'" class="text-uppercase">
                {{ user.role }}
              </CBadge>
            </div> -->
<div class="text-center mb-4">
  <div class="profile-avatar-wrapper mb-3">
    <CAvatar :src="profilePictureUrl" size="xl" class="profile-avatar" />
    <label for="profile-upload" class="avatar-overlay" title="Ubah foto">
      <font-awesome-icon icon="pencil-alt" size="sm" />
    </label>
    <input
      type="file"
      id="profile-upload"
      class="d-none"
      accept="image/*"
      @change="handleQuickImageUpload"
    />
  </div>
    <!-- Tambahkan ini -->
  <p v-if="selectedFileName" class="text-muted small mt-2">
    File dipilih: {{ selectedFileName }}
  </p>
  <h4 class="mb-1">{{ user.name }}</h4>
  <p class="text-muted mb-1">{{ user.email }}</p>
  <CBadge :color="user.role === 'superadmin' ? 'danger' : 'primary'" class="text-uppercase">
    {{ user.role }}
  </CBadge>
</div>

            <div class="profile-info mt-4">
              <h5 class="mb-3">Informasi Profil</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="profile-field">
                    <label class="text-muted">Nama Lengkap</label>
                    <p class="mb-0">{{ user.name }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="profile-field">
                    <label class="text-muted">Username</label>
                    <p class="mb-0">{{ user.username }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="profile-field">
                    <label class="text-muted">Email</label>
                    <p class="mb-0">{{ user.email }}</p>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="profile-field">
                    <label class="text-muted">Status</label>
                    <CBadge :color="user.status === 'active' ? 'success' : 'danger'">
                      {{ user.status }}
                    </CBadge>
                  </div>
                </div>
              </div>
            </div>
          </CCardBody>
        </CCard>
      </CCol>
    </CRow>

    <!-- Modal Edit Profile -->
    <CModal
      :visible="showEditModal"
      @close="closeEditModal"
      title="Edit Profil"
      size="lg"
    >
      <CModalHeader>
        <h5 class="mb-0">Edit Profil</h5>
      </CModalHeader>
      <CModalBody>
        <CForm @submit.prevent="handleQuickUpdate" class="row g-3">
          <CCol md="6">
            <CFormLabel for="name">Nama Lengkap</CFormLabel>
            <CFormInput
              id="name"
              v-model="quickEditForm.name"
              :class="{ 'is-invalid': validationErrors.name }"
              required
            />
            <CFormFeedback invalid v-if="validationErrors.name">
              {{ validationErrors.name[0] }}
            </CFormFeedback>
          </CCol>

          <CCol md="6">
            <CFormLabel for="username">Username</CFormLabel>
            <CFormInput
              id="username"
              v-model="quickEditForm.username"
              :class="{ 'is-invalid': validationErrors.username }"
              required
            />
            <CFormFeedback invalid v-if="validationErrors.username">
              {{ validationErrors.username[0] }}
            </CFormFeedback>
          </CCol>

          <CCol md="6">
            <CFormLabel for="email">Email</CFormLabel>
            <CFormInput
              id="email"
              v-model="quickEditForm.email"
              type="email"
              :class="{ 'is-invalid': validationErrors.email }"
              required
            />
            <CFormFeedback invalid v-if="validationErrors.email">
              {{ validationErrors.email[0] }}
            </CFormFeedback>
          </CCol>

          <CCol md="6">
            <CFormLabel for="password">Password (Opsional)</CFormLabel>
            <CFormInput
              id="password"
              v-model="quickEditForm.password"
              type="password"
              :class="{ 'is-invalid': validationErrors.password }"
            />
            <CFormText>Kosongkan jika tidak ingin mengubah password</CFormText>
            <CFormFeedback invalid v-if="validationErrors.password">
              {{ validationErrors.password[0] }}
            </CFormFeedback>
          </CCol>

          <!-- Role dan Status hanya untuk superadmin -->
          <template v-if="isSuperAdmin">
            <CCol md="6">
              <CFormLabel for="role">Role</CFormLabel>
              <CFormSelect
                id="role"
                v-model="quickEditForm.role"
                :disabled="user.id === currentUser.id"
              >
                <option value="admin">Admin</option>
                <option value="superadmin">Superadmin</option>
              </CFormSelect>
              <CFormText v-if="user.id === currentUser.id">
                Superadmin tidak dapat mengubah rolenya sendiri
              </CFormText>
            </CCol>

            <CCol md="6">
              <CFormLabel for="status">Status</CFormLabel>
              <CFormSelect
                id="status"
                v-model="quickEditForm.status"
                :disabled="user.id === currentUser.id"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </CFormSelect>
              <CFormText v-if="user.id === currentUser.id">
                Superadmin tidak dapat menonaktifkan akunnya sendiri
              </CFormText>
            </CCol>
          </template>
        </CForm>
      </CModalBody>
      <CModalFooter>
        <CButton color="secondary" @click="closeEditModal">
          Batal
        </CButton>
        <CButton color="primary" @click="handleQuickUpdate" :disabled="loading">
   <font-awesome-icon v-if="loading" icon="spinner" spin class="me-1" />
          Simpan Perubahan
        </CButton>
      </CModalFooter>
    </CModal>
  </template>

  <script setup>
  import { ref, onMounted, computed } from "vue";
  import axios from "axios";
  import Swal from "sweetalert2";
  import { useAuthStore } from "@/stores/auth";

  // State
  const loading = ref(false);
  const error = ref("");
  const user = ref({});
  const validationErrors = ref({});
  const auth = useAuthStore();
  const showEditModal = ref(false);
const selectedFileName = ref("");
  const baseStorageUrl = import.meta.env.VITE_API_BASE_URL + "/storage/profile_pictures/";

  // Quick edit form untuk update langsung
  const quickEditForm = ref({
    name: "",
    username: "",
    email: "",
    password: "",
    role: "",
    status: "",
  });

  // Computed
  const currentUser = computed(() => auth.user);
  const isSuperAdmin = computed(() => currentUser.value?.role === 'superadmin');

  const profilePictureUrl = computed(() => {
    if (!user.value.profile_picture) {
      return new URL("@/assets/images/avatars/2.jpg", import.meta.url).href;
    }
    return user.value.profile_picture.startsWith('http')
      ? user.value.profile_picture
      : `${baseStorageUrl}${user.value.profile_picture}`;
  });

  // Methods
  const fetchUserProfile = async () => {
    loading.value = true;
    error.value = "";
    validationErrors.value = {};

    try {
      const token = sessionStorage.getItem("token");
      if (!token) {
        error.value = "Token tidak ditemukan, harap login kembali.";
        return;
      }

      const res = await axios.get("/api/profile", {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      user.value = res.data;

      // Update quick edit form
      resetForm();
    } catch (err) {
      error.value = "Gagal mengambil data profil.";
    } finally {
      loading.value = false;
    }
  };

  const openEditModal = () => {
    resetForm();
    showEditModal.value = true;
  };

  const closeEditModal = () => {
    showEditModal.value = false;
    validationErrors.value = {};
  };

  const handleQuickUpdate = async () => {
    loading.value = true;
    validationErrors.value = {};

    try {
      const token = sessionStorage.getItem("token");
      const formData = new FormData();

      // Append form data
      formData.append("name", quickEditForm.value.name);
      formData.append("username", quickEditForm.value.username);
      formData.append("email", quickEditForm.value.email);

      if (quickEditForm.value.password) {
        formData.append("password", quickEditForm.value.password);
      }

      // Append role dan status jika superadmin
      if (isSuperAdmin.value && user.value.id !== currentUser.value.id) {
        if (quickEditForm.value.role) formData.append("role", quickEditForm.value.role);
        if (quickEditForm.value.status) formData.append("status", quickEditForm.value.status);
      }

      await axios.post(`/api/users/${user.value.id}?_method=PUT`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "multipart/form-data",
        },
      });

      Swal.fire({
        icon: "success",
        title: "Berhasil!",
        text: "Profil berhasil diperbarui.",
      });

      // Refresh profile data
      await fetchUserProfile();
      // Update auth store
      await auth.fetchUser();
      // Tutup modal
      closeEditModal();
    } catch (err) {
      if (err.response?.data?.errors) {
        validationErrors.value = err.response.data.errors;
      } else {
        Swal.fire({
          icon: "error",
          title: "Gagal!",
          text: "Terjadi kesalahan saat memperbarui profil.",
        });
      }
    } finally {
      loading.value = false;
    }
  };

  // const handleQuickImageUpload = async (event) => {
  //   const file = event.target.files[0];
  //   if (!file) return;

  //   // Validate file
  //   if (!file.type.match(/^image\/(jpeg|png|jpg)$/)) {
  //     Swal.fire({
  //       icon: "error",
  //       title: "Format file tidak valid",
  //       text: "Hanya file JPEG, PNG, atau JPG yang diizinkan",
  //     });
  //     event.target.value = "";
  //     return;
  //   }

  //   if (file.size > 2 * 1024 * 1024) {
  //     Swal.fire({
  //       icon: "error",
  //       title: "Ukuran file terlalu besar",
  //       text: "Maksimal ukuran file 2MB",
  //     });
  //     event.target.value = "";
  //     return;
  //   }

  //   loading.value = true;
  //   try {
  //     const token = sessionStorage.getItem("token");
  //     const formData = new FormData();
  //     formData.append("profile_picture", file);

  //     await axios.post(`/api/users/${user.value.id}?_method=PUT`, formData, {
  //       headers: {
  //         Authorization: `Bearer ${token}`,
  //         "Content-Type": "multipart/form-data",
  //       },
  //     });

  //     Swal.fire({
  //       icon: "success",
  //       title: "Berhasil!",
  //       text: "Foto profil berhasil diperbarui.",
  //     });

  //     // Refresh profile data
  //     await fetchUserProfile();
  //     // Update auth store
  //     await auth.fetchUser();
  //   } catch (err) {
  //     Swal.fire({
  //       icon: "error",
  //       title: "Gagal!",
  //       text: "Terjadi kesalahan saat memperbarui foto profil.",
  //     });
  //   } finally {
  //     loading.value = false;
  //     event.target.value = ""; // Reset input file
  //   }
  // };
const handleQuickImageUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  selectedFileName.value = file.name; // <- ini untuk tampilkan preview

  // Validasi format
  if (!file.type.match(/^image\/(jpeg|png|jpg)$/)) {
    Swal.fire({
      icon: "error",
      title: "Format file tidak valid",
      text: "Hanya file JPEG, PNG, atau JPG yang diizinkan",
    });
    event.target.value = "";
    selectedFileName.value = "";
    return;
  }

  if (file.size > 2 * 1024 * 1024) {
    Swal.fire({
      icon: "error",
      title: "Ukuran file terlalu besar",
      text: "Maksimal ukuran file 2MB",
    });
    event.target.value = "";
    selectedFileName.value = "";
    return;
  }

  loading.value = true;
  try {
    const token = sessionStorage.getItem("token");
    const formData = new FormData();
    formData.append("profile_picture", file);

    await axios.post(`/api/users/${user.value.id}?_method=PUT`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "multipart/form-data",
      },
    });

    Swal.fire({
      icon: "success",
      title: "Berhasil!",
      text: "Foto profil berhasil diperbarui.",
    });

    await fetchUserProfile();
    await auth.fetchUser();
  } catch (err) {
    Swal.fire({
      icon: "error",
      title: "Gagal!",
      text: "Terjadi kesalahan saat memperbarui foto profil.",
    });
  } finally {
    loading.value = false;
    event.target.value = ""; // reset input
  }
};

  const resetForm = () => {
    quickEditForm.value = {
      name: user.value.name || "",
      username: user.value.username || "",
      email: user.value.email || "",
      password: "",
      role: user.value.role || "",
      status: user.value.status || "",
    };
    validationErrors.value = {};
  };

  // Lifecycle
  onMounted(fetchUserProfile);
  </script>

  <style scoped>
  .profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
.profile-avatar-wrapper {
  position: relative;
  display: inline-block;
}

.profile-avatar-wrapper:hover .avatar-overlay {
  opacity: 1;
}

.avatar-overlay {
  position: absolute;
  bottom: 0;
  right: 0;
  background: #0d6efd;
  color: white;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.3s ease;
  opacity: 0;
  cursor: pointer;
}

  .btn-sm.rounded-circle {
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .profile-field {
    margin-bottom: 1rem;
  }

  .profile-field label {
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
    display: block;
  }

  .profile-field p {
    font-weight: 500;
  }
  </style>
