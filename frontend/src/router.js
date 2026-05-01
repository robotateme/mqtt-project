import { createRouter, createWebHashHistory } from 'vue-router';
import AllDevicesPanel from './components/devices/AllDevicesPanel.vue';
import UserDevicesPanel from './components/devices/UserDevicesPanel.vue';
import ProfilePanel from './components/ProfilePanel.vue';
import AllUsersPanel from './components/users/AllUsersPanel.vue';

const routes = [
  {
    path: '/',
    redirect: '/my-devices',
  },
  {
    path: '/all-devices',
    name: 'all-devices',
    component: AllDevicesPanel,
  },
  {
    path: '/all-users',
    name: 'all-users',
    component: AllUsersPanel,
  },
  {
    path: '/my-devices',
    name: 'my-devices',
    component: UserDevicesPanel,
    meta: {
      view: 'devices',
    },
  },
  {
    path: '/live-packets',
    name: 'live-packets',
    component: UserDevicesPanel,
    meta: {
      view: 'packets',
    },
  },
  {
    path: '/my-profile',
    name: 'my-profile',
    component: ProfilePanel,
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/my-devices',
  },
];

export default createRouter({
  history: createWebHashHistory(),
  routes,
});
