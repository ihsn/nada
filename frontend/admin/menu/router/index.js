import { createRouter, createWebHashHistory } from 'vue-router';
import MenuList from '../pages/MenuList.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: MenuList },
  ],
});
