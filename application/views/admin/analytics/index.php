<?php
// Admin Analytics Reports UI – Vue 3 + Vite entry point
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>
<div id="admin-analytics-reports-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>",
    csrfTokenName: "<?php echo addslashes(isset($csrf_token_name) ? $csrf_token_name : 'ncsrf'); ?>",
    serverTimezone: "<?php echo addslashes(isset($server_timezone) ? $server_timezone : date_default_timezone_get()); ?>",
    serverDate: "<?php echo addslashes(isset($server_date) ? $server_date : date('Y-m-d')); ?>",
    nadaBasePath: "<?php echo addslashes(isset($nada_base_path) ? $nada_base_path : rtrim(FCPATH, '/')); ?>"
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('admin/analytics_reports/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_analytics_reports', 'frontend/dist'); ?>
<?php endif; ?>
