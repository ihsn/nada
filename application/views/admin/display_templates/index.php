<?php
// Admin Display Template Manager UI – Vue 3 + Vite entry point
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-display-templates-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    apiBaseUrl: "<?php echo addslashes($api_base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>"
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/display_templates/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_display_templates', 'frontend/dist'); ?>
<?php endif; ?>
