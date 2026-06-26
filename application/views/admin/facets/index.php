<?php
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-facets-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl:           "<?php echo addslashes($site_url); ?>",
    baseUrl:           "<?php echo addslashes($base_url); ?>",
    apiBaseUrl:        "<?php echo addslashes($api_base_url); ?>",
    assetsBase:        "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken:         "<?php echo addslashes($csrf_token); ?>",
    translations:      <?php echo json_encode($translations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>,
    dataTypes:         <?php echo json_encode($data_types); ?>,
    reorderDataTypes:  <?php echo json_encode($reorder_data_types); ?>,
    fields:            <?php echo json_encode($fields); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/facets/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_facets', 'frontend/dist'); ?>
<?php endif; ?>
