import { createApp } from 'vue';
import App from './App.vue';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
// import CoreuiVue from '@coreui/vue';
// import CIcon from '@coreui/icons-vue';
// import {iconSset as icons} from './assets/icons/icons';
import router from './routes/route';
// import DocsComponents from '@/components/DocsComponents'
// import DocsExample from '@/components/DocsExample'
// import DocsIcons from '@/components/DocsIcons'

// app.provide('icons', icons)
// app.component('CIcon', CIcon)
const app = createApp(App);
app.use(router);
app.mount('#app');
