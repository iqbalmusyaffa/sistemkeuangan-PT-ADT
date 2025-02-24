import { createApp } from 'vue';
import App from './App.vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
import router from './routes/route';

const app = createApp(App);
app.use(router);
app.mount('#app');
