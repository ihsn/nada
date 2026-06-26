import { createRouter, createWebHashHistory } from 'vue-router';
import FacetsList    from '../pages/FacetsList.vue';
import FacetCreate   from '../pages/FacetCreate.vue';
import FacetEdit     from '../pages/FacetEdit.vue';
import FacetReorder  from '../pages/FacetReorder.vue';
import FacetTerms    from '../pages/FacetTerms.vue';
import FacetIndexer  from '../pages/FacetIndexer.vue';

export default createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/',              component: FacetsList   },
    { path: '/new',           component: FacetCreate  },
    { path: '/edit/:name',    component: FacetEdit,   props: true },
    { path: '/reorder',       component: FacetReorder },
    { path: '/terms/:id',     component: FacetTerms,  props: true },
    { path: '/indexer',       component: FacetIndexer },
  ],
});
