<?php
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-site-header-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl:       <?php echo json_encode($site_url); ?>,
    baseUrl:       <?php echo json_encode($base_url); ?>,
    apiBaseUrl:    <?php echo json_encode($api_base_url); ?>,
    previewUrl:    <?php echo json_encode($preview_url); ?>,
    assetsBase:    <?php echo json_encode(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>,
    csrfToken:     <?php echo json_encode($csrf_token); ?>,
    csrfTokenName: <?php echo json_encode(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>,
    translations:  <?php echo json_encode($translations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/site_header/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_site_header', 'frontend/dist'); ?>
<?php endif; ?>
