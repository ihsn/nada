<?php
/**
 * Login link for visitors, or the signed-in user's menu.
 *
 * @var string $variant   li (inside {{menu+login}}) | inline ({{login}})
 * @var string $id        id of the toggle link
 * @var string $user      username, '' when signed out
 * @var bool   $can_admin
 */
$tag = $variant === 'li' ? 'li' : 'div';
$class = $variant === 'li' ? 'nav-item dropdown sh-login' : 'dropdown sh-c sh-login sh-login-inline';
?>
<<?php echo $tag; ?> class="<?php echo $class; ?>">
    <?php if ($user !== ''): ?>
        <div class="dropdown ml-auto">
            <a class="nav-link dropdown-toggle " href="#" id="<?php echo $id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user-circle fa-lg"></i><?php echo html_escape($user); ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="<?php echo $id; ?>">
                <?php if ($can_admin): ?>
                    <a class="dropdown-item" href="<?php echo site_url('admin'); ?>"><?php echo t('site_administration'); ?></a>
                <?php endif; ?>
                <a class="dropdown-item" href="<?php echo site_url('auth/profile'); ?>"><?php echo t('profile'); ?></a>
                <a class="dropdown-item" href="<?php echo site_url('auth/change_password'); ?>"><?php echo t('password'); ?></a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?php echo site_url('auth/logout'); ?>"><?php echo t('logout'); ?></a>
            </div>
        </div>
    <?php else: ?>
        <a class="nav-link dropdown-toggle" href="" id="<?php echo $id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-circle fa-lg"></i><?php echo t('login'); ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="<?php echo $id; ?>">
            <a class="dropdown-item" href="<?php echo site_url('auth/login'); ?>"><?php echo t('login'); ?></a>
        </div>
    <?php endif; ?>
</<?php echo $tag; ?>>
