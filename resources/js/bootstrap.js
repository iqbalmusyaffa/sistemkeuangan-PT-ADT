import axios from 'axios';
window.axios = axios;


import * as coreui from '@coreui/coreui';
window.coreui = coreui;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
