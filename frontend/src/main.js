import 'bootstrap/dist/css/bootstrap.min.css';
import './styles.css';
import './styles/themes/empire-night.css';
import './styles/themes/republic-day.css';
import './styles/themes/tron-neon-night.css';
import './styles/themes/cellular-automata-day.css';
import './styles/themes/cellular-automata-night.css';

import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

createApp(App).use(router).mount('#app');
