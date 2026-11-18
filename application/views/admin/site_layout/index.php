<?php
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-site-layout-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl:            <?php echo json_encode($site_url); ?>,
    baseUrl:            <?php echo json_encode($base_url); ?>,
    layoutApiBaseUrl:   <?php echo json_encode($layout_api_base_url); ?>,
    previewBaseUrl:     <?php echo json_encode($preview_base_url); ?>,
    assetsBase:         <?php echo json_encode(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>,
    csrfToken:          <?php echo json_encode($csrf_token); ?>,
    csrfTokenName:      <?php echo json_encode(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>,
    translations:       <?php echo json_encode($translations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/site_layout/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_site_layout', 'frontend/dist'); ?>
<?php endif; ?>
