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
                Masuk ke akun Anda untuk melanjutkan ke FinanceHub.
              </p>
              <CForm @submit.prevent="login">
                <CInputGroup class="mb-3">
                  <CInputGroupText>
                    <CIcon icon="cil-user" />
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
                    <CIcon icon="cil-lock-locked" />
                  </CInputGroupText>
                  <CFormInput
                    v-model="password"
                    type="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                  />
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

<script>
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';

export default {
  data() {
    return {
      email: '',
      password: '',
      isLoading: false,
    };
  },
  methods: {
    async login() {
      const auth = useAuthStore();
      this.isLoading = true;

      try {
        const response = await auth.login(this.email, this.password);

        if (response.success) {
          sessionStorage.setItem('role', response.data.role);

          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Login successful!',
          });
          this.$router.push('/dashboard');
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
        this.isLoading = false;
      }
    },
    loginWithGoogle() {
      // Implementasikan login Google sesuai kebutuhan
      Swal.fire({
        icon: 'info',
        title: 'Google Login',
        text: 'Fitur login dengan Google belum diimplementasikan.',
      });
    },
  },
};
</script>

<style scoped>
.wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #23272f; /* warna gelap */
  padding: 0 !important;
}

.shadow {
  box-shadow: 0 2px 16px rgba(0,0,0,0.08);
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

.CButton, button[type=\"submit\"] {
  font-size: 1.1rem;
  font-weight: 600;
  background: #5a54ea;
  border: none;
  border-radius: 8px;
  transition: background 0.2s;
}

.CButton:active, .CButton:focus, .CButton:hover {
  background: #4338ca;
}

.CFormInput, input[type=\"text\"], input[type=\"password\"] {
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
