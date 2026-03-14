<div class="body-container" style="padding:10px;">

<h3 class="page-title mt-5"><?php echo t('import_language');?></h3>
<p class="text-muted mb-3">
    <?php echo anchor('admin/translate', '&larr; '.t('all_languages'));?>
</p>

<?php if (!empty($import_error)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($import_error); ?>
        <?php if (!empty($import_skipped)): ?>
            <ul class="mt-2 mb-0">
                <?php foreach ($import_skipped as $s): ?>
                    <li><?php echo htmlspecialchars($s); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card mt-3" style="max-width:540px;">
    <div class="card-body">
        <p><?php echo t('import_language_hint');?></p>
        <form method="post" action="<?php echo site_url('admin/translate/import_lang'); ?>" enctype="multipart/form-data">
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
            <div class="form-group">
                <label for="lang_zip"><?php echo t('import_zip_label');?></label>
                <input type="file" class="form-control-file mt-1" id="lang_zip" name="lang_zip" accept=".zip" required>
            </div>
            <button type="submit" name="import_submit" value="1" class="btn btn-primary btn-sm">
                <?php echo t('import_language');?>
            </button>
            <?php echo anchor('admin/translate', t('cancel'), 'class="btn btn-sm btn-outline-secondary ml-2"');?>
        </form>
    </div>
</div>

</div>
