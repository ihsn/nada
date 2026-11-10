<?php
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-countries-app"></div>
<script>
window.APP_CONFIG = {
    view:            "<?php echo addslashes($view); ?>",
    siteUrl:         "<?php echo addslashes($site_url); ?>",
    baseUrl:         "<?php echo addslashes($base_url); ?>",
    countriesApiUrl: "<?php echo addslashes($countries_api_url); ?>",
    regionsApiUrl:   "<?php echo addslashes($regions_api_url); ?>",
    assetsBase:      "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken:       "<?php echo addslashes($csrf_token); ?>",
    csrfTokenName:   "<?php echo addslashes(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>",
    translations:    <?php echo json_encode(isset($translations) ? $translations : array(), JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/countries/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_countries', 'frontend/dist'); ?>
<?php endif; ?>
