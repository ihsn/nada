import { createRouter, createWebHashHistory } from 'vue-router';
import MenuList from '../pages/MenuList.vue';
import MenuEditPage from '../pages/MenuEditPage.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', component: MenuList },
    {
      path: '/new',
      component: MenuEditPage,
      props: { isNew: true, linktype: 0 },
    },
    {
      path: '/new-link',
      component: MenuEditPage,
      props: { isNew: true, linktype: 1 },
    },
    {
      path: '/edit/:id',
      component: MenuEditPage,
      props: (route) => ({
        id: route.params.id,
        isNew: false,
      }),
    },
  ],
});
