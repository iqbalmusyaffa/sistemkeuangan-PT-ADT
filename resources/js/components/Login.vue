<template>
  <div class="wrapper min-vh-100 d-flex flex-row align-items-center">
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="6" :sm="10" :xs="12">
          <CCard class="p-4 shadow">
            <CCardBody>
              <!-- Logo -->
              <div class="text-center mb-4">
                <!-- <img src="@/assets/auth0-logo.png" alt="Logo" style="height: 48px;" /> -->
              </div>

              <!-- Judul -->
              <h2 class="text-center mb-2">Login</h2>
              <p class="text-center text-muted mb-4" style="font-size: 0.95rem;">
                Masuk ke akun Anda untuk melanjutkan ke finara.
              </p>

              <!-- Form Login -->
              <CForm @submit.prevent="login">
                <CInputGroup class="mb-3">
                  <CInputGroupText>
                    <FontAwesomeIcon icon="fa-user" />
                  </CInputGroupText>
                  <CFormInput
                    v-model="email"
                    placeholder="Email address"
                    autocomplete="email"
                    required
                  />
                </CInputGroup>

                <CInputGroup class="mb-2">
                  <CInputGroupText>
                    <FontAwesomeIcon icon="fa-lock" />
                  </CInputGroupText>
                  <CFormInput
                    :type="showPassword ? 'text' : 'password'"
                    v-model="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                  />
                  <CInputGroupText @click="togglePasswordVisibility" style="cursor: pointer;">
                    <FontAwesomeIcon :icon="showPassword ? 'fa-eye' : 'fa-eye-slash'" />
                  </CInputGroupText>
                </CInputGroup>

                <div class="d-flex justify-content-end mb-3">
                  <CButton color="link" class="px-0" style="font-size: 0.95rem;">
                    Forgot password?
                  </CButton>
                </div>

                <CButton color="primary" class="w-100 mb-3" type="submit" :disabled="isLoading">
                  {{ isLoading ? 'Loading...' : 'Login' }}
                </CButton>
              </CForm>
            </CCardBody>
          </CCard>
        </CCol>
      </CRow>
    </CContainer>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faEye, faEyeSlash, faUser, faLock } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

library.add(faEye, faEyeSlash, faUser, faLock);

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const isLoading = ref(false);

const auth = useAuthStore();
const router = useRouter();

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const login = async () => {
  isLoading.value = true;
  try {
    const response = await auth.login(email.value, password.value);

    if (response.success) {
      sessionStorage.setItem('role', response.data.role);

      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Login successful!',
      });

      router.push('/dashboard');
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Login Failed',
        text: response.message || 'Invalid login credentials.',
      });
    }
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Something went wrong during login.',
    });
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #23272f;
  padding: 0 !important;
}

.shadow {
  box-shadow: 0 2px 16px rgba(0, 0, 0, 0.08);
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
  background: #fff;
  border: none;
}

.CCardBody {
  padding: 2.5rem 2rem;
}

h2 {
  font-weight: 700;
  font-size: 2rem;
  color: #23272f;
}

.text-muted {
  color: #6c757d !important;
}

.CButton,
button[type="submit"] {
  font-size: 1.1rem;
  font-weight: 600;
  background: #5a54ea;
  border: none;
  border-radius: 8px;
  transition: background 0.2s;
}

.CButton:active,
.CButton:focus,
.CButton:hover {
  background: #4338ca;
}

.CFormInput,
input[type="text"],
input[type="password"] {
  font-size: 1rem;
  border-radius: 8px;
}

@media (max-width: 576px) {
  .shadow {
    max-width: 98vw;
    padding: 1.5rem 0.5rem !important;
    border-radius: 8px;
  }
  .CCardBody {
    padding: 1.2rem 0.5rem;
  }
  h2 {
    font-size: 1.4rem !important;
  }
  p {
    font-size: 1rem !important;
  }
}
</style>
