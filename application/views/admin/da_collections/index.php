<?php
// Admin bulk data access collections — Vue 3 + Vite
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-bulk-data-access-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    apiBaseUrl: "<?php echo addslashes($api_base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>",
    csrfTokenName: "<?php echo addslashes(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>",
    routerPathBase: "<?php echo addslashes(isset($router_path_base) ? $router_path_base : ''); ?>",
    canEdit: <?php echo ! empty($can_edit) ? 'true' : 'false'; ?>,
    canDelete: <?php echo ! empty($can_delete) ? 'true' : 'false'; ?>,
    translations: <?php echo json_encode($translations, JSON_HEX_APOS); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/bulk_data_access/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_bulk_data_access', 'frontend/dist'); ?>
<?php endif; ?>
