<!--[if IE 7]>
<style>
.dd-study-section .section-icons{
	top:7px;
}
#iconic_functions .section-icons{
left:0px;
}
</style>
<![endif]-->
<style>
    .dd_required{
        font-style:italic;
        color:red;
        font-size:12px;
        font-weight:normal;
        margin-left:20px;
    }
</style>
<?php 
  	$section_actions='';
   $section_actions.='<span class="dd-icon show-help" title="'.t('show_help_tooltip').'"><span class="icon-help"></span>Help</span>';
   $section_actions.='<span class="dd-icon show-all-fields"  title="'.t('show_fields_tooltip').'"><span class="icon-all-fields"></span>Show all fields</span>';
   $section_actions.='<span class="dd-icon show-recommended"  title="'.t('mandatory_recommended_tooltip').'"><span class="icon-recommended"></span>Recommended fields</span>';
   $section_actions.='<span class="dd-icon show-mandatory"  title="'.t('mandatory_only_tooltip').'"><span class="icon-mandatory"></span>Mandatory fields</span>';
   $section_actions.='<span class="dd-icon save"  title="'.t('save').'"><span class="icon-save"></span>Save</span>';
	//var_dump($merged['recommended_fields']);
?>

<?php 
    $dup=str_replace(array_keys($map), array_values($map), current($fields));
    $show_additional_fields=$this->config->item('additional_fields','datadeposit'); 
    $show_help_text=$this->config->item('show_help','datadeposit'); 
    $sections_collapsed=$this->config->item('sections_collapsed','datadeposit'); 

    //collapsed by default
    $field_toggle_class='field-collapsed';

    if($sections_collapsed===false){
        $field_toggle_class='field-expanded';
    }
    
?>
<script type="text/javascript">


$(function() {
    // Import Metadata button//
    $(document.body).on("click","#btn-import-metadata", function (e){
        //no project selected
        if (!$('#user-projects-list :selected').val()){
            alert('No project selected!');return;
        }
        if (confirm("<?php echo t('confirm_metadata_import'); ?>")) {
            $.get("<?php echo site_url('datadeposit/import_from_project/'); ?>?from="+$('#user-projects-list :selected').val()+"&to=<?php echo $this->uri->segment(3);?>", null, function(data) {
                if (data == 'fail') {
                    alert("<?php echo t('fail_import');?>");
                } else if (data == 'success') {
                    alert("<?php echo t('success_import');?>");
                    $('.changedInput').removeClass('changedInput');
                    document.location.href="<?php echo current_url();?>";
                }
            });
        }

    });

	/* Cleanup Text Boxes */
	$('input').click(function() {
		if ($(this).val() == ' ') {
			$(this).val('');
		}
	});
	$('form').submit(function() {
		$('input').each(function() {
			if ($(this).val() == ' ') {
				$(this).val('');
			}
		});
	});

 });
</script>

<script type="text/javascript">

$(function(){
	$('.button-del').click(function() {
		$(this).parent().parent().remove();
	});
});

//mark fields as "changed" to save changes when user moves to another page without saving
(function($) {
    $.fn.checkChanges = function(message, grid) {
        var _self  = this;
		var events = (grid) ? 'click' : 'keyup change keydown'; 
        $(_self).bind(events, function(e) {
            $(this).addClass('changedInput');
        });
        	$(window).bind('beforeunload ', function() {
         		if ($('.changedInput').length) {
                	return message;
            	}
        	});
    };
})(jQuery);

$('form').submit(function() {
	$('.changedInput').removeClass('changedInput');
});

$('#submit_study').click(function() {
	$('.changedInput').removeClass('changedInput');
});



$('input, textarea, select[id!="import_metadata"]').checkChanges('Your data will be unsaved.', false);
$('.button-add').checkChanges('Your data will be unsaved.', true);


function toggle_import_list() {
	if ($('#import_metadata').css('display') == 'none') {
		$('#import_metadata').css('display', 'block');
	} else {
		$('#import_metadata').css('display', 'none');
	}
}

//show mandatory fields expanded on page load
$(function() {
	if (window.location.hash == '#mandatory') {
		$(window).on('load',function() {
			$('.section-icons img').eq(3).click();
			expand_all(Section);
		});
	}
});

</script>

<?php $message=isset($message)?$message:$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>


<div class="instruction-box"><p><?php echo t('study_help'); ?></p></div>

<!-- buttons for import metadata, expand/collapse-->
<div class="dd-actions-container">
    <!-- settings button -->
    <div class="buttons-group-left btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <span class="glyphicon glyphicon-cog" aria-hidden="true"></span> Settings <span class="caret"></span>
        </button>
        <ul class="dropdown-menu" role="menu">
            <li><span class="show-help">Show/hide field help</span></li>
            <li class="divider"></li>
            <li><span class="show-all-fields">Show all fields</span></li>
            <li class="divider"></li>
            <li><span class="show-recommended">Recommended fields</span></li>
            <li class="divider"></li>
            <li><span class="show-mandatory">Mandatory fields</span></li>
        </ul>
    </div>

    <div class="action-buttons buttons-group-right">
	<?php if ($project[0]->access == 'owner'): ?>
       <div onclick="javascript:toggle_import_list();" class="btn btn-primary import-data">
            <span>Import Metadata</span>
        </div>
        <?php /*
        <select id="import_metadata" style="float:left;display:none;width:120px" name="dctype">
		<option value="">--Select project--</option>
		<?php foreach($projects as $list):
			if ($list->id == null || $list->id === $project[0]->id) {
				continue;
			}
		 ?>
        <option value="<?php echo $list->id; ?>" ><?php echo substr((isset($list->shortname) && !empty($list->shortname)) ? strtoupper($list->shortname) : ($list->title), 0, 15) ; ?></option>
        <?php endforeach; ?>
        </select>
        */?>
    <?php endif; ?>
    
    <div class="btn btn-primary expand-all"><span>Expand All</span></div>
    <div class="btn btn-primary collapse-all"><span>Collapse All</span></div>
</div>    
</div>



<div class="alert alert-warning alert-dismissible project-import-dialog" role="alert" >
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4>Import Metadata</h4>
    <div>To import metadata from another project, select the project from the list and press "Import".</div>
    <?php if ($project[0]->access == 'owner'): ?>
        <select id="user-projects-list" name="dctype">
            <option value="">--Select project--</option>
            <?php foreach($projects as $list):?>
                <?php if ($list->id == null || $list->id === $project[0]->id) {continue;}?>
                <option value="<?php echo $list->id; ?>" ><?php echo $list->title. '('.strtoupper($list->shortname) .')' ; ?></option>
                <!--<option value="<?php echo $list->id; ?>" ><?php echo substr((isset($list->shortname) && !empty($list->shortname)) ? strtoupper($list->shortname) : ($list->title), 0, 15) ; ?></option>-->
            <?php endforeach; ?>
        </select>
        <div style="margin-top:5px;">
            <input type="button" class="btn btn-primary" value="Import" id="btn-import-metadata"/>
            <span class="btn btn-cancel">Cancel</span>
        </div>
    <?php endif; ?>

</div>

<div id="toTop">^ Back to Top</div>


<div class="dd-edit-study-description">

<?php echo form_open("datadeposit/study/{$project[0]->id}", "id='form'"); ?>
<div class="dd-study-section <?php echo $field_toggle_class;?>">

    <div id="Identification" class="section-header">
    	<div class="section-title"><?php echo t('identification');?></div>

    </div>
    
    <div class="section-body">
    <div class="field">
        <label id="ident_title" for="ident_title"><?php echo t('title'); ?></label>
        <div class="HelpMsg titlHelpMsg" style="display:none;">
        <?php echo wordwrap(t('titl'),80); ?>
        </div>
        <input  type="text" name="ident_title" class="input-flex" value="<?php echo set_value('ident_title',$project[0]->title); ?>"/>
    </div>
    <div class="field">
        <label id="ident_subtitle" for="ident_subtitle"><?php echo t('subtitle'); ?></label>
        <div class="HelpMsg titlHelpMsg" style="display:none;">
        <?php echo wordwrap(t('help_subtitle'),80); ?>
        </div>
        <input type="text" name="ident_subtitle" class="input-flex" value="<?php echo set_value('ident_subtitle',$row[0]->ident_subtitle); ?>"/>
    </div>
    <div class="field">
        <label id="ident_abbr" for="ident_abbr"><?php echo t('abbreviation'); ?></label>
        <div class="HelpMsg altTitlHelpMsg" style="display:none">
        <?php echo wordwrap(t('altTitl'),80); ?>
        </div>
        <input type="text" name="ident_abbr" class="input-flex" value="<?php echo set_value('ident_abbr',$row[0]->ident_abbr); ?>"/>
    </div>	
    <div class="field">
        <label id="ident_study_type" for="ident_study_type" ><?php echo t('study_type'); ?></label>
        <div class="HelpMsg serNameHelpMsg" style="display:none">
        <?php echo wordwrap(t('serName'),80); ?>
        </div>
        <br />
        <?php echo form_dropdown('ident_study_type', $studytype, set_value('ident_study_type',$row[0]->ident_study_type)); ?>
        
    </div>	
    <div class="field">
        <label id="ident_ser_info" for="ident_ser_info"><?php echo t('series_information'); ?></label>
        <div class="HelpMsg serInfoHelpMsg" style="display:none">
        <?php echo wordwrap(t('serInfo'),80); ?>
        </div>
        <textarea  name="ident_ser_info" cols="30" class="input-flex"><?php echo set_value('ident_ser_info',$row[0]->ident_ser_info); ?></textarea>
    </div>
    <div class="field">
        <label id="ident_trans_title" for="ident_trans_title"><?php echo t('translated_title'); ?></label>
        <div class="HelpMsg parTitlHelpMsg" style="display:none">
        <?php echo wordwrap(t('parTitl'),80); ?>
        </div>
        <input type="text" name="ident_trans_title" class="input-flex" value="<?php echo set_value('ident_trans_title',$row[0]->ident_trans_title);?>"/>
    </div>
    </div><!--end-section-header-->
</div>


<div class="dd-study-section <?php echo $field_toggle_class;?>">

    <div id="Identification" class="section-header">
        <div class="section-title"><?php echo t('versions');?></div>

    </div>
             
    <div class="section-body">

        <div class="field">
            <label id="ver_desc" for="ver_desc"><?php echo t('description'); ?></label>
            <div class="HelpMsg versionHelpMsg" style="display:none"><?php echo wordwrap(t('version'),80); ?></div>
            <textarea name="ver_desc" cols="30" rows="5" class="input-flex"><?php echo set_value('ver_desc',$row[0]->ver_desc); ?></textarea>
        </div>
        
        <?php  if(isset($row[0]->ver_prod_date)) {
                $obj = $row[0]->ver_prod_date;
                $v_idate = is_object($obj) ? explode("-", $obj->format('Y-m-d')) : explode('-',date('Y-m-d', $obj));
        } ?>
    
        <div class="field field-inline">
        	<label id="version_idate" for="version_idate"><?php echo t('production_date'); ?></label>
        	<div class="HelpMsg version_idateHelpMsg" style="display:none">
        		<?php echo wordwrap(t('version_idate'),80); ?>
        	</div>
    		<br />
    		<span style="margin:0 4px;">MM:</span>
            <input size="2" maxlength="2" name="ver_prod_date_month" type="text" id="dcdate" size="50" class="input-flexx"  value="<?php echo isset($v_idate[1])?$v_idate[1]:''; ?>"/>
    		<span style="margin:0 4px;">DD:</span>
            <input size="2" maxlength="2" name="ver_prod_date_day" type="text" id="dcdate" size="50" class="input-flexx"  value="<?php echo isset($v_idate[2])?$v_idate[2]:''; ?>"/>    
    		<span style="margin:0 4px;">YYYY:</span>
            <input size="4" maxlength="4" name="ver_prod_date_year" type="text" id="dcdate" size="50" class="input-flexx"  value="<?php echo isset($v_idate[0])?$v_idate[0]:''; ?>"/>
    	</div>
    
    	<div class="field">
    		<label id="ver_notes" for="ver_notes"><?php echo t('notes'); ?></label>
    		<div class="HelpMsg notesHelpMsg" style="display:none"><?php echo wordwrap(t('version_notes'),80); ?></div>
    		<textarea name="ver_notes" cols="30" rows="5" class="input-flex"><?php echo set_value('ver_notes',$row[0]->ver_notes); ?></textarea>
    	</div>
        
    </div>
</div>


<div class="dd-study-section <?php echo $field_toggle_class;?>">
	<div id="Overview" class="section-header">
		<div class="section-title"><?php echo t('overview');?></div>

    </div>
    
    <div class="section-body">    
        <div class="field">
            <label id="overview_abstract" for="overview_abstract"><?php echo t('abstract'); ?></label>
            <div class="HelpMsg abstractHelpMsg" style="display:none">
            <?php echo wordwrap(t('overview_abstract'),80); ?>
            </div>
            <textarea name="overview_abstract" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->overview_abstract)?$row[0]->overview_abstract:''; ?></textarea>
        </div>
        <div class="field">
            <label id="overview_kind_of_data" for="overview_kind_of_data"><?php echo t('kind_of_data'); ?></label>
            <div class="HelpMsg dataKindHelpMsg" style="display:none">
            <?php echo wordwrap(t('dataKind'),80); ?>
            </div>
            <br />
            <?php echo form_dropdown('overview_kind_of_data', $kindofdata, isset($row[0]->overview_kind_of_data)?$row[0]->overview_kind_of_data:''); ?> 
        </div>
        <div class="field">
            <label id="overview_analysis" for="overview_analysis"><?php echo t('unit_of_analysis'); ?></label>
            <div class="HelpMsg anlyUnitHelpMsg" style="display:none">
            <?php echo wordwrap(t('anlyUnit'),80); ?>
            </div>
            <textarea name="overview_analysis" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->overview_analysis)?$row[0]->overview_analysis:''; ?></textarea>
        </div>                 
	</div>
    
</div>

<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Scope"  class="section-header">
        <div class="section-title"><?php echo t('scope');?></div>

     </div>   
     
    <div class="section-body">
	    <div class="field">
    		<label id="scope_definition" for="scope_definition"><?php echo t('description_of_scope'); ?></label>
    		<div class="HelpMsg scope_notesHelpMsg" style="display:none"><?php echo wordwrap(t('scope_notes'),80); ?></div>
    		<textarea name="scope_definition" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->scope_definition)?$row[0]->scope_definition:''; ?></textarea>
    	</div>
    </div>
</div>

<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Coverage"  class="section-header">
        <div class="section-title"><?php echo t('coverage');?></div>

     </div>   
     
    <div class="section-body">                
                    <div class="field">
                        <label id="coverage_country" for="coverage_country"><?php echo t('country'); ?></label>
                        <div class="HelpMsg nationHelpMsg" style="display:none">
                        <?php echo wordwrap(t('nation'),80); ?>
                        </div>
						<?php echo $country ?>
                    </div>
                    <div class="field">
                        <label id="coverage_geo" for="coverage_geo"><?php echo t('geographic_coverage'); ?></label>
                        <div class="HelpMsg geogCoverHelpMsg" style="display:none">
                        <?php echo wordwrap(t('geogCover'),80); ?>
                        </div>
                        <textarea name="coverage_geo" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->coverage_geo)?$row[0]->coverage_geo:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="coverage_universe" for="coverage_universe"><?php echo t('universe'); ?></label>
                        <div class="HelpMsg universeHelpMsg" style="display:none">
                        <?php echo wordwrap(t('country_universe'),80); ?>
                        </div>
                        <textarea name="coverage_universe" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->coverage_universe)?$row[0]->coverage_universe:''; ?></textarea>
                    </div>
                </div>
</div>                
                
                

<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Producers_Sponsers"  class="section-header">
        <div class="section-title"><?php echo t('producers_and_sponsors');?></div>

     </div>   
     
    <div class="section-body">                

                    <div class="field">
                        <label id="prod_s_investigator" for="prod_s_investigator"><?php echo t('primary_investigator'); ?></label>
                        <div class="HelpMsg AuthEntyHelpMsg" style="display:none">
                        <?php echo wordwrap(t('AuthEnty'),80); ?>
                        </div>
						<?php echo $prim_investigator; ?>
                    	<br /><br />
                    </div>
                     <div class="field">
                        <label id="prod_s_other_prod" for="prod_s_other_prod"><?php echo t('other_producers'); ?></label>
                        <div class="HelpMsg producerHelpMsg" style="display:none">
                        <?php echo wordwrap(t('producers'),80); ?>
                        </div>
						<?php echo $other_producers; ?>
                        <br /><br />
                    </div>
                    <div class="field">
                        <label id="prod_s_funding" for="prod_s_funding"><?php echo t('funding'); ?></label>
                        <div class="HelpMsg fundAgHelpMsg" style="display:none">
                        <?php echo wordwrap(t('fundAg'),80); ?>
                        </div>
						<?php echo $funding; ?>
                        <br /><br />
                    </div>
                    <div class="field">
                        <label id="prod_s_acknowledgements" for="prod_s_acknowledgements"><?php echo t('other_acknowledgements'); ?></label>
                        <div class="HelpMsg othld_pHelpMsg" style="display:none">
                        <?php echo wordwrap(t('othId_p'), 80); ?>
                        </div>
						<?php echo $acknowledgements; ?>
                        <br /><br />
                    </div>
                </div>
</div>


<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Sampling" class="section-header">
            <div class="section-title"><?php echo t('sampling');?></div>

    </div>
    
    <div class="section-body">

                    <div class="field">
                        <label id="sampling_procedure" for="sampling_procedure"><?php echo t('sampling_procedure'); ?></label>
                        <div class="HelpMsg sampProcHelpMsg" style="display:none">
                        <?php echo wordwrap(t('sampProc'), 80); ?>
                        </div>
                        <textarea name="sampling_procedure" cols="30" rows="8" class="input-flex"><?php echo isset($row[0]->sampling_procedure)?$row[0]->sampling_procedure:''; ?></textarea>
                    </div>
                     <div class="field">
                        <label id="sampling_dev" for="sampling_dev"><?php echo t('deviations_from_sample_design'); ?></label>
                        <div class="HelpMsg deviatHelpMsg" style="display:none">
                        <?php echo wordwrap(t('deviat'), 80); ?>
                        </div>
                        <textarea name="sampling_dev" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->sampling_dev)?$row[0]->sampling_dev:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="sampling_rates" for="sampling_rates"><?php echo t('response_rates'); ?></label>
                        <div class="HelpMsg respRateHelpMsg" style="display:none">
                        <?php echo wordwrap(t('respRate'), 80); ?>
                        </div>
                        <textarea name="sampling_rates" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->sampling_rates)?$row[0]->sampling_rates:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="sampling_weight" for="sampling_weight"><?php echo t('weighting'); ?></label>
                        <div class="HelpMsg weightHelpMsg" style="display:none">
                        <?php echo wordwrap(t('weight'), 80); ?>
                        </div>
                        <textarea name="sampling_weight" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->sampling_weight)?$row[0]->sampling_weight:''; ?></textarea>
                    </div>
                </div>
</div>




	<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Data_Collection"  class="section-header">
        <div class="section-title"><?php echo t('data_collection');?></div>

     </div>   
     
    <div class="section-body">
                    <div class="field">
                        <label id="coll_dates" for="coll_dates"><?php echo t('dates_of_data_collection'); ?> (yyyy/mm/dd)</label>
                        <div class="HelpMsg collDateHelpMsg" style="display:none">
                        <?php echo wordwrap(t('collDate'), 80); ?>
                        </div>
						<?php echo $dates_datacollection; ?>
                        <br /><br /><br />
                    </div>
                     <div class="field">
                        <label id="coll_periods" for="coll_periods"><?php echo t('time_periods'); ?>(yyyy/mm/dd)</label>
                        <div class="HelpMsg timePrdHelpMsg" style="display:none">
                        <?php echo wordwrap(t('timePrd'), 80); ?>
                        </div>
						<?php echo $time_periods; ?>
                        <br /><br /><br />
                    </div>
                    <div class="field">
                        <label id="coll_mode" for="coll_mode" ><?php echo t('mode_of_data_collection'); ?></label>
                        <div class="HelpMsg collModeHelpMsg" style="display:none">
                        <?php echo wordwrap(t('collMode'), 80); ?>
                        </div>
                        <br />
                        <select name="coll_mode">
                        <?php if (isset($row[0]->coll_mode) && $row[0]->coll_mode != '--'): ?><option value="--">--</option><?php endif; ?>
                          <?php if (isset($row[0]->coll_mode) && !empty($row[0]->coll_mode)): ?>  
													<option selected="selected" value="<?php if (isset($row[0]->coll_mode) && !empty($row[0]->coll_mode)) echo $row[0]->coll_mode;?>"> <?php if (isset($row[0]->coll_mode)) echo $row[0]->coll_mode;?></option>
                         <?php endif; ?>
<option value="Computer Assisted Personal Interview [capi]">Computer Assisted Personal Interview [capi]</option>
<option value="Computer Assisted Telephone Interview [cati]">Computer Assisted Telephone Interview [cati]</option>
<option value="Face-to-face [f2f]">Face-to-face [f2f]</option>
<option value="Mail Questionnaire [mail]">Mail Questionnaire [mail]</option>
<option value="Focus Group [foc]">Focus Group [foc]</option>
<option value="Internet [int]">Internet [int]</option>
<option value="Other [oth]">Other [oth]</option>
</select>                                      </div>
                    <div class="field">
                        <label id="coll_notes" for="coll_notes"><?php echo t('notes_on_data_collection'); ?></label>
                        <div class="HelpMsg collSituHelpMsg" style="display:none">
                        <?php echo wordwrap(t('collSitu'),80); ?>
                        </div>
                        <textarea name="coll_notes" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->coll_notes)?$row[0]->coll_notes:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="coll_questionnaire" for="coll_questionnaire"><?php echo t('questionnaires'); ?></label>
                        <div class="HelpMsg resInstruHelpMsg" style="display:none">
                        <?php echo wordwrap(t('resInstru'),80); ?>
                        </div>
                        <textarea name="coll_questionnaire" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->coll_questionnaire)?$row[0]->coll_questionnaire:''; ?></textarea>
                    </div>
                     <div class="field">
                        <label id="coll_collectors" for="coll_collectors"><?php echo t('data_collectors'); ?></label>
                        <div class="HelpMsg dataCollectorHelpMsg" style="display:none">
                        <?php echo wordwrap(t('dataCollector'),80); ?>
                        </div>
						<?php echo $data_collectors; ?>
                    </div>
                    <br /><br />
                    <div class="field">
                        <label id="coll_supervision" for="coll_supervision"><?php echo t('supervision'); ?></label>
                        <div class="HelpMsg actMinHelpMsg" style="display:none">
                        <?php echo wordwrap(t('actMin'),80); ?>
                        </div>
                        <textarea name="coll_supervision" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->coll_supervision)?$row[0]->coll_supervision:''; ?></textarea>
                    </div>
                </div>
</div>


	<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Data_Processing"  class="section-header">
        <div class="section-title"><?php echo t('data_processing');?></div>

     </div>   
     
	<div class="section-body">
		<div class="field">
                        <label id="process_editing" for="process_editing"><?php echo t('data_editing'); ?></label>
                        <div class="HelpMsg cleanOpsHelpMsg" style="display:none">
                        <?php echo wordwrap(t('cleanOps'),80); ?>
                        </div>
                        <textarea name="process_editing" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->process_editing)?$row[0]->process_editing:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="process_other" for="process_other"><?php echo t('other_processing'); ?></label>
                        <div class="HelpMsg method_notesHelpMsg" style="display:none">
                        <?php echo wordwrap(t('method_notes'),80); ?>
                        </div>
                        <textarea name="process_other" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->process_other)?$row[0]->process_other:''; ?></textarea>
                    </div>
                </div>
</div>


	<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Data_Appraisal"  class="section-header">
        <div class="section-title"><?php echo t('data_appraisal');?></div>

     </div>   
     
    <div class="section-body">                

                    <div class="field">
                        <label id="appraisal_error" for="appraisal_error"><?php echo t('estimates_of_sampling_error'); ?></label>
                        <div class="HelpMsg EstSmpErrHelpMsg" style="display:none">
                        <?php echo wordwrap(t('EstSmpErr'),80); ?>
                        </div>
                        <textarea name="appraisal_error" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->appraisal_error)?$row[0]->appraisal_error:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="appraisal_other" for="appraisal_other"><?php echo t('other_forms_of_data_appraisal'); ?></label>
                        <div class="HelpMsg dataApprHelpMsg" style="display:none">
                        <?php echo wordwrap(t('dataAppr'),80); ?>
                        </div>
                        <textarea name="appraisal_other" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->appraisal_other)?$row[0]->appraisal_other:''; ?></textarea>
                    </div>
                </div>
</div>

	<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Data_Access"  class="section-header">
        <div class="section-title"><?php echo t('data_access');?></div>

     </div>   
     
    <div class="section-body">                

                    <div class="field">
                        <label id="access_authority" for="access_authority"><?php echo t('access_authority'); ?></label>
                        <div class="HelpMsg useStmt_contactHelpMsg" style="display:none">
                        <?php echo wordwrap(t('useStmt_contact'),80); ?>
                        </div>
						<?php echo $access_authority; ?>
                    </div>
                    <br /><br />
                     <div class="field">
                        <label id="access_confidentiality" for="access_confidentiality"><?php echo t('confidentiality'); ?></label>
                        <div class="HelpMsg confDecHelpMsg" style="display:none">
                        <?php echo wordwrap(t('confDec'),80); ?>
                        </div>
                        <textarea name="access_confidentiality" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->access_confidentiality)?$row[0]->access_confidentiality:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="access_conditions" for="access_conditions"><?php echo t('access_conditions'); ?></label>
                        <div class="HelpMsg conditionsHelpMsg" style="display:none">
                        <?php echo wordwrap(t('conditions'),80); ?>
                        </div>
                        <textarea name="access_conditions" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->access_conditions)?$row[0]->access_conditions:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="access_cite_require" for="access_cite_require"><?php echo t('citations_requirement'); ?></label>
                        <div class="HelpMsg citReqHelpMsg" style="display:none">
                        <?php echo wordwrap(t('citReq'),80); ?>
                        </div>
                        <textarea name="access_cite_require" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->access_cite_require)?$row[0]->access_cite_require:''; ?></textarea>
                    </div>
                </div>
</div>



	<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Disclaimer"  class="section-header">
        <div class="section-title"><?php echo t('disclaimer_and_copyright');?></div>

     </div>   
     
    <div class="section-body">                

                    <div class="field">
                        <label id="disclaimer_disclaimer" for="disclaimer_disclaimer"><?php echo t('disclaimers'); ?></label>
                        <div class="HelpMsg disclaimerHelpMsg" style="display:none">
                        <?php echo wordwrap(t('disclaimer'),80); ?>
                        </div>
                        <textarea name="disclaimer_disclaimer" cols="30" rows="5" class="input-flex"><?php echo isset($row[0]->disclaimer_disclaimer)?$row[0]->disclaimer_disclaimer:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="disclaimer_copyright" for="disclaimer_copyright"><?php echo t('copyrights'); ?></label>
                        <div class="HelpMsg copyrightHelpMsg" style="display:none">
                        <?php echo wordwrap(t('copyright'),80); ?>
                        </div>
                        <input type="text" name="disclaimer_copyright" class="input-flex" value="<?php echo isset($row[0]->disclaimer_copyright)?$row[0]->disclaimer_copyright:''; ?>"/>
                    </div>
                </div>
</div>


    <?php if($show_additional_fields):?>    
    <div class="dd-study-section <?php echo $field_toggle_class;?>" >
    <div id="Operational-Information"  class="section-header">
        <div class="section-title"><?php echo t('operational_information');?></div>

     </div>   
     
    <div class="section-body">                
                    <div class="field">
                        <label id="operational_wb_name" for="operational_wb_name"><?php echo t('operational_wb_name'); ?></label>
                        <div class="HelpMsg titlHelpMsg" style="display:none;">
                        <?php echo wordwrap(t('operational_wb_name_help'),80); ?>
                        </div>
                        <input type="text" name="operational_wb_name" class="input-flex" value="<?php echo (isset($row[0]->operational_wb_name)) ? $row[0]->operational_wb_name : ''; ?>"/>
                    </div>
                    <div class="field">
                        <label id="operational_wb_id" for="operational_wb_id"><?php echo t('operational_wb_id'); ?></label>
                        <div class="HelpMsg titlHelpMsg" style="display:none;">
                        <?php echo wordwrap(t('operational_wb_id_help'),80); ?>
                        </div>
                        <input type="text" name="operational_wb_id" class="input-flex" value="<?php echo isset($row[0]->operational_wb_id) ? $row[0]->operational_wb_id : ''; ?>"/>
                    </div>
                    <div class="field">
                        <label id="operational_wb_net" for="operational_wb_net"><?php echo t('operational_wb_net'); ?></label>
                        <div class="HelpMsg altTitlHelpMsg" style="display:none">
                        <?php echo wordwrap(t('operational_wb_net_help'),80); ?>
                        </div>
                        <br />
                        <select name="operational_wb_net">														
                        <option value="--">--</option>
                          <?php if (isset($row[0]->operational_wb_net) && !empty($row[0]->operational_wb_net)): ?>  
													<option selected="selected" value="<?php if (isset($row[0]->operational_wb_net) && !empty($row[0]->operational_wb_net)) echo $row[0]->operational_wb_net;?>"> <?php if (isset($row[0]->operational_wb_net)) echo $row[0]->operational_wb_net;?></option>
                         <?php endif; ?>
                            <option value="Financial and Private Sector Development Network (FPD)">Financial and Private Sector Development Network (FPD)</option>
                            <option value="Human Development Network (HDN)">Human Development Network (HDN)</option>
                            <option value="Sustainable Development Network (SDN)">Sustainable Development Network (SDN)</option>
                            <option value="Poverty Reduction and Policy Management Network (PREM)">Poverty Reduction and Policy Management Network (PREM)</option>
                        </select>
                    </div>  
                    <div class="field">
                        <label id="operational_wb_sector" for="operational_wb_sector"><?php echo t('operational_wb_sector'); ?></label>
                        <div class="HelpMsg altTitlHelpMsg" style="display:none">
                        <?php echo wordwrap(t('operational_wb_sector_help'),80); ?>
                        </div>
                        <br />
                        <select name="operational_wb_sector">
														<option value="--">--</option>
                          <?php if (isset($row[0]->operational_wb_sector) && !empty($row[0]->operational_wb_sector)): ?>  
													<option selected="selected" value="<?php if (isset($row[0]->operational_wb_sector) && !empty($row[0]->operational_wb_sector)) echo $row[0]->operational_wb_sector;?>"> <?php if (isset($row[0]->operational_wb_sector)) echo $row[0]->operational_wb_sector;?></option>
                         <?php endif; ?>
                            <option value="Agriculture and Rural Development (ARD)">Agriculture and Rural Development (ARD)</option>
                            <option value="Economic Policy (EP)">Economic Policy (EP)</option>
                            <option value="Education (ED)">Education (ED)</option>
                            <option value="Energy and Mining (EMT)">Energy and Mining (EMT)</option>
                            <option value="Environment (ENV)">Environment (ENV)</option>
                            <option value="Financial Management (FM)">Financial Management (FM)</option>
                            <option value="Financial and Private Sector Development (FPD)">Financial and Private Sector Development (FPD)</option>
                            <option value="Gender and Development (GE)">Gender and Development (GE)</option>
                            <option value="Global Information/Communications Technology (GIC)">Global Information/Communications Technology (GIC)</option>
                            <option value="Health, Nutrition and Population (HE)">Health, Nutrition and Population (HE)</option>
                            <option value="Poverty Reduction (PO)">Poverty Reduction (PO)</option>
                            <option value="Procurement (PR)">Procurement (PR)</option>
                            <option value="Project Finance and Guarantees (PFG)">Project Finance and Guarantees (PFG)</option>
                            <option value="Public Sector Governance (PS)">Public Sector Governance (PS)</option>
                            <option value="Resource Management (RM)">Resource Management (RM)</option>
                            <option value="Social Development (SDV)">Social Development (SDV)</option>
                            <option value="Social Protection (SP)">Social Protection (SP)</option>
                            <option value="Transport (TR)">Transport (TR)</option>
                            <option value="Urban Development (UD)">Urban Development (UD)</option>
                            <option value="Water (WAT)">Water (WAT)</option>
                            <option value="Multi-Sector">Multi-Sector</option>
                        </select>
                    </div>   
                    <div class="field">
                        <label id="operational_wb_summary" for="operational_wb_summary"><?php echo t('operational_wb_summary'); ?></label>
                        <div class="HelpMsg serInfoHelpMsg" style="display:none">
                        <?php echo wordwrap(t('operational_wb_summary_help'),80); ?>
                        </div>
                        <textarea  name="operational_wb_summary" cols="30" class="input-flex"><?php echo isset($row[0]->operational_wb_summary)?$row[0]->operational_wb_summary:''; ?></textarea>
                    </div>
                    <div class="field">
                        <label id="operational_wb_objectives" for="operational_wb_objectives"><?php echo t('operational_wb_objectives'); ?></label>
                        <div class="HelpMsg parTitlHelpMsg" style="display:none">
                        <?php echo wordwrap(t('operational_wb_objectives_help'),80); ?>
                        </div>
                        <textarea  name="operational_wb_objectives" cols="30" class="input-flex"><?php echo isset($row[0]->operational_wb_objectives)?$row[0]->operational_wb_objectives:''; ?></textarea>
                    </div>
                </div>
    </div>
    <?php endif;?>

    <?php if($show_additional_fields):?>    
	<div class="dd-study-section <?php echo $field_toggle_class;?>" >
        <div id="Impact-Evaluation"  class="section-header">
            <div class="section-title"><?php echo t('impact-evaluation');?></div>
        </div>   
     
        <div class="section-body" >                
                    <div class="field">
                        <label id="impact_wb_name" for="impact_wb_name"><?php echo t('impact_wb_name'); ?></label>
                        <div class="HelpMsg titlHelpMsg" style="display:none;">
                        <?php echo wordwrap(t('impact_wb_name_help'),80); ?>
                        </div>
                        <input type="text" name="impact_wb_name" class="input-flex" value="<?php echo (isset($row[0]->impact_wb_name)) ? $row[0]->impact_wb_name : ''; ?>"/>
                    </div>
                    <div class="field">
                        <label id="impact_wb_id" for="impact_wb_id"><?php echo t('impact_wb_id'); ?></label>
                        <div class="HelpMsg titlHelpMsg" style="display:none;">
                        <?php echo wordwrap(t('impact_wb_id_help'),80); ?>
                        </div>
                        <input type="text" name="impact_wb_id" class="input-flex" value="<?php echo isset($row[0]->impact_wb_id) ? $row[0]->impact_wb_id : ''; ?>"/>
                    </div>
                    <div class="field">
                        <label id="impact_wb_area" for="impact_wb_area"><?php echo t('impact_wb_area'); ?></label>
                        <div class="HelpMsg altTitlHelpMsg" style="display:none">
                        <?php echo wordwrap(t('impact_wb_area_help'),80); ?>
                        </div>
                        <select name="impact_wb_area">
														<option value="--">--</option>
                          <?php if (isset($row[0]->impact_wb_area) && !empty($row[0]->impact_wb_area)): ?>  
													<option selected="selected" value="<?php if (isset($row[0]->impact_wb_area) && !empty($row[0]->impact_wb_area)) echo $row[0]->impact_wb_area;?>"> <?php if (isset($row[0]->impact_wb_area)) echo $row[0]->impact_wb_area;?></option>
                         <?php endif; ?>
                            <option value="Agriculture">Agriculture</option>
                            <option value="CDD/Social Funds">CDD/Social Funds</option>
                            <option value="Conditional Cash Transfers">Conditional Cash Transfers</option>
                            <option value="Early Childhood Development">Early Childhood Development</option>
                            <option value="Education">Education</option>
                            <option value="Electric Power &amp; Other Energy">Electric Power &amp; Other Energy</option>
                            <option value="Employment">Employment</option>
                            <option value="Environment">Environment</option>
                            <option value="Governance/Accountability">Governance/Accountability</option>
                            <option value="HIV/AIDS">HIV/AIDS</option>
                            <option value="Information and Communication Technology">Information and Communication Technology</option>
                            <option value="Infrastructure">Infrastructure</option>
                            <option value="Malaria">Malaria</option>
                            <option value="Microfinance">Microfinance</option>
                            <option value="Migration">Migration</option>
                            <option value="Nutrition">Nutrition</option>
                            <option value="Other Health">Other Health</option>
                            <option value="Private Sector Development">Private Sector Development</option>
                            <option value="Reproductive Health">Reproductive Health</option>
                            <option value="Social Protection">Social Protection</option>
                            <option value="Transportation, Roads, Infrastructure">Transportation, Roads, Infrastructure</option>
                            <option value="Urban Upgrading">Urban Upgrading</option>
                            <option value="Water Supply &amp; Sanitation">Water Supply &amp; Sanitation</option>
                            <option value="Youth Development">Youth Development</option>
                        </select>
                    </div>  
                    <div class="field">
                        <label id="impact_wb_lead" for="impact_wb_lead"><?php echo t('impact_wb_lead'); ?></label>
                        <div class="HelpMsg altTitlHelpMsg" style="display:none">
                        <?php echo wordwrap(t('impact_wb_lead_help'),80); ?>
                        </div>
                        <?php echo $impact_wb_lead; ?>
                    </div>   
                    <br /><br />
                    <div class="field">
                        <label id="impact_wb_members" for="impact_wb_members"><?php echo t('impact_wb_members'); ?></label>
                        <div class="HelpMsg serInfoHelpMsg" style="display:none">
                        <?php echo wordwrap(t('impact_wb_members_help'),80); ?>
                        </div>
                        <?php echo $impact_wb_members; ?>
                    </div>
                    <br /> <br />
                    <div class="field">
                        <label id="impact_wb_description" for="impact_wb_description"><?php echo t('impact_wb_description'); ?></label>
                        <div class="HelpMsg parTitlHelpMsg" style="display:none">
                        <?php echo wordwrap(t('impact_wb_description_help'),80); ?>
                        </div>
                        <textarea  name="impact_wb_description" cols="30" class="input-flex"><?php echo isset($row[0]->impact_wb_description)?$row[0]->impact_wb_description:''; ?></textarea>
                    </div>
        </div>
    </div>
    <?php endif;?>

	<div class="dd-study-section <?php echo $field_toggle_class;?>">
    <div id="Contacts"  class="section-header">
        <div class="section-title"><?php echo t('contacts');?></div>
     </div>
     
    <div class="section-body">                
                    <div class="clearfix field">
                        <label id="contacts_contacts" for="contacts_contacts"><?php echo t('contact_persons'); ?></label>
                        <div class="HelpMsg distStmt_contactHelpMsg"><?php echo wordwrap(t('distStmt_contact'),80); ?></div>
                        <?php echo $contacts; ?>
                    </div>
                </div>
                
      
</div>
</div>

<div class="save-container">
	 <input type="submit" name="submit" value="Save" class="submit-button"/>
    <a href="<?php echo site_url('datadeposit/projects'); ?>">Cancel</a>
</div>    

<?php echo form_close(); ?>
</div>

<script type="text/javascript">

//required and recommended fields
window.dd={
		'recommended_fields': <?php echo json_encode($merged['recommended_fields']);?>,
		'mandatory_fields': <?php echo json_encode($merged['mandatory_fields']);?>
};

//highlight mandatory fields
$.each(dd.mandatory_fields, function(key,value){
		$("#"+value).closest("label").append(' <span class="dd_required"><?php echo t('Required');?></span>');
        $("#"+value).closest("label").prepend("* ");
});

//expand all
$(document.body).on("click",".dd-actions-container .expand-all", function (e){
	$(".dd-edit-study-description .field-collapsed").toggleClass("field-collapsed field-expanded");
	e.preventDefault();
});

//collapse all
$(document.body).on("click",".dd-actions-container .collapse-all", function (e){
	$(".dd-edit-study-description .field-expanded").toggleClass("field-expanded field-collapsed");
	e.preventDefault();
});


//expand collapsed fields
$(document.body).on("click",".dd-edit-study-description .field-collapsed", function (e){
	$(this).toggleClass("field-expanded field-collapsed");
	e.preventDefault();
});

//collapse expanded fields
$(document.body).on("click",".dd-edit-study-description .field-expanded", function (e){
	$(this).toggleClass("field-collapsed field-expanded");
	e.preventDefault();
});

//stop event propogation to parent field-expanded/collapsed
$(document.body).on("click",".dd-edit-study-description .section-body, .dd-edit-study-description .section-icons", function (e){
	e.stopPropagation();
});

//event handlers for help, required, all, recommeded, and save icons

//toggle help display
$(document.body).on("click",".dd-edit-study-description .show-help, .dd-actions-container .show-help", function (e){
	$(".dd-edit-study-description .HelpMsg").toggle();
});

//show all fields
$(document.body).on("click",".dd-edit-study-description .show-all-fields, .dd-actions-container .show-all-fields", function (e){
	$(".dd-study-section, .dd-study-section .field").show();
});

//show recomended or mandatory fields
// mandatory are always shown, to show/hide recommended pass 1 or 0 as param
function show_fields(show_recommended)
{
	//hide all fields
	$(".dd-study-section .field").hide().removeClass("recommended-field");
		
	//show mandatory fields
	$.each(dd.mandatory_fields, function(key,value){
		$("#"+value).closest(".field").show().addClass("recommended-field");
	});

	if (show_recommended==1)
	{
		//show recommended fields
		$.each(dd.recommended_fields, function(key,value){
			$("#"+value).closest(".field").show().addClass("recommended-field");
		});
	}
	
	//hide sections which don't have any required/mandatory fields	
	$(".dd-study-section").each(function(e){		
		if($(this).find(".recommended-field").length==0){
			$(this).hide();
		}
		else{
			$(this).show();
		}
	});

}

//show only recommended fields
$(document.body).on("click",".dd-edit-study-description .show-recommended, .dd-actions-container .show-recommended", function (e){
	show_fields(1);
});

//show mandatory fields only
$(document.body).on("click",".dd-edit-study-description .show-mandatory, .dd-actions-container .show-mandatory", function (e){
	show_fields(0);
});


//import metadata open button
$(document.body).on("click",".dd-actions-container .import-data", function (e){
    $(".project-import-dialog").toggle();
});

//close button
$(document.body).on("click",".project-import-dialog .close, .project-import-dialog .btn-cancel", function (e){
    $(".project-import-dialog").toggle();
});


//save changes
$(document.body).on("click",".dd-edit-study-description .save", function (e){
	$('.changedInput').removeClass('changedInput');	
	$('input').each(function() {
		if ($(this).val() == ' ') {
			$(this).val('');
		}
	});
	
	var d = $('form#form, input#submit').serialize();
	
	$.post("<?php echo current_url(); ?>", d, function(data) {
		if (data) {
			alert("<?php echo t('study_updated'); ?>");
		}
	});
	return false;
});


//back to top
$(window).scroll(function() {
	if($(this).scrollTop() != 0) {
		$('#toTop').fadeIn();	
	} else {
		$('#toTop').fadeOut();
	}
});

$('#toTop').click(function() {
$('body,html').animate({scrollTop:0},'fast');
});

<?php if($show_help_text==true):?>
jQuery(document).ready(function(){
    $(".dd-edit-study-description .HelpMsg").show();
});
<?php endif;?>

</script>