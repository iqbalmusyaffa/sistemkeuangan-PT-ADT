<template>
    <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
        <div class="row w-100 align-items-center">
            <!-- Form Card -->
            <div class="col-lg-6 col-md-8 col-11 mx-auto">
                <div class="card shadow-lg border-0 rounded-4 p-4">
                    <h5 class="text-primary">Register</h5>
                    <h2 class="fw-bold">Create a new account! 👋👋</h2>
                    <p class="text-muted">Please fill in the details below to create your account.</p>

                    <!-- Registration Form -->
                    <form @submit.prevent="register">
                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" v-model="form.name" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" v-model="form.email" required>
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" v-model="form.username" required>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" v-model="form.password" required>
                        </div>

                        <!-- Profile Picture -->
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" @change="handleFileUpload">
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" v-model="form.role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="stafkeuangan">Staf Keuangan</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>

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
import axios from 'axios';

export default {
    data() {
        return {
            form: {
                name: '',
                email: '',
                username: '',
                password: '',
                profile_picture: null,
                role: 'user'
            },
            error: null,
            success: null
        };
    },
    methods: {
        handleFileUpload(event) {
            this.form.profile_picture = event.target.files[0];
        },
        async register() {
            try {
                let formData = new FormData();
                formData.append('name', this.form.name);
                formData.append('email', this.form.email);
                formData.append('username', this.form.username);
                formData.append('password', this.form.password);
                formData.append('role', this.form.role);

                if (this.form.profile_picture) {
                    formData.append('profile_picture', this.form.profile_picture);
                }

                const response = await axios.post('/api/register', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                this.success = 'Registration successful!';
                this.error = null;
                console.log('Registration successful:', response.data);
            } catch (error) {
                this.error = 'Registration failed. Please check the form and try again.';
                if (error.response && error.response.data) {
                    console.error('Registration error:', error.response.data);
                    this.error = JSON.stringify(error.response.data);
                } else {
                    console.error('Registration error:', error.message);
                }
                this.success = null;
            }
        }
    }
};
</script>

<style scoped>
/* Add your component-specific styles here */
</style>
