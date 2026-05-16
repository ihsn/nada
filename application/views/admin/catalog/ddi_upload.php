<?php
// Admin DDI upload – Vue 3 + Vite
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-ddi-upload-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    apiBaseUrl: "<?php echo addslashes($api_base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>",
    csrfTokenName: "<?php echo addslashes(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>",
    translations: <?php echo json_encode($translations, JSON_HEX_APOS); ?>,
    defaultRepositoryid: <?php echo json_encode(isset($default_repositoryid) ? $default_repositoryid : '', JSON_HEX_APOS); ?>,
    uploadActionUrl: "<?php echo addslashes(isset($upload_action_url) ? $upload_action_url : site_url('admin/catalog/add_study')); ?>",
    createStudyUrl: "<?php echo addslashes(isset($create_study_url) ? $create_study_url : site_url('admin/catalog/create')); ?>",
    catalogAdminUrl: "<?php echo addslashes(isset($catalog_admin_url) ? $catalog_admin_url : site_url('admin/catalog')); ?>",
    catalogEditBase: "<?php echo addslashes(isset($catalog_edit_base) ? $catalog_edit_base : site_url('admin/catalog/edit/')); ?>",
    collectionsAdminUrl: "<?php echo addslashes(isset($collections_admin_url) ? $collections_admin_url : site_url('admin/collections')); ?>",
    maxUploadMb: <?php echo (int) (isset($max_upload_mb) ? $max_upload_mb : 0); ?>,
    pageHeading: "<?php echo addslashes(t('admin_add_study_title')); ?>",
    flashError: <?php echo json_encode(isset($flash_error) && $flash_error !== '' && $flash_error !== null ? $flash_error : null, JSON_HEX_APOS | JSON_HEX_TAG); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/ddi_upload/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_ddi_upload', 'frontend/dist'); ?>
<?php endif; ?>
