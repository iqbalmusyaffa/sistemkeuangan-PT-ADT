// Import Vue and necessary plugins
import { createApp } from 'vue';
import { createPinia } from 'pinia';

// Import main application component
import App from './App.vue';

// Import Bootstrap and CoreUI styles
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
import '@coreui/coreui/dist/css/coreui.min.css';

// Import CoreUI Vue
import CoreuiVue from '@coreui/vue';

// Import Font Awesome
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { fas } from '@fortawesome/free-solid-svg-icons';
import { fab } from '@fortawesome/free-brands-svg-icons';
import '@fortawesome/fontawesome-free/css/all.min.css';

// Add all solid and brand icons to the library
library.add(fas, fab);

// Import router configuration
import router from './routes/route';

// Create Vue application instance
const app = createApp(App);

// Use Pinia for state management
const pinia = createPinia();
app.use(pinia);

// Use CoreUI Vue
app.use(CoreuiVue);

// Register FontAwesomeIcon as a global component
app.component('FontAwesomeIcon', FontAwesomeIcon);

// Use router for navigation
app.use(router);

// Mount the application to the DOM
app.mount('#app');
