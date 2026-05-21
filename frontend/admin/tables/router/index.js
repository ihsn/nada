import { createRouter, createWebHashHistory } from 'vue-router';
import TablesListPage from '../pages/TablesListPage.vue';
import CreateTablePage from '../pages/CreateTablePage.vue';
import EditTablePage from '../pages/EditTablePage.vue';

const routes = [
  { path: '/', name: 'tables-list', component: TablesListPage, meta: { title: 'Tables' } },
  { path: '/create', name: 'tables-create', component: CreateTablePage, meta: { title: 'Create table' } },
  {
    path: '/edit/:db_id/:table_id',
    name: 'edit',
    component: EditTablePage,
    meta: { title: 'Edit table' },
    props: true,
  },
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

export default router;
