<?php
// Admin Data structures UI – Vue 3 + Vite entry point
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-data-structures-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    apiBaseUrl: "<?php echo addslashes($api_base_url); ?>",
    codelistsApiBaseUrl: "<?php echo addslashes(isset($codelists_api_base_url) ? $codelists_api_base_url : site_url('api/admin/codelists/')); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>"
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/data_structures/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_data_structures', 'frontend/dist'); ?>
<?php endif; ?>
