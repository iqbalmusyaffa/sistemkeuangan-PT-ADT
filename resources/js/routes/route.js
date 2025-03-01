// src/router.js
import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/auth/Login.vue';
import Register from '../components/auth/Register.vue';
// Lazy load Dashboard App
const DashboardApp = () => import('../dashboard/App.vue')
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
    path: '/dashboard/:pathMatch(.*)*',  // Semua /dashboard/* diarahkan ke dashboard app
    name: 'DashboardApp',
    component: DashboardApp,
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
