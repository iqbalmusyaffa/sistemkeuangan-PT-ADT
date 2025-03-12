import { defineStore } from "pinia";
import axios from "axios";

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    token: localStorage.getItem("token") || null,
  }),
  actions: {
    async fetchUser() {
      if (!this.token) return;
      try {
        const response = await axios.get("http://127.0.0.1:8000/api/profile", {
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        });
        this.user = response.data; // Simpan seluruh data user
      } catch (error) {
        console.error("Error fetching user:", error);
      }
    },
    setToken(token) {
      this.token = token;
      localStorage.setItem("token", token);
    },
    logout() {
      this.token = null;
      this.user = null;
      localStorage.removeItem("token");
    },
  },
});
