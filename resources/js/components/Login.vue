<template>
    <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
        <div class="row w-100 align-items-center">
            <!-- Form Card -->
            <div class="col-lg-6 col-md-8 col-11 mx-auto">
                <div class="card shadow-lg border-0 rounded-4 p-4">
                    <h5 class="text-primary">Login</h5>
                    <h2 class="fw-bold">Hi, welcome back! 👋👋</h2>
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
                    <div class="mt-3">
                        <p class="text-muted">Don't have an account? <a href="/register">Sign up</a></p>
                    </div>
                    <!-- Error Message -->
                    <div v-if="error" class="mt-3 alert alert-danger" role="alert">
                        {{ error }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    data() {
        return {
            email: '',
            password: '',
            error: null,
        };
    },
    methods: {
        async login() {
            try {
                // console.log('Attempting login with:', { email: this.email, password: this.password });

                const response = await axios.post('/api/login', {
                    email: this.email,
                    password: this.password,
                });

                // console.log('API Response:', response);

                if (response.data && response.data.access_token) {
                    const token = response.data.access_token;
                    // console.log('Token received:', token);

                    // Store the token in localStorage
                    localStorage.setItem('token', token);

                    // Set the Authorization header for future requests
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

                    // Redirect to dashboard or home page
                    console.log('Login successful, redirecting to dashboard');
                    this.$router.push('/dashboard');
                } else {
                    console.error('Token not found in response:', response.data);
                    this.error = 'Login failed: Token not found in response.';
                }
            } catch (error) {
                console.error('Login failed:', error);

                if (error.response && error.response.data) {
                    console.error('Error Response Data:', error.response.data);
                    this.error = `Login failed: ${error.response.data.message || 'An error occurred.'}`;
                } else {
                    this.error = 'Login failed: An unexpected error occurred.';
                }
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
