<?php
/**
 * Language switcher. Rendered only when two or more languages are enabled.
 *
 * @var string $variant li (inside {{menu+language}}) | inline ({{language}})
 * @var string $id
 * @var array  $items   [label, url, active]
 * @var string $current label of the current language
 */
?>
<?php if ($variant === 'li'): ?>
<li class="nav-item dropdown sh-language">
    <a class="nav-link dropdown-toggle" href="#" id="<?php echo $id; ?>" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fas fa-globe" aria-hidden="true"></i> <?php echo html_escape($current); ?>
    </a>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="<?php echo $id; ?>">
        <?php foreach ($items as $item): ?>
        <a class="dropdown-item<?php echo $item['active'] ? ' active' : ''; ?>" href="<?php echo html_escape($item['url']); ?>"><?php echo html_escape($item['label']); ?></a>
        <?php endforeach; ?>
    </div>
</li>
<?php else: ?>
<span class="lang-container sh-c sh-language sh-language-inline">
    <?php foreach ($items as $item): ?>
    <span class="lang-label"> <a href="<?php echo html_escape($item['url']); ?>"<?php echo $item['active'] ? ' aria-current="true"' : ''; ?>><?php echo html_escape($item['label']); ?></a> </span>
    <?php endforeach; ?>
</span>
<?php endif; ?>
