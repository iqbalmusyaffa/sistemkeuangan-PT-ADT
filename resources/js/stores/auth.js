// src/stores/auth.js
import { defineStore } from "pinia";
import axios from "axios";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    token: sessionStorage.getItem("token") || null,  // Use sessionStorage for token
  }),

  actions: {
    // Fetch user data from the API
    async fetchUser() {
      const expiry = sessionStorage.getItem("token_expiry");  // Use sessionStorage for expiry
      if (!this.token || !expiry || Date.now() > expiry) {
        this.logout();
        return;
      }

      try {
        const response = await axios.get("http://127.0.0.1:8000/api/profile", {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        });
        this.user = response.data;
      } catch (error) {
        console.error("Error fetching user:", error);
        this.logout(); // Token expired or invalid
      }
    },

    // Set token and expiration in sessionStorage
    setToken(token, expiry) {
      this.token = token;
      sessionStorage.setItem("token", token);  // Save token to sessionStorage
      const expirationTime = expiry || Date.now() + 3600000; // Default expiry to 1 hour
      sessionStorage.setItem("token_expiry", expirationTime); // Save expiration time
    },

    // Logout and clear token and expiration
    logout() {
      this.token = null;
      this.user = null;
      sessionStorage.removeItem("token");  // Remove token from sessionStorage
      sessionStorage.removeItem("token_expiry");  // Remove expiration from sessionStorage
    },

    // Check if the token is still valid
    isTokenValid() {
      const expiry = sessionStorage.getItem("token_expiry");
      return this.token && expiry && Date.now() < expiry;
    }
  },
});
