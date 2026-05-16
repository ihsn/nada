<?php
/**
 * Full HTML shell for Vue-first admin pages (no Template library).
 * CSS: themes/{theme_folder}/shell.css
 */
defined('BASEPATH') OR exit('No direct script access allowed');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$this->load->library('site_menu');
$this->load->helper('vite_helper');
$CI =& get_instance();
$admin_header_config = $CI->site_menu->get_admin_header_config();
$vite_dev_url_header = defined('VITE_DEV_URL') ? VITE_DEV_URL : 'http://localhost:5173';
$use_vite_dev_header = defined('VUE_ENVIRONMENT') && VUE_ENVIRONMENT === 'development';
$theme_folder = isset($theme_folder) ? $theme_folder : 'adminvue';
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <base href="<?php echo js_base_url(); ?>">
    <title><?php echo isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : ''; ?></title>

    <link href="<?php echo base_url(); ?>themes/<?php echo htmlspecialchars($theme_folder, ENT_QUOTES, 'UTF-8'); ?>/shell.css?v=8" rel="stylesheet">

    <script type="text/javascript">
      var CI = {'base_url': '<?php echo site_url(); ?>'};
    </script>

    <?php if (isset($_styles)) { echo $_styles; } ?>
    <?php if (isset($_scripts)) { echo $_scripts; } ?>

    <script>
    (function () {
      if (typeof window.fetch !== 'function') return;
      var origFetch = window.fetch;
      window.fetch = function () {
        return origFetch.apply(this, arguments).then(function (response) {
          if (response.status === 401 && typeof CI !== 'undefined' && CI.base_url) {
            window.location = CI.base_url + '/auth/login/?destination=admin/';
          }
          return response;
        });
      };
    })();
    </script>

</head>
<body class="admin-vue-body">

<script>
window.ADMIN_HEADER_CONFIG = <?php echo json_encode($admin_header_config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
</script>
<div id="admin-app-header"></div>
<?php if ($use_vite_dev_header): ?>
<?php echo render_vite_dev_scripts('admin/header/main.js', $vite_dev_url_header); ?>
<?php else: ?>
<?php echo render_vite_entry_assets('admin_header', 'frontend/dist'); ?>
<?php endif; ?>

<?php if (isset($collection)): ?>
<div class="sub-header"><?php echo $collection; ?></div>
<?php endif; ?>

    <div class="admin-vue-shell">
        <div>
            <div id="content">
              <?php if (isset($content)): ?>
                  <?php echo $content; ?>
              <?php endif; ?>
            </div>
        </div>
    </div>

  </body>
</html>
