<?php
/**
 * Blank HTML shell for Vue print/summary pages (no admin header).
 */
defined('BASEPATH') OR exit('No direct script access allowed');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
?>
<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <base href="<?php echo js_base_url(); ?>">
    <title><?php echo isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : ''; ?></title>
    <script type="text/javascript">
      var CI = {'base_url': '<?php echo site_url(); ?>'};
    </script>
  </head>
  <body class="admin-vue-body admin-vue-body--blank">
    <div id="content">
      <?php if (isset($content)): ?>
        <?php echo $content; ?>
      <?php endif; ?>
    </div>
  </body>
</html>
