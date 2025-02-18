<template>
    <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
      <div class="row w-100 align-items-center">
        <!-- Form Card -->
        <div class="col-lg-6 col-md-8 col-11 mx-auto">
          <div class="card shadow-lg border-0 rounded-4 p-4">
            <h5 class="text-primary">Login</h5>
            <h2 class="fw-bold">Hi, welcome back! 👋👋</h2>
            <p class="text-muted">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore.
            </p>
            <form @submit.prevent="login">
              <div class="mb-3">
                <input type="email" class="form-control" placeholder="Email address" v-model="loginData.email" required>
              </div>
              <div class="mb-3">
                <input type="password" class="form-control" placeholder="Password" v-model="loginData.password" required>
              </div>
              <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Remember me</label>
              </div>
              <button type="submit" class="btn btn-primary w-100">Submit</button>
            </form>
            <div class="mt-3">
              <span>Don’t have an account? <a href="#" class="text-primary">Register</a></span>
            </div>
          </div>
        </div>

        <!-- Image and Text -->
        <div class="col-lg-6 d-none d-lg-block">
          <div class="text-center">
            <img src="https://via.placeholder.com/300" alt="Welcome Image" class="img-fluid mb-3">
            <h4 class="text-primary">Therichpost.com free code snippets.</h4>
            <p class="text-muted">
              Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore.
            </p>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref } from 'vue';

  const loginData = ref({
    email: '',
    password: ''
  });

  const login = () => {
    console.log("Login Data:", loginData.value);
    alert("Login Success! (Fungsi login disesuaikan di backend)");
  };
  </script>

  <style scoped>
  .card {
    background: white;
    border-radius: 1.5rem;
  }
  </style>
