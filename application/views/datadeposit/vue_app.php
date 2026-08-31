<?php
$config = (isset($config) && is_array($config)) ? $config : array();
$this->load->helper('vite_helper');
$vite_dev_url = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
?>

<div id="datadeposit-app"></div>
<script>
window.APP_CONFIG = <?php echo json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php if ($use_vite_dev): ?>
<?php echo render_vite_dev_scripts('datadeposit/main.js', $vite_dev_url); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('datadeposit', 'frontend/dist'); ?>
<?php endif; ?>
