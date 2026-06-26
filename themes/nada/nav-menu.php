<!-- Start menus -->
<?php $menus = isset($data['menus']) ? $data['menus'] : false; ?>
<div class="navbar-collapse collapse" id="containerNavbar" aria-expanded="false">
<?php if (isset($menus)): ?>
    <?php $current_page = current_url(); ?>
    <ul class="navbar-nav ml-auto">
        <?php $this->load->view('menu/nav_items', array('menus' => $menus, 'current_page' => $current_page)); ?>
    </ul>
<?php endif; ?>

</div>
<!-- Close Menus -->
