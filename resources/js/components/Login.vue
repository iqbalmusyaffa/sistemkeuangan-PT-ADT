<template>
    <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
        <div class="row w-100 align-items-center">
            <!-- Form Card -->
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
                            <button type="submit" class="btn btn-primary">Login</button>
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
import axios from 'axios';

export default {
    data() {
        return {
            email: '',
            password: '',
        };
    },
    methods: {
        async login() {
            try {
                console.log('Attempting to login with /api/login');

                // Send login request using axios
                const response = await axios.post('/api/login', {
                    email: this.email,
                    password: this.password,
                });

                console.log('Response:', response);

                // Check if access_token is present in response
                if (response.data && response.data.access_token) {
                    const token = response.data.access_token;
                    const tokenExpiry = response.data.token_expiry || Date.now() + 3600000; // Default expiry to 1 hour

                    // Save token and expiry to sessionStorage
                    sessionStorage.setItem('token', token);
                    sessionStorage.setItem('token_expiry', tokenExpiry);

                    // Redirect to dashboard after successful login
                    this.$router.push('/dashboard');

                    // Show success message using SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Login successful!',
                    });
                } else {
                    // Handle login failure due to missing token in response
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Login failed: Token not found in response.',
                    });
                }
            } catch (error) {
                console.error('Login failed:', error);
                // Show error message using SweetAlert
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: `Login failed: ${error.message}`,
                });
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
