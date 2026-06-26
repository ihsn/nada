<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
$repo_for_api = isset($active_repo) && $active_repo !== null && $active_repo !== '' ? $active_repo : '';
$has_ssr_rows = isset($ssr_rows) && is_array($ssr_rows) && count($ssr_rows) > 0;
?>
<script>document.documentElement.classList.add('js');</script>
<?php if ($has_ssr_rows): ?>
<?php echo $this->load->view('catalog_vue/ssr_cards', get_defined_vars(), true); ?>
<?php endif; ?>
<div id="public-catalog-vue-app">
<?php echo $this->load->view('catalog_vue/app_loading', null, true); ?>
</div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    apiBaseUrl: "<?php echo addslashes($api_base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    activeRepo: "<?php echo addslashes($repo_for_api); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>",
    siteConfig: <?php echo json_encode(isset($site_config) ? $site_config : new stdClass(), JSON_HEX_APOS | JSON_HEX_TAG); ?>,
    activeRepoInfo: <?php echo json_encode(isset($active_repo_object) && $active_repo_object ? $active_repo_object : null); ?>,
    translations: <?php echo json_encode($translations, JSON_HEX_APOS | JSON_HEX_TAG); ?>,
    initialSearch: <?php echo json_encode(isset($initial_search) && $initial_search ? $initial_search : null, JSON_HEX_APOS | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE); ?>,
    initialSearchQueryKey: <?php echo json_encode(isset($initial_search_query_key) ? $initial_search_query_key : '', JSON_HEX_APOS | JSON_HEX_TAG); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('catalog-search/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('catalog_search', 'frontend/dist'); ?>
<?php endif; ?>
