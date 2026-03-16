<?php
// Admin Codelists UI – Vue 3 + Vite entry point
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-codelists-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    apiBaseUrl: "<?php echo addslashes($api_base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>",
    translations: <?php echo json_encode($translations, JSON_HEX_APOS); ?>,
    enabledLanguages: <?php echo json_encode(isset($enabled_languages) ? $enabled_languages : [], JSON_HEX_APOS); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/codelists/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_codelists', 'frontend/dist'); ?>
<?php endif; ?>
