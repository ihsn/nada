<style>
.flex{width:100%;}
</style>
<div class='content-container'>
<form method="post">

<div class="field">
  <label for="id"><?php echo t('Paste survey ID(s) here');?><span class="required">*</span></label>
  <textarea rows="10" name="id" class="flex"><?php echo get_form_value('id',isset($id) ? $id : ''); ?></textarea>
</div>

<div class="field">
  <label for="json_output"><?php echo t('JSON Output');?></label>
  <textarea rows="10" name="json_output"  class="flex"><?php echo get_form_value('json_output',isset($json_output) ? $json_output : ''); ?></textarea>
</div>

<input type="submit" name="submit" value="Submit"/>
</form>
</div>