// src/router.js
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
import Dashboard from '../dashboard/components/Dashboard.vue';
const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
  },
//   {
//     path: '/',
//     redirect: '/login', // Redirect to login page by default
//   },
{
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
  },
{
    path: '/',
    redirect: '/login', // Redirect to login page by default
  },
  {
    path: '/:catchAll(.*)', // Catch-all route for 404
    redirect: '/login', // Redirect to login or show a 404 component
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
