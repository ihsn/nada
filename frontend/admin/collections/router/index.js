import { createRouter, createWebHashHistory } from 'vue-router';
import CollectionsList from '../pages/CollectionsList.vue';
import CollectionCreate from '../pages/CollectionCreate.vue';
import CollectionEdit from '../pages/CollectionEdit.vue';
import CollectionHistory from '../pages/CollectionHistory.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: CollectionsList },
    { path: '/new', component: CollectionCreate },
    { path: '/edit/:repositoryid', component: CollectionEdit },
    { path: '/history/:repositoryid', component: CollectionHistory },
  ],
});
