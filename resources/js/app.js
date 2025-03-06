// Import Vue and necessary plugins
import { createApp } from 'vue';
import { createPinia } from 'pinia';

// Import main application component
import App from './App.vue';

// Import Bootstrap and CoreUI styles
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min';
import '@coreui/coreui/dist/css/coreui.min.css';

// Import CoreUI Vue and Icon components
import CoreuiVue from '@coreui/vue';
import CIcon from '@coreui/icons-vue';

// Import icons set
import { iconsSet as icons } from '@/assets/icons';

// Import custom components
// import DocsComponents from '@/components/dashboard/DocsComponents.vue';
// import DocsExample from '@/components/dashboard/DocsExample.vue';
// import DocsIcons from '@/components/dashboard/DocsIcons.vue';
// import DocsReference from '@/components/dashboard/DocsReference.vue';

// Import router configuration
import router from './routes/route';

// Create Vue application instance
const app = createApp(App);

// Use Pinia for state management
const pinia = createPinia();
app.use(pinia);

// Use CoreUI Vue
app.use(CoreuiVue);

// Provide icons globally
app.provide('icons', icons);

// Register global components
app.component('CIcon', CIcon);
// app.component('DocsComponents', DocsComponents);
// app.component('DocsExample', DocsExample);
// app.component('DocsIcons', DocsIcons);
// app.component('DocsReference', DocsReference);

// Use router for navigation
app.use(router);

// Mount the application to the DOM
app.mount('#app');
