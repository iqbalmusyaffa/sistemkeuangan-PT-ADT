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
                    <!-- Success Message -->
                    <div v-if="success" class="mt-3 alert alert-success" role="alert">
                        {{ success }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            email: '',
            password: '',
            error: null,
            success: null, // Menambahkan state untuk pesan keberhasilan
        };
    },
    methods: {
        async login() {
            // console.log('Login function started');
            this.error = null; // Reset error message
            this.success = null; // Reset success message
            // console.log('Error and success messages reset');
            try {
                // console.log('Attempting to fetch /api/login');
                const response = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password,
                    }),
                });

                // console.log('Fetch response:', response);

                if (!response.ok) {
                    console.log('Response not OK, checking for error data');
                    const errorData = await response.json();
                    console.error('Error data from response:', errorData);
                    throw new Error(errorData.message || 'Login failed');
                }

                console.log('Response OK, parsing JSON');
                const data = await response.json();
                // console.log('Parsed JSON data:', data);
                const token = data.access_token;

                if (token) {
                    // console.log('Token found:', token);
                    localStorage.setItem('token', token);
                    console.log('Token stored in localStorage');
                    this.success = 'Login successful! Redirecting to dashboard...'; // Set pesan keberhasilan
                    console.log('Redirecting to /dashboard');
                    this.$router.push('/dashboard');
                } else {
                    console.error('Token not found in response:', data);
                    this.error = 'Login failed: Token not found in response.';
                    console.log('Error message set:', this.error);
                }
            } catch (error) {
                console.error('Login failed:', error);
                this.error = `Login failed: ${error.message}`;
                console.log('Error message set:', this.error);
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
