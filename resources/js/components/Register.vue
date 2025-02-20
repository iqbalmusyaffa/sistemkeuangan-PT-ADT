<template>
    <div class="d-flex justify-content-center align-items-center min-vh-100 bg-light">
        <div class="row w-100 align-items-center">
            <!-- Form Card -->
            <div class="col-lg-6 col-md-8 col-11 mx-auto">
                <div class="card shadow-lg border-0 rounded-4 p-4">
                    <h5 class="text-primary">Register</h5>
                    <h2 class="fw-bold">Create a new account! 👋👋</h2>
                    <p class="text-muted">Please fill in the details below.</p>
                    <form @submit.prevent="register">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" v-model="name" required />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" v-model="email" required />
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" v-model="username" required />
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" v-model="password" required />
                        </div>
                        <div class="mb-3">
                            <label for="profile_picture" class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" @change="onFileChange" />
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" v-model="role">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                                <option value="stafkeuangan">Staf Keuangan</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from 'sweetalert2';

export default {
    data() {
        return {
            name: '',
            email: '',
            username: '',
            password: '',
            profile_picture: null,
            role: 'user',
        };
    },
    methods: {
        onFileChange(event) {
            const file = event.target.files[0];
            this.profile_picture = file;
        },
        async register() {
            const formData = new FormData();
            formData.append('name', this.name);
            formData.append('email', this.email);
            formData.append('username', this.username);
            formData.append('password', this.password);
            formData.append('profile_picture', this.profile_picture);
            formData.append('role', this.role);

            try {
                const response = await fetch('/api/register', {
                    method: 'POST',
                    body: formData,
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Registration failed');
                }

                const data = await response.json();
                Swal.fire({
                    icon: 'success',
                    title: 'Registration Successful',
                    text: 'Your account has been created successfully!',
                });

                // Reset form fields
                this.name = '';
                this.email = '';
                this.username = '';
                this.password = '';
                this.profile_picture = null;
                this.role = 'user';
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: error.message,
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
