<?php if (isset($menus)): ?>
    <?php $current_page = current_url(); ?>
    <ul class="navbar-nav ml-auto">
        <?php $this->load->view('menu/nav_items', array('menus' => $menus, 'current_page' => $current_page)); ?>
    </ul>
<?php endif; ?>
