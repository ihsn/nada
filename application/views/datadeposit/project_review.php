<?php if (!isset($row[0]->id) ||  !isset($project[0])): ?>
	<div class="error">
	<?php echo ('Project information could not be loaded.'); return false;?>
    </div>
<?php endif; ?>


<?php
	$show_additional_fields=$this->config->item('additional_fields','datadeposit');
?>



<div class="contents page-review-submit">    
    <?php
		$project_fields=array(
			'Last updated'		=> date("F d, Y",$project[0]->last_modified),
			//'Project created by'	=> implode(", ", $project[0]->owner),
			//'Collaborator(s)'	=> implode(", ",$project[0]->collaborators),
			'Publish to'		=> $project[0]->to_catalog,
			'Embargoed'			=> ($project[0]->is_embargoed==="1") ? "Yes": "No",
			'Embargoed notes'	=> nl2br($project[0]->embargoed),
			'Disclosure risk'	=> nl2br($project[0]->disclosure_risk),
			'Key variables'		=> nl2br($project[0]->key_variables),
			'Sensitive variables'	=> nl2br($project[0]->sensitive_variables),
			'Access policy'		=> nl2br($project[0]->access_policy),
			'Notes'				=> nl2br($project[0]->library_notes),
			'Status'			=> strtoupper($project[0]->status)
		);
	?>
    <div class="field"> 
            <h2 class="title"><?php echo t('project_info');?></h2> 
            <table class="tbl-border table table-bordered">
                <tr><td class="td-label">Title</td><td><?php echo $project[0]->title; ?></td></tr>
                <tr><td class="td-label">Description</td><td><?php echo $project[0]->description; ?></td></tr>
                <?php if (isset($project[0]->collaborators) && count($project[0]->collaborators)>0):?>
                <tr><td class="td-label">Collaboration</td><td><?php echo implode(", ",$project[0]->collaborators); ?></td></tr>
                <?php endif;?>
                <tr><td class="td-label">Project ID</td><td><?php echo $project[0]->id; ?></td></tr>
                <tr><td class="td-label">Created By</td><td><?php echo $project[0]->created_by; ?></td></tr>
                <tr><td class="td-label">Created On</td><td><?php echo date('F d, Y', $project[0]->created_on) ?></td></tr>
                <!--<tr><td class="td-label">Status</td><td><?php echo strtoupper($project[0]->status); ?></td></tr>-->
             	
                <?php foreach($project_fields as $field_title=>$field_value):?>
                	<?php if ($field_value):?>
                    <tr>
                    	<td class="td-label"><?php echo $field_title;?></td>
                        <td><?php echo $field_value;?></td>
                    </tr>
                    <?php endif;?>
                <?php endforeach;?>   
                
            </table>
     </div>
     
	<div class="field"> 
        <?php
			$fields=array();
			$fields['section_identification']=array(
					'Title'					=>form_prep(nl2br($row[0]->ident_title)),
					'Subtitle'				=>$row[0]->ident_subtitle,
					'Abbreviation'			=>$row[0]->ident_abbr,
					'Study Type'			=>$row[0]->ident_study_type,
					'Series Information'	=>$row[0]->ident_ser_info,
					'Translated Title'		=>$row[0]->ident_trans_title,
					'ID Number'				=>$row[0]->ident_id,
			);
			
			$fields['section_version']=array(
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
					'Other Processing'	=>$row[0]->process_other,
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


			if($show_additional_fields){
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
			}

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
        <h2 class="title"><?php echo t('study_desc');?></h2>
        <?php foreach($fields as $section_name=>$section):?>
        <?php if ($section['is_empty']){continue;}?>
        <div class="field"> 
        <fieldset>
            <legend><?php echo t($section_name);?></legend> 
            <div class="field">
                <table class="table table-bordered">
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
</div>
<!--end-study-description-->        
        
<?php if (!empty($files)): ?>    
    <div class="field">
    <h2 class="title"><?php echo t('data_files');?></h2>
	<table class="grid-table table table-bordered" >
        <tr valign="top" align="left" class="header">
            <th><?php echo t('name');?></th>
            <!--<th style="width:500px"><?php echo t('description');?></th>	-->
            <th><?php echo t('type');?></th>
            <?php if ($this->uri->segment(1) == 'admin'): ?>
            <!--<th><?php echo t('download'); ?></th>-->
            <?php endif; ?>
        </tr>
		<?php $prefix = ""; ?>
		<?php foreach( $files as $file): ?>
        <tr valign="top">
            <td><?php echo $file['filename']; ?></td>
			<!--<td><?php //echo isset($file['description']) ? $file["description"] : 'N/A';?></td>-->
            <td><?php echo (isset($file['dctype'])) ? preg_replace('#\[.*?\]#', '', $file['dctype']) : 'N/A';?></td>
		    <?php /* //if ($this->uri->segment(1) == 'admin'): ?>
            <td><?php echo "<a href=", site_url('datadeposit/download'), '/', $file['id'], ">Download</a>"; ?> </td> 
        	<?php */ //endif; ?>
        </tr>
    	<?php endforeach;?>        
	</table>
    </div>
<?php endif; ?>

<?php if (isset($citations) && is_array($citations) && count($citations) >0): ?>

	<div class="field">
    <h2 class="title"><?php echo t('citations');?></h2> 
	<div>
 	<table class="grid-table table table-bordered" cellspacing="0" cellpadding="0">
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
    </div>
<?php endif; ?>


</div>