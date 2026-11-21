<?php
/**
 * Array rendered as tags.
 *
 * display_options:
 *  - tag_column        - column shown as the tag text (default: name)
 *  - group_column      - group tags under this column's value, e.g. vocab (optional)
 *  - group_link_column - column holding a link for the group label, e.g. the vocabulary URI (optional)
 *  - link_column       - column holding a link for each tag (optional)
 *  - dedupe            - drop repeated tags within a group (default: on; set 0 to keep repeats)
 *
 * options:
 *  - badge_class - a single or multiple classes
 */

$this->load->helper('display_template');
$badge_class = "badge badge-pill badge-light badge-tags";
if (isset($options['badge_class'])) {
    $badge_class = $options['badge_class'];
}

$opts = (isset($template['display_options']) && is_array($template['display_options'])) ? $template['display_options'] : array();
$opt_str = function ($key, $default = '') use ($opts) {
    return isset($opts[$key]) && is_scalar($opts[$key]) && trim((string)$opts[$key]) !== '' ? trim((string)$opts[$key]) : $default;
};
$column_name = $opt_str('tag_column', 'name');
$group_column = $opt_str('group_column');
$group_link_column = $opt_str('group_link_column');
$link_column = $opt_str('link_column');
$dedupe = !in_array($opt_str('dedupe', '1'), array('0', 'false', 'no'), true);

$tag_scalar_def = array(
    'type' => 'string',
    'display_options' => array('format' => 'plain', 'linkify' => false),
);

//only web links are turned into anchors
$safe_link = function ($url) {
    $url = trim((string)$url);
    return preg_match('~^(https?|ftp)://~i', $url) ? $url : '';
};
$cell = function ($row, $col) {
    return ($col !== '' && isset($row[$col]) && is_scalar($row[$col])) ? trim((string)$row[$col]) : '';
};

//build groups: group label => array('link' => ..., 'tags' => array(html, ...))
$groups = array();
$seen = array();
if (isset($data) && is_array($data)) {
    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }
        $tag_val = isset($row[$column_name]) ? $row[$column_name] : '';
        $tag_html = display_template_render_scalar_value($tag_val, $tag_scalar_def, array('mode' => 'cell'));
        if (trim(strip_tags($tag_html)) === '') {
            continue;
        }
        $group = $cell($row, $group_column);
        $dedupe_key = mb_strtolower($group . "\x1f" . trim(strip_tags($tag_html)));
        if ($dedupe && isset($seen[$dedupe_key])) {
            continue;
        }
        $seen[$dedupe_key] = true;

        if (!isset($groups[$group])) {
            $groups[$group] = array('link' => '', 'tags' => array());
        }
        if ($groups[$group]['link'] === '') {
            $groups[$group]['link'] = $safe_link($cell($row, $group_link_column));
        }
        $tag_link = $safe_link($cell($row, $link_column));
        if ($tag_link !== '') {
            $tag_html = '<a href="' . html_escape($tag_link) . '" target="_blank" rel="noopener noreferrer">' . $tag_html . '</a>';
        }
        $groups[$group]['tags'][] = $tag_html;
    }
}
//tags without a group first
if (isset($groups['']) && count($groups) > 1) {
    $groups = array('' => $groups['']) + $groups;
}
?>
<?php if (!empty($groups)):?>
<div class="mb-2 field field-tags field-<?php echo html_escape(str_replace('.', '-', $template['key']));?>">
    <div class="field-title"><?php echo display_template_resolve_title($template['key'], $template['title']);?></div>
    <div class="field-value">
        <?php foreach ($groups as $group_label => $group):?>
            <?php if ($group_column !== ''):?>
            <div class="tag-group">
                <?php if ($group_label !== ''):?>
                <div class="tag-group-label">
                    <?php if ($group['link'] !== ''):?>
                        <a href="<?php echo html_escape($group['link']);?>" target="_blank" rel="noopener noreferrer"><?php echo html_escape($group_label);?></a>
                    <?php else:?>
                        <?php echo html_escape($group_label);?>
                    <?php endif;?>
                </div>
                <?php endif;?>
                <div class="tag-group-tags">
            <?php endif;?>
            <?php foreach ($group['tags'] as $tag_html):?>
                <span class="<?php echo $badge_class;?>"><?php echo $tag_html;?></span>
            <?php endforeach;?>
            <?php if ($group_column !== ''):?>
                </div>
            </div>
            <?php endif;?>
        <?php endforeach;?>
    </div>
</div>
<?php endif;?>
