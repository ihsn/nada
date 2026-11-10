import { createRouter, createWebHashHistory } from 'vue-router';
import CountriesListPage from '../pages/CountriesListPage.vue';
import CountryEditPage from '../pages/CountryEditPage.vue';
import CountryMappingsPage from '../pages/CountryMappingsPage.vue';
import RegionsListPage from '../pages/RegionsListPage.vue';
import RegionEditPage from '../pages/RegionEditPage.vue';

const countryRoutes = [
  { path: '/', name: 'countries-list', component: CountriesListPage },
  { path: '/new', name: 'country-new', component: CountryEditPage },
  { path: '/edit/:id', name: 'country-edit', component: CountryEditPage, props: true },
  { path: '/mappings', name: 'country-mappings', component: CountryMappingsPage },
];

const regionRoutes = [
  { path: '/', name: 'regions-list', component: RegionsListPage },
  { path: '/new', name: 'region-new', component: RegionEditPage },
  { path: '/edit/:id', name: 'region-edit', component: RegionEditPage, props: true },
];

/**
 * admin/countries and admin/regions mount the same app; PHP sets APP_CONFIG.view.
 *
 * @param {'countries'|'regions'} view
 */
export function createCountriesRouter(view) {
  return createRouter({
    history: createWebHashHistory(),
    routes: [
      ...(view === 'regions' ? regionRoutes : countryRoutes),
      { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
  });
}
