<?php
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-users-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl:       "<?php echo addslashes($site_url); ?>",
    baseUrl:       "<?php echo addslashes($base_url); ?>",
    apiBaseUrl:    "<?php echo addslashes(rtrim($api_base_url, '/') . '/'); ?>",
    assetsBase:    "<?php echo addslashes($assets_base); ?>",
    csrfToken:     "<?php echo addslashes($csrf_token); ?>",
    csrfTokenName: "<?php echo addslashes($csrf_token_name); ?>",
    routerPathBase:"<?php echo addslashes($router_path_base); ?>",
    translations:  <?php echo json_encode($translations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/users/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_users', 'frontend/dist'); ?>
<?php endif; ?>
