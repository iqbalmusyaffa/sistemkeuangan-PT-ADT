import { createApp } from 'vue';
import App from './App.vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
import router from './routes/route'; // Correct import path for the router

const app = createApp(App);
app.use(router); // Use the router
app.mount('#app');
