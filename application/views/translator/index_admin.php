<div class="body-container" style="padding:10px;">

<h3 class="page-title mt-5"><?php echo t('translate');?></h3>
<p class="text-muted mb-3">
    <?php echo t('base_language');?>: English
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <?php echo anchor('admin/translate/import_lang', '<i class="fas fa-file-import"></i> '.t('import_language'), 'class="btn btn-sm btn-outline-secondary"');?>
</p>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<table class="table table-striped table-sm" width="100%" cellspacing="0" cellpadding="0">
	<tr class="header">
        <th><?php echo t('Language');?></th>
        <th><?php echo t('source');?></th>
        <th><?php echo t('completion');?></th>
        <th><?php echo t('actions');?></th>
    </tr>
	<?php foreach($languages as $lang):?>
        <?php
            $c    = isset($completeness[$lang]) ? $completeness[$lang] : null;
            $info = isset($lang_info[$lang]) ? $lang_info[$lang] : array('has_official'=>false,'has_userdata'=>false);
        ?>
        <tr valign="top">
            <td><strong><?php echo ucfirst($lang);?></strong></td>
            <td>
                <?php if ($lang === 'english'): ?>
                    <span class="badge badge-dark"><?php echo t('base_language');?></span>
                <?php elseif ($info['has_official'] && $info['has_userdata']): ?>
                    <span class="badge badge-info" title="<?php echo t('lang_modified_title');?>"><?php echo t('lang_modified');?></span>
                <?php elseif ($info['has_official']): ?>
                    <span class="badge badge-secondary" title="<?php echo t('lang_official_title');?>"><?php echo t('lang_official');?></span>
                <?php else: ?>
                    <span class="badge badge-warning" title="<?php echo t('lang_custom_title');?>"><?php echo t('lang_custom');?></span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($lang === 'english'): ?>
                    &mdash;
                <?php elseif ($c): ?>
                    <div class="progress mb-1" style="height:16px;min-width:140px;">
                        <div class="progress-bar <?php echo $c['percent'] >= 100 ? 'bg-success' : ($c['percent'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>"
                             role="progressbar"
                             style="width:<?php echo $c['percent'];?>%"
                             aria-valuenow="<?php echo $c['percent'];?>"
                             aria-valuemin="0" aria-valuemax="100">
                            <?php echo $c['percent'];?>%
                        </div>
                    </div>
                    <small class="text-muted"><?php echo $c['translated'];?>/<?php echo $c['total'];?> <?php echo t('keys_translated');?></small>
                    <?php if (!empty($c['missing_files'])): ?>
                        &nbsp;<small class="text-danger">&mdash; <?php echo count($c['missing_files']); ?> <?php echo t('files_missing');?>: <?php echo implode(', ', $c['missing_files']); ?></small>
                    <?php endif;?>
                <?php endif;?>
            </td>
            <td>
                <?php echo anchor('admin/translate/edit/'.$lang, t('edit'));?> | <?php echo anchor('admin/translate/download/'.$lang, t('download'));?>
            </td>
        </tr>
    <?php endforeach;?>
</table>

<hr class="mt-4">

<h5 class="mt-3"><?php echo t('create_new_language');?></h5>
<form method="post" action="<?php echo site_url('admin/translate/create_lang'); ?>" class="form-inline mt-2">
    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
    <div class="form-group mr-2">
        <label class="mr-1" for="language_name"><?php echo t('language_name');?></label>
        <input type="text" class="form-control form-control-sm" id="language_name" name="language_name"
               placeholder="e.g. arabic" pattern="[a-z][a-z0-9_]{1,29}" required
               title="<?php echo t('language_name_hint');?>">
    </div>
    <button type="submit" class="btn btn-sm btn-outline-primary"><?php echo t('create_new_language');?></button>
    <small class="text-muted ml-3"><?php echo t('language_name_hint');?></small>
</form>

</div>