<template>
  <div class="wrapper min-vh-100 d-flex flex-row align-items-center">
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="8">
          <CCardGroup>
            <CCard class="p-4">
              <CCardBody>
                <CForm @submit.prevent="login">
                  <h1>Login</h1>
                  <p class="text-body-secondary">Sign In to your account</p>
                  <CInputGroup class="mb-3">
                    <CInputGroupText>
                      <CIcon icon="cil-user" />
                    </CInputGroupText>
                    <CFormInput
                      v-model="email"
                      placeholder="Email"
                      autocomplete="email"
                      required
                    />
                  </CInputGroup>
                  <CInputGroup class="mb-4">
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
                  <CRow>
                    <CCol :xs="6">
                      <CButton color="primary" class="px-4" type="submit" :disabled="isLoading">
                        {{ isLoading ? 'Loading...' : 'Login' }}
                      </CButton>
                    </CCol>
                    <CCol :xs="6" class="text-right">
                      <CButton color="link" class="px-0">
                        Forgot password?
                      </CButton>
                    </CCol>
                  </CRow>
                </CForm>
              </CCardBody>
            </CCard>
            <CCard class="text-white bg-primary py-5" style="width: 44%">
              <CCardBody class="text-center">
                <div>
                  <h2>Sign up</h2>
                  <p>
                    Lorem ipsum dolor sit amet, consectetur adipisicing elit,
                    sed do eiusmod tempor incididunt ut labore et dolore magna
                    aliqua.
                  </p>
                  <CButton color="light" variant="outline" class="mt-3">
                    Register Now!
                  </CButton>
                </div>
              </CCardBody>
            </CCard>
          </CCardGroup>
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
  },
};
</script>

<style scoped>
.wrapper {
  background-color: #f5f5f5;
}
</style>
