<style>
.note_body_text{height:200px;}
</style>
<form method="post" class="form-edit-note" action="<?php echo $action_url; ?>" style="padding:10px;">

<?php if (isset($show_note_types) && $show_note_types===TRUE):?>
<div class="field">
    <label class="inline"><?php echo t('select_note_type');?>
    <?php echo form_dropdown('type', array('admin'=>t('admin_note'),'reviewer'=>t('reviewer_note'),'public'=>t('public_note')), get_form_value("type",isset($type) ? $type : ''),'class="edit_note_type"'); ?>
    </label>	
</div>
<?php endif;?>

<div class="field add-note">
    <textarea name="note" class="input-flex note_body_text" ><?php echo isset($note) ? $note : '';?></textarea>
    <?php if(!$this->input->is_ajax_request()):?>
        <div style="text-align:right;margin-right:2px;">
            <input type="submit" name="submit" value="<?php echo t('update');?>" />
        </div>
    <?php endif;?>
</div>
</form>
