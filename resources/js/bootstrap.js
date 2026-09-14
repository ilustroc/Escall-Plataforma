import axios from 'axios';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common.Accept = 'application/json';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;
window.axios = axios;
export default axios;
