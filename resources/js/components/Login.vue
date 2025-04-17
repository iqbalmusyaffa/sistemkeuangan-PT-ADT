<template>
    <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
      <div class="row w-100 align-items-center">
        <div class="col-lg-6 col-md-8 col-11 mx-auto">
          <div class="card shadow-lg border-0 rounded-4 p-4">
            <h5 class="text-primary">Login</h5>
            <h2 class="fw-bold">Hi, welcome back!</h2>
            <p class="text-muted">Please enter your credentials to access your account.</p>
            <form @submit.prevent="login">
              <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" v-model="email" required />
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" v-model="password" required />
              </div>
              <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary" :disabled="isLoading">
                  <span v-if="isLoading">Loading...</span>
                  <span v-else>Login</span>
                </button>
                <a href="#" class="text-muted">Forgot password?</a>
              </div>
            </form>
          </div>
        </div>
      </div>
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

          // Jika berhasil, redirect ke dashboard
          if (response.success) {
            // Simpan role ke sessionStorage
            sessionStorage.setItem('role', response.data.role);
            // console.log('Role saved:', response.data.role); // Tambah log untuk debug

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
  .card {
    background-color: #ffffff;
  }
  </style>
