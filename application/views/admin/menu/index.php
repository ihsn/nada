<?php
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-menu-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl:         "<?php echo addslashes($site_url); ?>",
    baseUrl:         "<?php echo addslashes($base_url); ?>",
    apiBaseUrl:      "<?php echo addslashes($api_base_url); ?>",
    filestoreApiUrl: "<?php echo addslashes(isset($filestore_api_url) ? $filestore_api_url : site_url('api/admin/filestore')); ?>",
    filesPublicUrl:  "<?php echo addslashes(isset($files_public_url) ? $files_public_url : base_url('files/public')); ?>",
    assetsBase:      "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken:       "<?php echo addslashes($csrf_token); ?>",
    csrfTokenName:   "<?php echo addslashes(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>",
    useHtmlEditor:   "<?php echo addslashes(isset($use_html_editor) ? $use_html_editor : 'yes'); ?>"
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/menu/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_menu', 'frontend/dist'); ?>
<?php endif; ?>
