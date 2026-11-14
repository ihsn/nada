<?php
/**
 * Site menu with the mobile menu button (Bootstrap 4 collapse).
 *
 * @var string $id            id of the collapsible part
 * @var array  $menus         published menu tree (Menu_model::get_published_menu_tree)
 * @var string $login_html    login/user <li>, or ''
 * @var string $language_html language <li>, or ''
 */
?>
<button class="navbar-toggler navbar-toggler-right collapsed wb-navbar-button-toggler" type="button" data-toggle="collapse" data-target="#<?php echo $id; ?>" aria-controls="<?php echo $id; ?>" aria-expanded="false" aria-label="<?php echo html_escape(tt('toggle_navigation', 'Toggle navigation')); ?>">
    <span class="navbar-toggler-icon"></span>
</button>
<div class="navbar-collapse collapse sh-c sh-menu" id="<?php echo $id; ?>" aria-expanded="false">
    <ul class="navbar-nav ml-auto">
        <?php echo $this->load->view('menu/nav_items', array('menus' => $menus, 'current_page' => current_url()), true); ?>
        <?php echo $language_html; ?>
        <?php echo $login_html; ?>
    </ul>
</div>
