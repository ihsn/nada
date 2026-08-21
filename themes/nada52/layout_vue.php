<?php
/**
 * Public Vue catalog layout — same header/footer chrome as default layout,
 * with a lean head (no Google Fonts / classic catalog CSS).
 */
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$menu_horizontal = TRUE;
$bootstrap_theme = 'themes/' . $this->template->theme();

$data = array();
$this->load->helper('menu');
$data['menus'] = $this->Menu_model->get_published_menu_tree();

$uri_ = $this->uri->segment_array();
foreach ($uri_ as $key => $val) {
    if (is_numeric($val)) {
        unset($uri_[$key]);
    }
}
$uri_ = implode('-', $uri_);

// Match default layout width (`container`) — Vue search UI is designed for that.
$content_wrap_class = 'container default-wrapper catalog-vue-wrapper page-' . $this->uri->segment(1) . ' ' . $uri_;
if (isset($body_class)) {
    $content_wrap_class = $body_class . ' page-' . $this->uri->segment(1) . ' ' . $uri_;
}

$use_cdn = true;
?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once 'head_vue.php'; ?>
</head>

<body class="catalog-vue-body">

    <?php include_once 'header.php'; ?>

    <div class="wp-page-body <?php echo $content_wrap_class; ?>">
        <div class="body-content-wrap theme-nada-2">

            <div class="container">
                <?php $breadcrumbs_str = $this->breadcrumb->to_string(); ?>
                <?php if ($breadcrumbs_str != '') : ?>
                    <ol class="breadcrumb wb-breadcrumb">
                        <?php echo $breadcrumbs_str; ?>
                    </ol>
                <?php endif; ?>
            </div>

            <?php echo isset($content) ? $content : ''; ?>
        </div>
    </div>

    <?php include_once 'footer.php'; ?>
</body>

</html>
