<?php if (isset($_GET['print']) && $_GET['print'] == 'yes'): ?>
<script type="text/javascript" src="<?php echo site_url(); ?>/../javascript/jquery/jquery.js"></script>
<?php endif; ?>

<script type="text/javascript">
	jQuery(document).ready(function(){
		$("#tabs").tabs();
	});
</script>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
  
<div id="toTop">^ Back to Top</div>

<div class="contents page-review-submit">    
    <?php $active_tab_class=' class= "ui-corner-top ui-tabs-selected ui-state-active"'; ?>
	<div id="tabs">
        <ul>
            <li><a href="<?php echo current_url();?>#tabs-1"><?php echo t('review');?></a></li>
            <li<?php echo $active_tab_class ?>><a href="<?php echo current_url();?>#tabs-2"><?php echo t('submit');?></a></li>
        </ul>
    <div id="tabs-1">
 
        <div style="font-size:14px;width:894px;text-align:right">
            Generate: <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=ddi">DDI</a> |
            <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=rdf">RDF</a> |
            <a target="_blank" href="<?php echo current_url();?>?print=yes"><?php echo t('print_preview'); ?></a> |
            <a href="javascript:void(0);" id="email_to_friend"><?php echo t('email_to_friend'); ?></a>
         </div>
     
  <?php $x = true; if ($project[0]->status == 'draft' && $this->uri->segment(1) != 'admin'): ?>
  	<?php $warnings=NULL;?>
	<?php foreach($fields['merged'] as $key => $value):?>
 		<?php if (!isset($row[0]->$key) || empty($row[0]->$key) || $row[0]->$key == '[]' || $row[0]->$key == ' ' || $row[0]->$key == '--' || $row[0]->$key == '0000-00-00'): ?>
			<?php $warnings[]= '<a class="mandatory" href="'. site_url('datadeposit/study'.'/'.$this->uri->segment(3).'#mandatory').'" >'. $value . 
				' is a mandatory field that is not filled.</a>'; ?>
        <?php else: 
            $key = str_replace('coverage_country', 'country', $key); 
            $key = str_replace('coll_dates', 'dates_datacollection', $key); ?> 
        	<?php echo '<script>$(function() {$("li.', strtolower($key), '").css("display", "none");});', '</script>', PHP_EOL; ?>
        <?php endif; ?>
		<?php $x = false; endforeach; ?> 
    <?php endif; ?>

	<?php if ($warnings):?>
    	<div class="warnings">
            <ul>
        <?php foreach($warnings as $warning):?>
        	<li><?php echo $warning;?></li>
        <?php endforeach;?>
        </ul>
        </div>
    <?php endif;?>

    <div class="field"> 
        <fieldset>
            <legend class="title"><?php echo t('project_info');?></legend> 
            <table class="tbl-border">
                <tr><th class="td-label">Title</th><td><?php echo $project[0]->title; ?></td></tr>
                <tr><th class="td-label">Description</th><td><?php echo $project[0]->description; ?></td></tr>
                <tr><th class="td-label">Collaboration</th><td><?php echo implode(", ",$project[0]->collaborators); ?></td></tr>
                <tr><th class="td-label">Project ID</th><td><?php echo $project[0]->id; ?></td></tr>
                <tr><th class="td-label">Created By</th><td><?php echo $project[0]->created_by; ?></td></tr>
                <tr><th class="td-label">Date Created On</th><td><?php echo date('Y-m-d', $project[0]->created_on) ?></td></tr>
                <tr><th class="td-label">Status</th><td><?php echo $project[0]->status; ?></td></tr>
            </table>
        </fieldset>
     </div>
     
     <?php if (!isset($row[0]->id)): ?>
     <p><?php echo t('no_study_found'); ?></p>
     <?php else: ?>

	<div class="field"> 
     <fieldset>
 	   <legend class="title"><?php echo t('study_desc');?></legend>
        
        <?php
			$fields=array();
			$fields['identification']=array(
					'Title'					=>form_prep(nl2br($row[0]->ident_title)),
					'Subtitle'				=>$row[0]->ident_subtitle,
					'Abbreviation'			=>$row[0]->ident_abbr,
					'Study Type'			=>$row[0]->ident_study_type,
					'Series Information'	=>$row[0]->ident_ser_info,
					'Translated Title'		=>$row[0]->ident_trans_title,
					'ID Number'				=>$row[0]->ident_id,
			);
			
			$fields['version']=array(
					'Description'			=>$row[0]->ver_desc,
					'Production Date'		=>date("Y-m-d",$row[0]->ver_prod_date),
					'Notes'					=>$row[0]->ver_notes,
			);
		
			$fields['overview']=array(
					'Abstract'				=>$row[0]->overview_abstract,
					'Kind of Data'			=>$row[0]->overview_kind_of_data,
					'Unit of Analysis'		=>$row[0]->overview_analysis,
			);
		
			$fields['scope']=array(
					'Description of Scope'	=>$row[0]->scope_definition,
			);

			$fields['coverage']=array(
					'Country'				=>$country,
					'Geographic Coverage'	=>$row[0]->coverage_geo,
					'Universe'				=>$row[0]->coverage_universe,
			);
						
			$fields['producers_and_sponsors']=array(
					'Primary Investigators'				=>$prim_investigator,
					'Other Producers'					=>$other_producers,
					'Funding'							=>$funding,
					'Other Acknowledgements'			=>$acknowledgements,
			);

			$fields['sampling']=array(
					'Sampling Procedure'				=>$row[0]->sampling_procedure,
					'Deviations from Sample Design'		=>$row[0]->sampling_dev,
					'Response Rate'						=>$row[0]->sampling_rates,
					'Weighting'							=>$row[0]->sampling_weight,
			);
			
			
			$fields['data_collection']=array(
					'Dates of Collection'		=>$dates_datacollection,
					'Time Periods'				=>$time_periods,
					'Mode of Data Collection'	=>$row[0]->coll_mode,
					'Notes of Data Collection'	=>$row[0]->coll_notes,
					'Questionnaires'			=>$row[0]->coll_questionnaire,
					'Data Collectors'			=>$data_collectors,
					'Supervision'				=>$row[0]->coll_supervision,					
			);


			$fields['data_processing']=array(
					'Data Editing'		=>$row[0]->process_editing,
					'Other Processing'	=>'TODO',
			);
			
			$fields['data_appraisal']=array(
					'Estimates of Sampling Error'		=>$row[0]->appraisal_error,
					'Other Forms of Data Appraisal'		=>$row[0]->appraisal_other,
			);
			

			$fields['data_access']=array(
					'Access Authority'		=>$access_authority,
					'Confidentiality'		=>$row[0]->access_confidentiality,
					'Access Conditions'		=>$row[0]->access_conditions,
					'Citation Requirement'	=>$row[0]->access_cite_require,
			);


			$fields['disclaimer_and_copyright']=array(
					'Disclaimer'		=>$row[0]->disclaimer_disclaimer,
					'Copyright'			=>$row[0]->disclaimer_copyright,
			);


			$fields['operational_information']=array(
					'operational_wb_name'		=>$row[0]->operational_wb_name,
					'operational_wb_id'			=>$row[0]->operational_wb_id,
					'operational_wb_net'		=>$row[0]->operational_wb_net,
					'operational_wb_sector'		=>$row[0]->operational_wb_sector,
					'operational_wb_summary'	=>$row[0]->operational_wb_summary,
					'operation_wb_objectives'	=>$row[0]->operational_wb_objectives,
			);


			$fields['impact-evaluation']=array(
					'impact_wb_name'		=>$row[0]->impact_wb_name,
					'impact_wb_id'			=>$row[0]->impact_wb_id,
					'impact_wb_area'		=>$row[0]->impact_wb_area,
					'impact_wb_lead'		=>$impact_wb_lead,
					'impact_wb_members'		=>$impact_wb_members,
					'impact_wb_description'	=>$row[0]->impact_wb_description,
			);

			$fields['contacts']=array(
					'Contact Persons'		=>$contacts,
			);


			//check all sections/fields to identify sections with no data		
			foreach($fields as $section_name=>$section)
			{
				$fields[$section_name]['is_empty']=TRUE;;
				foreach($section as $key=>$value)
				{
					//if a field has a value, mark this section non-empty
					if ($value)
					{
						$fields[$section_name]['is_empty']=FALSE;
					}
				}		
			}		
		?>
        
        <div class="study-description">
        <?php foreach($fields as $section_name=>$section):?>
        <?php if ($section['is_empty']){continue;}?>
        <div class="field"> 
        <fieldset>
            <legend><?php echo ($section_name);?></legend> 
            <div class="field">
                <table>
                <?php foreach($section as $key=>$value):?>
                    <?php if (!$value){continue;}?>
                    <tr>
                        <td class="td-label"><?php echo t($key);?></td>
                        <td><?php echo $value;?></td>
                    </tr>
                <?php endforeach;?>
                </table>
            </div>
        </fieldset>
	    </div>
        <?php endforeach;?>
		</div>
</fieldset>
</div>
<!--end-study-description-->        
        
<?php if (!empty($files)): ?>    
    <div class="field">
    <fieldset>
    <legend><?php echo t('data_files');?></legend>
	<table class="grid-table" >
        <tr valign="top" align="left" class="header">
            <th><?php echo t('name');?></th>
            <!--<th style="width:500px"><?php echo t('description');?></th>	-->
            <th><?php echo t('type');?></th>
            <?php if ($this->uri->segment(1) == 'admin'): ?>
            <th><?php echo t('download'); ?></th>
            <?php endif; ?>
        </tr>
		<?php $prefix = ""; ?>
		<?php foreach( $files as $file): ?>
        <tr valign="top">
            <td><?php echo $file['filename']; ?></td>
			<!--<td><?php //echo isset($file['description']) ? $file["description"] : 'N/A';?></td>-->
            <td><?php echo (isset($file['dctype'])) ? preg_replace('#\[.*?\]#', '', $file['dctype']) : 'N/A';?></td>
		    <?php if ($this->uri->segment(1) == 'admin'): ?>
            <td><?php echo "<a href=", site_url('datadeposit/download'), '/', $file['id'], ">Download</a>"; ?> </td> 
        	<?php endif; ?>
        </tr>
    	<?php endforeach;?>        
	</table>
    </fieldset>
    </div>
<?php endif; ?>

<?php if (isset($citations) && count($citations) >0 ): ?>

	<div class="field">
    <fieldset>
    <legend><?php echo t('citations');?></legend> 
	<div>
 	<table class="grid-table" cellspacing="0" cellpadding="0">
    	<tr class="header">
            <th><?php echo t('citation_type'); ?></th>
            <th><?php echo t('title'); ?></th>
        </tr>
		<?php $tr_class=""; ?>
		<?php foreach($citations as $row): ?>
    		<?php $row=(object)$row; //var_dump($row);exit;?>
			<?php //if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
            <tr class="<?php //echo $tr_class; ?>">
                <td><?php echo t($row->ctype); ?></td>
                <td><?php echo $row->title;?></td>
            </tr>
    <?php endforeach;?>
    </table>
    </div>
    </fieldset>
    </div>
<?php endif; ?>
<?php endif; ?>


</div>
<div id="tabs-2">

<script type="text/javascript">
$(function() {

$('form').submit(function() {
	$('.changedInput').removeClass('changedInput');
});

$('.button').click(function() {
	$('.changedInput').removeClass('changedInput');
});

$(window).on('load',function() {
	if ($('.is_embargoed').is(':checked')) {
		$('.embargoed').css('display', 'block');
	}
});

$('input[name="is_embargoed"]').change(function() {
	if ($('.embargoed').css('display') == 'none') {
		$('.embargoed').css('display', 'block');
	} else {
		$('.embargoed').css('display', 'none');
	}
});
$('img[alt="help"]').click(function() {
	var help_item = $(this).parent().parent().next('.HelpMsg');
	if (help_item.css('display') == 'none') {
		help_item.css('display', 'block');
	} else {
		help_item.css('display', 'none');
	}
});
});
</script>
<style text="test/css">
</style>
 <div style="margin: 10px 0 15px 5px"><?php echo ($project[0]->access == 'owner') ? t('instructions_project_submit') : t('instructions_project_contributor_review'); ?></div>
	<?php echo form_open("datadeposit/submit_review/{$project[0]->id}", 'class="form clearfix"');?>

    <div class="field">

    <label for="accesspolicy"><sup style="font-size:11.4pt;color:#ff0000">*</sup>Choose an appropriate access policy <a class="accesspolicyHelp" href="" onclick="return false;"><img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg accesspolicyHelpMsg" style="display:none;">

    <?php echo t('suggested_access_policy_help'); ?>
    </div>

	<select name="access_policy">
<option  <?php if (isset($project[0]->access_policy) && $project[0]->access_policy == "--") echo 'selected="selected"'; ?> value="--">--</option>
<option  <?php if (isset($project[0]->access_policy) && $project[0]->access_policy == "Direct Access") echo 'selected="selected"'; ?> value="Direct Access">Direct Access</option>
<option  <?php if (isset($project[0]->access_policy) && $project[0]->access_policy == "Public Use Files") echo 'selected="selected"'; ?> value="Public Use Files">Public Use Files</option>
<option  <?php if (isset($project[0]->access_policy) && $project[0]->access_policy == "Licensed Access") echo 'selected="selected"'; ?> value="Licensed Access">Licensed Access</option>
<option  <?php if (isset($project[0]->access_policy) && $project[0]->access_policy == "Data Enclave") echo 'selected="selected"'; ?> value="Data Enclave">Data Enclave</option>
<option  <?php if (isset($project[0]->access_policy) && $project[0]->access_policy == "Not Defined") echo 'selected="selected"'; ?> value="Not Defined">Not Defined</option>
<!--<option value="No Access">No Access</option>-->
</select>    </div>


    <div class="field">
	<label for="to_catalog"><sup style="font-size:11.4pt;color:#ff0000">*</sup><?php echo t('catalog_to_publish'); ?> <a class="accesspolicyHelp" href="" onclick="return false;"><img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>
 <div class="HelpMsg accesspolicyHelpMsg" style="display:none;">


	<?php echo t('catalog_to_publish_help'); ?>	
    </div>
    <select name="to_catalog">
	<option <?php if (isset($project[0]->to_catalog) && $project[0]->to_catalog == "--") echo 'selected="selected"'; ?> value="--">--</option>
	<option <?php if (isset($project[0]->to_catalog) && $project[0]->to_catalog == "internal") echo 'selected="selected"'; ?> value="internal"><?php echo t('internal'); ?></option>
	<option <?php if (isset($project[0]->to_catalog) && $project[0]->to_catalog == "external") echo 'selected="selected"'; ?> value="external"><?php echo t('external'); ?></option>    
    </select>
    </div>
	<label for="embargoed"><?php echo t('embargoed'); ?> <a class="accesspolicyHelp" href="" onclick="return false;"><img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>
<div class="HelpMsg accesspolicyHelpMsg" style="display:none;">


	<?php echo t('is_embargoed_help'); ?>	
    </div>
	<input class="is_embargoed" type="checkbox" <?php if (isset($project[0]->is_embargoed) && (int)$project[0]->is_embargoed !== 0) echo 'checked="checked"'; ?>  name="is_embargoed" value="<?php echo t('embargoed'); ?>">

    <div class="embargoed field">

    <label for="embargoed"><?php echo t('notes_to_embargoed'); ?> <a class="accesspolicyHelp" href="" onclick="return false;"><img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>

 <div class="HelpMsg accesspolicyHelpMsg" style="display:none;">

   <?php echo t('notes_to_embargoed_help'); ?>
    </div>

    <textarea name="embargoed"  class="input-flex" ><?php if (isset($project[0]->embargoed)) echo $project[0]->embargoed; ?></textarea>

    </div>
    
    <div class="field">

    <label for="title"><?php echo t('disclosure_risk'); ?><a class="accesspolicyHelp" href="" onclick="return false;"> <img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg submitccHelpMsg" style="display:none;">

  <?php echo t('disclosure_risk_help'); ?>
    </div>

    <input name="disclosure_risk" type="text" id="ccsubmit" class="input-flex" value="<?php if (isset($project[0]->disclosure_risk)) echo $project[0]->disclosure_risk; ?>"/>

    </div>

    <div class="field">

    <label for="notes_to_library"><?php echo t('notes_to_library'); ?><a class="notes_to_libraryHelp" href="" onclick="return false;"><img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>
    <div class="HelpMsg notes_to_libraryHelpMsg" style="display:none;">

	<?php echo t('notes_to_library_help'); ?>

    </div>

    <textarea name="library_notes"  class="input-flex" ><?php if (isset($project[0]->library_notes)) echo $project[0]->library_notes; ?></textarea>

    </div>

    

    <div class="field">

    <label for="title">CC <a class="submitccHelp" href="" onclick="return false;"><img src="<?php echo site_url(); ?>/../images/icon_question.gif"  alt="help" title="help"/></a></label>

    <div class="HelpMsg submitccHelpMsg" style="display:none;">

	<?php echo t('cc_help'); ?>

    </div>

    <input name="cc" type="text" id="ccsubmit" class="input-flex" value="<?php if (isset($project[0]->cc)) echo $project[0]->cc; ?>"/>

    </div>
    
        <div class="field clearfix">
     
     <script type="text/javascript">
		$(function() {
			$("#first_submit").click(function() {
				$(this).css('display', 'none');
				$("#confirm").css('display', 'block');
			});
			$("#cancel").click(function() {
				$("#confirm").css('display', 'none');
				$("#first_submit").css('display', 'block');
			});
		});
	 </script>
                 
    <input type="hidden" name="submit_project" value="Save and submit" id="save" class="button <?php echo ($project[0]->access == 'owner') ? t('submit') : t('save'); ?>"/>
    <?php if ($project[0]->access == 'owner'): ?>
    <div id="confirm" style="display:none;padding:10px">
    <p><?php echo t('confirm_submission'); ?></p>
                    <div id="final_submit" onclick="$('input#draft').remove();$(this).remove();$('.form').submit();"style="width:120px" class="button">
                        <span><?php echo t('confirm_submit'); ?></span>
                    </div>
                    <div id="cancel" onclick="javascript:cancel();"style="width:70px" class="button">
                        <span><?php echo t('cancel'); ?></span>
                    </div>                        
	<br /><br />
    </div>
	<?php endif; ?>
	
                    <div <?php if ($project[0]->access == 'owner') echo 'id="first_submit"'; ?> onclick="<?php if ($project[0]->access != 'owner') echo '$(\'.form\').submit();'; ?>"style="width:100px" class="button">
                        <span><?php echo ($project[0]->access == 'owner') ? t('submit') : t('save'); ?></span>
                    </div>

    </div>

</div>


    <?php echo form_close(); ?>
    </div>
</div>  
<!--</div>-->

 <script type="text/javascript">
	$(document).ready(function() {

		/* Help doing fieldset expand and collapse*/
		$('.field-expanded > legend').click(function(e) {
			e.preventDefault();
			$(this).parent('fieldset').toggleClass("field-collapsed");
			return false;
		});
		
		// This will hide fieldset on loading page
		
		$(document).ready(function() {
			$('.field-expanded > legend').parent('fieldset').toggleClass('field-collapsed');

			$("#citation_type").change(function(){
				$("#change_type").click();
			});
		});
		
		
	});

</script>
