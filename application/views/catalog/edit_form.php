<form method="post" enctype="multipart/form-data" class="form">
<h1>Edit survey information</h1>
<div class="field">
	<label for="report_url"><img src="images/report.png" border="0"/> <?php print ('Reports and analytical output'); ?></label>
	<input name="report_url" type="text" id="report_url" value="<?php //echo $data['report_url']; ?>"/>
	<label for="report_attachment" class="desc">Provide a URL</label>
</div>
<div class="field hidden">
	<input name="report_attachment" type="file" id="report_attachment" />
	<label for="report_attachment" class="desc">Upload a file</label>
</div>

<div class="field">           
<label for="ind_url"><?php print ('Indicators database'); ?> <img src="images/page_white_database.png" border="0"/></label>
        <input name="ind_url" type="text" id="ind_url" value="<?php //echo $data['ind_url']; ?>"/>
</div>

<div class="field">
	<label for="quest_url"><img src="images/page_question.png" border="0"/> <?php print ('Questionnaire'); ?></label>
	<input name="quest_url" type="text" id="quest_url" value="<?php //echo $data['report_url']; ?>"/>
	<label for="quest_url" class="desc">Provide a URL or <a href="">upload file</a></label>
</div>
<div class="field hidden">
	<input name="quest_attachment" type="file" id="quest_attachment" />
	<label for="quest_attachment" class="desc">Upload a file</label>
</div>


<div class="field">
	<label for="doc_url"><img src="images/documentation.png" border="0"/> <?php print ('Technical documentation'); ?></label>
	<input name="doc_url" type="text" id="doc_url" value="<?php //echo $data['report_url']; ?>"/>
	<label for="doc_url" class="desc">Provide a URL or <a href="">upload file</a></label>
</div>
<div class="field hidden">
	<input name="doc_attachment" type="file" id="doc_attachment" />
	<label for="doc_attachment" class="desc">Upload a file</label>
</div>

<div class="field">           
<label for="study_url"><img src="images/page_white_world.png" border="0"/> <?php print ('Study website'); ?></label>
        <input name="study_url" type="text" id="study_url" value="<?php //echo $data['ind_url']; ?>"/>
</div>


<div class="field">           
<label for="request_form"><img src="images/page_white_world.png" border="0"/> <?php print ('Microdata request form'); ?></label>
        <select name="request_form" id="request_form" ></select>
</div>


<div class="field">           
<label for="share_ddi"><img src="images/shared.png" border="0"/> <?php print ('Share DDI'); ?></label>
        <input type="checkbox" name="share_ddi" id="share_ddi" value="1" /><label for="share_ddi" class="inline">Share DDI file?</label>
</div>

<div class="field">
<input type="submit" name="Update" id="update" value="<?php echo ('Update'); ?>" />
<input type="submit" name="Cancel" id="cancel" value="<?php echo ('Cancel'); ?>" />
</div>


<?php return;?>
        <td class="field-caption" ><div align="right"><?php print ('Microdata request form'); ?> </div></td>
        <td><img src="images/form_direct.gif" border="0"/></td>
        <td><select name="request_form" id="request_form" class="input-flat" style="width:300px;">
              <?php echo $forms_options; ?>
        </select></td>
      </tr>
      <tr valign="top">
        <td class="field-caption" align="right" > </td>
        <td><img src="images/shared.png" border="0"/></td>
        <td><input type="checkbox" name="chk_shared" id="chk_shared" value="1" <?php echo ($data['chk_shared']==1)? 'checked' : '' ?>/>
          <?php print ('Share with Harvester?'); ?></td>
      </tr>
    </table>
    <div class="html-form-submit">    
          <input type="submit" name="submit" id="submit" value="<?php print ('Update');?>" class="input-flat"/>
          <input type="button" name="close" id="close" value="<?php print ('Cancel');?>" class="input-flat" onClick="parent.on_editsurvey_close();"/>
    </div>	
  </form>