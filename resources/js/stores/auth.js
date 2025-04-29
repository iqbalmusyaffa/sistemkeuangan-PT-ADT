// src/stores/auth.js
import { defineStore } from 'pinia'
import axios from 'axios'
import Swal from 'sweetalert2'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: sessionStorage.getItem('token') || null,
    tokenExpiry: sessionStorage.getItem('token_expiry') || null,
    user: null,
  }),

  actions: {
    setToken(token, expiry) {
      this.token = token
      this.tokenExpiry = expiry

      sessionStorage.setItem('token', token)
      sessionStorage.setItem('token_expiry', expiry)

      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },

    async login(email, password) {
      try {
        const response = await axios.post('/api/login', { email, password })
        console.log('Login response:', response.data) // Debug log

        const token = response.data.access_token
        const expiry = Date.now() + 4 * 60 * 60 * 1000  // sesi 1jam

        if (token) {
          this.setToken(token, expiry)
          // Simpan role ke sessionStorage
          sessionStorage.setItem('role', response.data.role)
          console.log('Role saved in auth store:', response.data.role) // Debug log

          await this.fetchUser()
          return { success: true, data: response.data }
        } else {
          return { success: false, message: 'Token not found in response.' }
        }
      } catch (error) {
        const message = error.response?.data?.message || error.message
        return { success: false, message }
      }
    },

    async fetchUser() {
      if (!this.token) return null

      try {
        const response = await axios.get('/api/profile', {
          headers: { Authorization: `Bearer ${this.token}` },
        })

        if (response.data.status === 'active') {
          this.user = response.data
          return this.user
        } else {
          this.logout()
          return null
        }
      } catch (error) {
        this.logout()
        return null
      }
    },

    checkTokenExpiry() {
      const now = Date.now()
      if (this.tokenExpiry && now > parseInt(this.tokenExpiry)) {
        this.logout(true)
        return false
      }
      return true
    },

    logout(showAlert = false) {
      // Clear state
      this.token = null
      this.tokenExpiry = null
      this.user = null

      // Clear session storage
      sessionStorage.clear() // Hapus semua data session

      // Clear axios header
      delete axios.defaults.headers.common['Authorization']

      if (showAlert) {
        Swal.fire({
          icon: 'info',
          title: 'Sesi Berakhir',
          text: 'Sesi login Anda telah berakhir. Silakan login kembali.',
          confirmButtonText: 'OK',
        })
      }
    },
  },
})
