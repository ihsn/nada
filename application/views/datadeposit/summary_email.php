<?php
	$fieldset_style='style="border:none;border-top:1px solid gainsboro;font-weight:bold;margin-bottom:15px;"';
	$th_style='style="font-weight:normal;text-align:left;"';
    $table_style='style="font-weight:normal;text-align:left;"';
    $show_additional_fields=$this->config->item('additional_fields','datadeposit');
?>
<div style="font-family:Arial, Helvetica, sans-serif;margin-top:50px;background-color:#F5F5F5;padding:10px;">

    <div class="field"> 

        <fieldset class="field-expandedx" <?php echo $fieldset_style;?> >
        <legend><?php echo t('project_info');?></legend> 
 
    <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Title</th><td align="left"><?php echo $project[0]->title; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Description</th><td align="left"><?php echo $project[0]->description; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Collaboration</th><td align="left"><?php echo $project[0]->collaborators; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Project ID</th><td align="left"><?php echo $project[0]->id; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Created By</th><td align="left"><?php echo $project[0]->created_by; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Date Created On</th><td align="left"><?php echo date('Y-m-d', $project[0]->created_on) ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Status</th><td align="left"><?php echo $project[0]->status; ?></td></tr>
	</table>
        </fieldset>
     </div>
     <?php if (!isset($row[0]->id)): ?>
     <p><?php echo t('no_study_found'); ?></p>
     <?php else: ?>
     <fieldset <?php echo $fieldset_style;?>>
    <legend><?php echo t('study_desc');?></legend>
  	</fieldset>
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('identification');?></legend> 
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Title</th><td align="left"><?php echo $row[0]->ident_title; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Subtitle</th><td align="left"><?php echo $row[0]->ident_subtitle; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Abbreviation</th><td align="left"><?php echo ($row[0]->ident_abbr != '')?nl2br($row[0]->ident_abbr):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Study Type</th><td align="left"><?php echo ($row[0]->ident_study_type != '')?nl2br($row[0]->ident_study_type):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Series Information</th><td align="left"><?php echo ($row[0]->ident_ser_info != '')?nl2br($row[0]->ident_ser_info):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Translated Title</th><td align="left"><?php echo ($row[0]->ident_trans_title != '')?nl2br($row[0]->ident_trans_title):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">ID Number</th><td align="left"><?php echo ($row[0]->ident_id != '')?nl2br($row[0]->ident_id):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('versions');?></legend> 
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Description</th><td align="left"><?php echo ($row[0]->ver_desc != '')?nl2br($row[0]->ver_desc):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Production Date</th><td align="left"> <?php 
					$obj = $row[0]->ver_prod_date;
					$obj = $row[0]->ver_prod_date;
					if($obj){
					echo date('Y-m-d', $obj);
					}
					else
					{
						echo 'N/A';
					}
		?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Notes</th><td align="left"><?php echo ($row[0]->ver_notes != '')?nl2br($row[0]->ver_notes):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('overview');?></legend>  
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Abstract</th><td align="left"><?php echo ($row[0]->overview_abstract != '')?nl2br($row[0]->overview_abstract):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Kind of Data</th><td align="left"><?php echo ($row[0]->overview_kind_of_data != '')?nl2br($row[0]->overview_kind_of_data):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Unit of Analysis</th><td align="left"><?php echo ($row[0]->overview_analysis != '')?nl2br($row[0]->overview_analysis):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Impact Evaluation Methods</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $methods); ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('scope');?></legend>   
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Description of Scope</th><td align="left"><?php echo ($row[0]->scope_definition != '')?nl2br($row[0]->scope_definition):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Topics Classifications</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $topic_class); ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('coverage');?></legend> 
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Country</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $country); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Geographic Coverage</th><td align="left"><?php echo ($row[0]->coverage_geo != '')?nl2br($row[0]->coverage_geo):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Universe</th><td align="left"><?php echo ($row[0]->coverage_universe != '')?nl2br($row[0]->coverage_universe):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('producers_and_sponsors');?></legend>  
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Primary Investigator</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $prim_investigator); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Other Producers</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $other_producers); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Funding</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $funding); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Other Acknowledgements</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $acknowledgements); ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('sampling');?></legend> 
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Sampling Procedure</th><td align="left"><?php echo ($row[0]->sampling_procedure != '')?nl2br($row[0]->sampling_procedure):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Deviations from Sample Design</th><td align="left"><?php echo ($row[0]->sampling_dev != '')?nl2br($row[0]->sampling_dev):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Response Rates</th><td align="left"><?php echo ($row[0]->sampling_rates != '')?nl2br($row[0]->sampling_rates):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Weighting</th><td align="left"><?php echo ($row[0]->sampling_weight != '')?nl2br($row[0]->sampling_weight):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('data_collection');?></legend>
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Dates of Collection</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $dates_datacollection); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Time Periods</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $time_periods); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Mode of Data Collection</th><td align="left"><?php echo ($row[0]->coll_mode != '')?nl2br($row[0]->coll_mode):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Notes of Data Collection</th><td align="left"><?php echo ($row[0]->coll_notes != '')?nl2br($row[0]->coll_notes):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Questionnaires</th><td align="left"><?php echo $row[0]->coll_questionnaire; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Data Collectors</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $data_collectors); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Supervision</th><td align="left"><?php echo ($row[0]->coll_supervision != '')?nl2br($row[0]->coll_supervision):'&nbsp;'; ?></td></tr>
   		</table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('data_processing');?></legend>
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Data Editing</th><td align="left"><?php echo ($row[0]->process_editing != '')?nl2br($row[0]->process_editing):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Other Processing</th><td align="left"><?php echo ($row[0]->process_editing != '')?nl2br($row[0]->process_editing):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('data_appraisal');?></legend>
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Estimates of Sampling Error</th><td align="left"><?php echo ($row[0]->appraisal_error != '')?nl2br($row[0]->appraisal_error):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Other Forms of Data Appraisal</th><td align="left"><?php echo ($row[0]->appraisal_other != '')?nl2br($row[0]->appraisal_other):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('data_access');?></legend> 
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Access Authority</th><td align="left"><?php echo  str_replace('<th ', '<th  '.$th_style, $access_authority); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Confidentiality</th><td align="left"><?php echo ($row[0]->access_confidentiality != '')?nl2br($row[0]->access_confidentiality):'&nbsp;' ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Access Conditions</th><td align="left"><?php echo ($row[0]->access_conditions != '')?nl2br($row[0]->access_conditions):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Citation Requirement</th><td align="left"><?php echo ($row[0]->access_cite_require != '')?nl2br($row[0]->access_cite_require):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('disclaimer_and_copyright');?></legend>   
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Disclaimer</th><td align="left"><?php echo ($row[0]->disclaimer_disclaimer != '')?nl2br($row[0]->disclaimer_disclaimer):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Copyright</th><td align="left"><?php echo ($row[0]->disclaimer_copyright != '')?nl2br($row[0]->disclaimer_copyright):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <?php if($show_additional_fields):?>
    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('operational_information');?></legend>
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('operational_wb_name');?></th><td align="left"><?php echo ($row[0]->operational_wb_name != '')?nl2br($row[0]->operational_wb_name):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('operational_wb_id');?></th><td align="left"><?php echo ($row[0]->operational_wb_id != '')?nl2br($row[0]->operational_wb_id):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('operational_wb_net');?></th><td align="left"><?php echo ($row[0]->operational_wb_net != '')?nl2br($row[0]->operational_wb_net):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('operational_wb_sector');?></th><td align="left"><?php echo ($row[0]->operational_wb_sector != '')?nl2br($row[0]->operational_wb_sector):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('operational_wb_summary');?></th><td align="left"><?php echo ($row[0]->operational_wb_summary != '')?nl2br($row[0]->operational_wb_summary):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('operational_wb_objectives');?></th><td align="left"><?php echo ($row[0]->operational_wb_objectives != '')?nl2br($row[0]->operational_wb_objectives):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('impact-evaluation');?></legend>
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('impact_wb_name');?></th><td align="left"><?php echo ($row[0]->impact_wb_name != '')?nl2br($row[0]->impact_wb_name):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('impact_wb_id');?></th><td align="left"><?php echo ($row[0]->impact_wb_id != '')?nl2br($row[0]->impact_wb_id):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('impact_wb_area');?></th><td align="left"><?php echo ($row[0]->impact_wb_area != '')?nl2br($row[0]->impact_wb_area):'&nbsp;'; ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('impact_wb_lead');?></th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $impact_wb_lead); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('impact_wb_members');?></th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $impact_wb_members); ?></td></tr>
        <tr><th <?php echo $th_style;?> align="left" class="td-label"><?php echo t('impact_wb_description');?></th><td align="left"><?php echo ($row[0]->impact_wb_description != '')?nl2br($row[0]->impact_wb_description):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    <?php endif;?>

    <div class="field"> 
        <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
        <legend><?php echo t('contacts');?></legend>   
        <div class="field">
        <table <?php echo $table_style;?>>
        <tr><th <?php echo $th_style;?> align="left" class="td-label">Contact Persons</th><td align="left"><?php echo str_replace('<th ', '<th  '.$th_style, $contacts); ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
<?php if (!empty($files)): ?>    
    </fieldset>
    <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
    <legend><?php echo t('data_files');?></legend>
<table style="margin-top:20px;margin-bottom:10px" class="grid-table" cellspacing="0" cellpadding="0" <?php echo $table_style;?>>
<tr valign="top" align="left" style="height:10px" class="header">
    <th <?php echo $th_style;?> align="left" style="width:200px"><?php echo t('name');?></th>
    <th <?php echo $th_style;?> align="left" style="width:500px"><?php echo t('description');?></th>	
    <th <?php echo $th_style;?> align="left" style="width:80px"><?php echo t('type');?></th>
    <?php if ($this->uri->segment(1) == 'admin'): ?>
    <th <?php echo $th_style;?> align="left" style="width:100px"><?php echo t('download'); ?></th>
    <?php endif; ?>
    <!--<th <?php echo $th_style;?> align="left" >Exists</th>-->
</tr>
<?php $prefix = ""; ?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
        <tr valign="top">
            <td align="left"><?php echo $file['filename']; ?></td>
			<td align="left"><?php echo isset($file['description']) ? $file["description"] : 'N/A';?></td>            
            <td align="left"><?php echo (isset($file['dctype'])) ? preg_replace('#\[.*?\]#', '', $file['dctype']) : 'N/A';?></td>
		    <?php if ($this->uri->segment(1) == 'admin'): ?>
            <td align="left"><?php echo "<a href=", site_url('datadeposit/download'), '/', $file['id'], ">Download</a>"; ?> </td> 
        	<?php endif; ?>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
<?php endif; ?>
    </fieldset>
    <?php if ($this->active_citations_count): ?>
    <fieldset class="field-expandedx" <?php echo $fieldset_style;?>>
    <legend><?php echo t('citations');?></legend> 
	<div class="field">
 <table style="margin-top:20px;margin-bottom:10px" class="grid-table" width="100%" cellspacing="0" cellpadding="0" <?php echo $table_style;?>>
    	<tr style="height:10px"  class="header">
        	<th <?php echo $th_style;?> align="left" >&nbsp;</th>
            <th <?php echo $th_style;?> align="left" ><?php echo t('citation_type'); ?></th>
            <th <?php echo $th_style;?> align="left" ><?php echo t('title'); ?></th>
			<th <?php echo $th_style;?> align="left" ><?php echo t('date'); ?></th>
			<th <?php echo $th_style;?> align="left" ><?php echo t('created'); ?></th>            
            <th <?php echo $th_style;?> align="left" ><?php echo t('modified'); ?></th>
            <th <?php echo $th_style;?> align="left" >&nbsp;</th>
            <th <?php echo $th_style;?> align="left" >&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($this->active_citations as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
        	<td align="left">&nbsp;</td>
            <td align="left"><?php echo t($row->ctype); ?></td>
            <td align="left"><?php echo $row->title;?></td>
            <td nowrap="nowrap"><?php echo $row->pub_year; ?>&nbsp;</td>
            <td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->created); ?></td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>            
			
            <td nowrap="nowrap">&nbsp;
            


            </td>
            <td nowrap="nowrap">&nbsp;
			
            </td>
            <td align="left">&nbsp;    
            

            </td>
            <td align="left">&nbsp;    
            
            </td>
        </tr>
    <?php endforeach;?>
    </table>
    </div>

    
    </fieldset>
<?php endif; ?>
    <?php endif; ?>
</div>

