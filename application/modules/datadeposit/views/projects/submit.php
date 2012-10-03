<style text="test/css">
label img {
	margin-left: 6px; 
}
input[type=text], textarea {
	width: 60%!important;
}
</style>
    <?php $message=isset($message)?$message:$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
 <div style="text-align:right;padding-right:20px;padding-top:10px;"><a class="Help" style="cursor:pointer;" onclick="return false;">Help</a></div>
	<?php echo form_open("projects/submit/{$project[0]->id}", 'class="form"');?>

    <div class="field">

    <label for="accesspolicy">Suggested access policy<a class="accesspolicyHelp" href="" onclick="return false;"><img src="images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg accesspolicyHelpMsg" style="display:none;">

    <p>This should be same as Project Information title</p>

    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas porttitor congue massa. Fusce posuere, magna sed pulvinar ultricies, purus lectus malesuada libero, sit amet commodo magna eros quis urna.</p>

    </div>

	<select name="access_policy">
    <?php if (isset($project[0]->access_policy)): ?>
<option value="<?php echo $project[0]->access_policy; ?>" selected="selected"><?php echo $project[0]->access_policy; ?></option>
<?php endif; ?>
<option value="Direct Access">Direct Access</option>
<option value="Public Use Files">Public Use Files</option>
<option value="Licensed Access">Licensed Access</option>
<option value="Data Enclave">Data Enclave</option>
<option value="Not Defined">Not Defined</option>
<option value="No Access">No Access</option>
</select>    </div>

    

    <div class="field">

    <label for="notes_to_library">Notes to Library<a class="notes_to_libraryHelp" href="" onclick="return false;"><img src="images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg notes_to_libraryHelpMsg" style="display:none;">

    <p>This should be same as Project Information title</p>

    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas porttitor congue massa. Fusce posuere, magna sed pulvinar ultricies, purus lectus malesuada libero, sit amet commodo magna eros quis urna.</p>

    </div>

    <textarea name="library_notes"  class="input-flex" ><?php if (isset($project[0]->library_notes)) echo $project[0]->library_notes; ?></textarea>

    </div>

    

    <div class="field">

    <label for="contact">Contact<a class="contactHelp" href="" onclick="return false;"><img src="images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg contactHelpMsg" style="display:none;">

    <p>This should be same as Project Information title</p>

    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas porttitor congue massa. Fusce posuere, magna sed pulvinar ultricies, purus lectus malesuada libero, sit amet commodo magna eros quis urna.</p>

    </div>

    <input name="submit_contact" type="text" id="contact" class="input-flex" value="<?php if (isset($project[0]->submit_contact)) echo $project[0]->submit_contact; ?>"/>

    </div>

    

    <div class="field">

    <label for="access_authority">Access Authority<a class="access_authorityHelp" href="" onclick="return false;"><img src="images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg access_authorityHelpMsg" style="display:none;">

    <p>This should be same as Project Information title</p>

    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas porttitor congue massa. Fusce posuere, magna sed pulvinar ultricies, purus lectus malesuada libero, sit amet commodo magna eros quis urna.</p>

    </div>

    <input name="access_authority" type="text" id="access_authority" class="input-flex" value="<?php if (isset($project[0]->access_authority)) echo $project[0]->access_authority; ?>"/>

    </div>

    

    <div class="field">

    <label for="onbehalf">Submitting on behalf of<a class="onbehalfHelp" href="" onclick="return false;"><img src="images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg onbehalfHelpMsg" style="display:none;">

    <p>This should be same as Project Information title</p>

    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas porttitor congue massa. Fusce posuere, magna sed pulvinar ultricies, purus lectus malesuada libero, sit amet commodo magna eros quis urna.</p>

    </div>

    <input type="text" name="submit_on_behalf" id="onbehalf" class="input-flex" value="<?php if (isset($project[0]->submit_on_behalf)) echo $project[0]->submit_on_behalf; ?>"/>

    </div>

    

    <div class="field">

    <label for="title">CC <a class="submitccHelp" href="" onclick="return false;"><img src="images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg submitccHelpMsg" style="display:none;">

    <p>This should be same as Project Information title</p>

    <p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Maecenas porttitor congue massa. Fusce posuere, magna sed pulvinar ultricies, purus lectus malesuada libero, sit amet commodo magna eros quis urna.</p>

    </div>

    <input name="cc" type="text" id="ccsubmit" class="input-flex" value="<?php if (isset($project[0]->cc)) echo $project[0]->cc; ?>"/>

    </div>

    

    <div class="field" style="text-align:right;margin:5px 20px;">

    <input type="submit" name="submit_project" value="Save" id="submit" class="button"/>

    <input type="submit" name="submit_project" value="Submit" id="submit" class="button"/>

    <!--<a class="btn_cancel" href="http://localhost/datadeposit/datadeposit/index.php/projects/summary/1">Cancel</a>-->

    </div>

    

    <?php echo form_close(); ?>

