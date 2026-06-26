<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
$page_title = isset($page_title) ? $page_title : t('pdf_preview');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo html_escape($page_title); ?></title>
    <?php if (! $use_vite_dev): ?>
    <?php echo render_vite_entry_assets('pdf_viewer', 'frontend/dist'); ?>
    <?php endif; ?>
</head>
<body>
<div id="pdf-viewer-app"></div>
<script>
window.APP_CONFIG = {
    siteUrl: "<?php echo addslashes($site_url); ?>",
    baseUrl: "<?php echo addslashes($base_url); ?>",
    assetsBase: "<?php echo addslashes(isset($assets_base) ? $assets_base : base_url('frontend/dist/')); ?>",
    csrfToken: "<?php echo addslashes($csrf_token); ?>",
    translations: <?php echo json_encode($translations, JSON_HEX_APOS | JSON_HEX_TAG); ?>
};
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('pdf-viewer/main.js', $vite_dev_url); ?>
<?php endif; ?>
</body>
</html>
