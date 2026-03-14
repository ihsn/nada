<?php if (in_array($language, $rtl_languages)): ?>
<style>
.translation-container .flex-textarea{direction:rtl;}
</style>
<?php endif;?>
<style>
.edit-lang{
    background:gainsboro;
}
.not-found td{background:#dc3545;color:white;}
</style>

<div class="container-fluid translation-container" >

<div class="page-links text-right">
	<a href="<?php echo site_url('admin/translate/'); ?>" class="btn btn-outline-primary btn-sm text-right"><i class="fas fa-home mr-1"></i><?php echo t('Home'); ?></a>
</div>

<h1 class="page-title"><?php echo t('translate'); ?></h1>

<div class="container-fluid">

  <div class="row">
    <div class="col-md-3">
      <div class="list-group choose-file-container">
        <div class="list-group-item" style="background:#f8f9fa;border-bottom:1px solid #dee2e6;">
            <a href="<?php echo site_url('admin/translate'); ?>" class="d-block text-muted mb-1" style="font-size:0.8rem;">
                <i class="fas fa-chevron-left mr-1"></i><?php echo t('all_languages'); ?>
            </a>
            <?php if ($active_lang_file): ?>
            <a href="<?php echo site_url('admin/translate/edit/'.$language); ?>" class="d-block text-secondary" style="font-size:0.85rem;">
                <i class="fas fa-list mr-1"></i><?php echo ucfirst($language); ?> &mdash; <?php echo t('all_files'); ?>
            </a>
            <?php endif; ?>
        </div>
            <div class="list-group-item list-group-item-action  edit-lang">
            <form class="choose-lang" method="post" action="<?php echo site_url("admin/translate/change_lang"); ?>" name="change-lang" id="form-change-lang">
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
            <h5 class="mb-1"><?php echo t('select_language_to_translate'); ?></h5>
            <select class="form-control-sm" name="lang" id="lang">
                <?php foreach ($languages as $lang): ?>
                <option value="<?php echo $lang; ?>" <?php echo ($language == $lang) ? 'selected="selected"' : ''; ?> ><?php echo ucfirst($lang); ?></option>
                <?php endforeach;?>
            </select>
            <input type="hidden" name="file" value="<?php echo htmlspecialchars($active_lang_file); ?>">
            </form>
            </div>


       		<div class="file-list" >
        	<?php foreach ($files as $file): ?>
            	<?php $sname = str_replace("_lang.php", "", $file);?>
        		<?php
$sname = str_replace("_", " ", $sname);
$lang_filename = str_replace('_lang.php', '', $file);
$file_complete = isset($file_status[$lang_filename]) ? $file_status[$lang_filename] : null;
?>
                <div>
                	<div class="translation-file">
                    	<a class="list-group-item list-group-item-action <?php echo ($active_lang_file == $lang_filename) ? 'active' : ''; ?>" href="<?php echo site_url('admin/translate/edit/' . $language . '/' . str_replace('_lang.php', '', $file)); ?>">
                    		<?php if ($file_complete === true): ?>
                    			<i class="fas fa-check-circle text-success mr-1" title="Complete"></i>
                    		<?php elseif ($file_complete === false): ?>
                    			<i class="fas fa-exclamation-circle text-danger mr-1" title="Incomplete"></i>
                    		<?php endif; ?>
                    		<?php echo $sname; ?>
                    	</a>
                    </div>
                </div>
            <?php endforeach;?>
            </div>
      </div>



    </div>

    <div class="col-md-9">
      <!--Body content-->

        <?php if (isset($active_lang_file)): ?>
        <div class="edit-lang-info mb-2">
          File: <span class="lang-name"><?php echo $language; ?></span> / <span class="lang-file-name"><?php echo $active_lang_file; ?></span>
            <span style="color:gray;font-size:smaller;"><?php echo $edit_file_fullpath; ?></span>
        </div>
        <?php endif;?>

		<?php if (isset($save_status)): $save_status = (object) $save_status;?>
	            <?php if ($save_status->type == 'success'): ?>
	                <div class="success"><?php echo $save_status->msg; ?></div>
	            <?php endif;?>

            <?php if ($save_status->type == 'error'): ?>
                <div class="error"><?php echo $save_status->msg; ?></div>
            <?php endif;?>
        <?php endif;?>

	 <div class="form-body">
      <?php $this->load->view('translator/edit_file');?>
      </div>
    </div>
  </div>
</div>

</div>

<script type='text/javascript' >
$(document).on('change','#lang', function() {
    $("#form-change-lang").submit();
    return false;
});
</script>