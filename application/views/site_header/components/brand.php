<?php
/**
 * Site header brand. All values arrive escaped from Site_header.
 *
 * @var string $mode           text | logo_text | logo | logo_only
 * @var string $home_url
 * @var string $title
 * @var string $subtitle       empty = no subtitle
 * @var string $logo_url
 * @var string $logo_alt
 * @var bool   $show_separator
 */
?>
<?php if ($mode === 'logo_only'): ?>
<a class="sh-logo sh-c navbar-brand--logo" href="<?php echo $home_url; ?>"><img src="<?php echo $logo_url; ?>" alt="<?php echo $logo_alt; ?>"></a>
<?php elseif ($mode === 'text'): ?>
<div class="navbar-brand no-logo sh-c sh-brand">
    <div class="navbar-brand--sitename">
        <div><a class="nada-site-title" href="<?php echo $home_url; ?>"><?php echo $title; ?></a></div>
        <?php if ($subtitle !== ''): ?>
        <div class="nada-site-subtitle"><?php echo $subtitle; ?></div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="navbar-brand sh-c sh-brand<?php echo $show_separator ? '' : ' sh-no-separator'; ?>">
    <div class="navbar-brand--logo">
        <a href="<?php echo $home_url; ?>"><img src="<?php echo $logo_url; ?>" alt="<?php echo $logo_alt; ?>"></a>
    </div>
    <?php if ($mode === 'logo_text'): ?>
    <div class="navbar-brand--sitename">
        <div><a class="nada-site-title" href="<?php echo $home_url; ?>"><?php echo $title; ?></a></div>
        <?php if ($subtitle !== ''): ?>
        <div class="nada-site-subtitle"><?php echo $subtitle; ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
