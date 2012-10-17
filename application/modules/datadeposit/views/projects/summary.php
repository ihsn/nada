<style type="text/css">
/*
.contents{
		width:100%;
		min-height: 500px;
		border:1px solid gainsboro;
	}
.contents .field{
		margin:15px;	
	}
.contents label{
		backgroundx:#CCC;
		display:block;
		margin:5px 0px;
		padding:3px;
		font-weight:bold;
	}
	
	legend{margin-left:12px; font-weight:bold;}
	fieldset{border:1px solid #CCC; margin-bottom:8px;}
	
	.field-expanded,.always-visible{background-colorx:#F8F8F8;border:1px solid gainsboro;margin-top:5px;margin-bottom:10px;margin-right:8px;margin-left:7px;}
	.always-visible{padding:10px;}
	.field-expanded .field, .always-visible .field {padding:5px;}
	.field-expanded legend, .field-collapsed legend, .always-visible legend{background:white;padding-left:5px;padding-right:5px;font-weight:bold; cursor:pointer}
	.field-collapsed{background:none; border:0px;border-top:1px solid gainsboro;margin-top:5px;margin-bottom:5px;}
	.field-collapsed legend {background-image:url(images/next.gif); background-position:left top; padding-left:20px;background-repeat:no-repeat;}
	.field-collapsed .field{display:none;}
	.field-expanded .field label, .always-visible label{font-weight:normal;}
	

table {
	border-width: 1px;
	border-spacing: 2px;
	border-color: gray;
	border-collapse: separate;
	background-color: white;
	width:95%;
}
table th {
	border-width: 1px;
	padding: 1px;
	border-style: inset;
	border-color: gray;
	background-color: #ccc;
	-moz-border-radius: 0px 0px 0px 0px;
	text-align:left;
	font-weight:bold;
	width:150px;
}
table td {
	border-width: 1px;
	padding: 1px;
	border-style: inset;
	border-color: gray;
	background-color: white;
	-moz-border-radius: 0px 0px 0px 0px;
}
*/
.td-label{font-weight:normal;width:150px;color:#333333}
.contents legend{font-size:14px;font-weight:bold;padding-top:20px;border-bottom:1px solid gainsboro;margin-bottom:10px;display:block;width:100%;}
.project-summary {padding:5px;margin-top:10px;}
.contents table {width:100%;}
h2{font-size:16px;font-weight:bold;margin-top:20px;}
fieldset{border:0px; margin-bottom:8px;}
</style>
    <?php //echo $toolbar; ?>
    <h1 class="page-title"><?php echo t('summary')?></h1>
    
    <?php $message=$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
        
<div class="contents">
    <fieldset class="field-expandedx">
    <legend><?php echo t('study_desc');?></legend>
  
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('project_info');?></legend> 
 
    <table>
        <tr><th class="td-label">Description</th><td><?php echo $project[0]->description; ?></td></tr>
        <tr><th class="td-label">Collaboration</th><td><?php echo $project[0]->collaborators; ?></td></tr>
        <tr><th class="td-label">Project ID</th><td><?php echo $project[0]->id; ?></td></tr>
        <tr><th class="td-label">Created By</th><td><?php echo $project[0]->created_by; ?></td></tr>
        <tr><th class="td-label">Date Created On</th><td><?php echo $project[0]->created_on ?></td></tr>
        <tr><th class="td-label">Status</th><td><?php echo $project[0]->status; ?></td></tr>
	</table>
        </fieldset>
     </div>
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('identification');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Title</th><td><?php echo $row[0]->ident_title; ?></td></tr>
        <tr><th class="td-label">Abbreviation</th><td><?php echo ($row[0]->ident_abbr != '')?$row[0]->ident_abbr:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Study Type</th><td><?php echo ($row[0]->ident_study_type != '')?$row[0]->ident_study_type:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Series Information</th><td><?php echo ($row[0]->ident_ser_info != '')?$row[0]->ident_ser_info:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Translated Title</th><td><?php echo ($row[0]->ident_trans_title != '')?$row[0]->ident_trans_title:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">ID Number</th><td><?php echo ($row[0]->ident_id != '')?$row[0]->ident_id:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('versions');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Description</th><td><?php echo ($row[0]->ver_desc != '')?$row[0]->ver_desc:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Production Date</th><td><?php echo $row[0]->ver_prod_date; ?></td></tr>
        <tr><th class="td-label">Notes</th><td><?php echo ($row[0]->ver_notes != '')?$row[0]->ver_notes:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('overview');?></legend>  
        <div class="field">
        <table>
        <tr><th class="td-label">Abstract</th><td><?php echo ($row[0]->overview_abstract != '')?$row[0]->overview_abstract:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Kind of Date</th><td><?php echo ($row[0]->overview_kind_of_data != '')?$row[0]->overview_kind_of_data:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Unit of Analysis</th><td><?php echo ($row[0]->overview_analysis != '')?$row[0]->overview_analysis:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Impact Evaluation Methods</th><td><?php echo $methods ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('scope');?></legend>   
        <div class="field">
        <table>
        <tr><th class="td-label">Description of Scope</th><td><?php echo ($row[0]->scope_definition != '')?$row[0]->scope_definition:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Topics Classifications</th><td><?php echo $topic_class ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('coverage');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Country</th><td><?php echo $country ?></td></tr>
        <tr><th class="td-label">Geographic Coverage</th><td><?php echo ($row[0]->coverage_geo != '')?$row[0]->coverage_geo:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Universe</th><td><?php echo ($row[0]->coverage_universe != '')?$row[0]->coverage_universe:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('producers_and_sponsors');?></legend>  
        <div class="field">
        <table>
        <tr><th class="td-label">Primary Investigator</th><td><?php echo $prim_investigator; ?></td></tr>
        <tr><th class="td-label">Other Producers</th><td><?php echo $other_producers; ?></td></tr>
        <tr><th class="td-label">Funding</th><td><?php echo $funding ?></td></tr>
        <tr><th class="td-label">Other Acknowledgements</th><td><?php echo $acknowledgements; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('sampling');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Sampling Procedure</th><td><?php echo ($row[0]->sampling_procedure != '')?$row[0]->sampling_procedure:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Deviations from Sample Design</th><td><?php echo ($row[0]->sampling_dev != '')?$row[0]->sampling_dev:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Response Rates</th><td><?php echo ($row[0]->sampling_rates != '')?$row[0]->sampling_rates:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Weighting</th><td><?php echo ($row[0]->sampling_weight != '')?$row[0]->sampling_weight:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_collection');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label">Dates of Collection</th><td><?php echo $dates_datacollection; ?></td></tr>
        <tr><th class="td-label">Time Periods</th><td><?php echo $time_periods; ?></td></tr>
        <tr><th class="td-label">Mode of Data Collection</th><td><?php echo ($row[0]->coll_mode != '')?$row[0]->coll_mode:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Notes of Data Collection</th><td><?php echo ($row[0]->coll_notes != '')?$row[0]->coll_notes:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Questionnaires</th><td><?php echo $row[0]->coll_questionnaire; ?></td></tr>
        <tr><th class="td-label">Data Collectors</th><td><?php echo $data_collectors; ?></td></tr>
        <tr><th class="td-label">Supervision</th><td><?php echo ($row[0]->coll_supervision != '')?$row[0]->coll_supervision:'&nbsp;'; ?></td></tr>
   		</table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_processing');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label">Data Editing</th><td><?php echo ($row[0]->process_editing != '')?$row[0]->process_editing:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Other Processing</th><td><?php echo ($row[0]->process_editing != '')?$row[0]->process_editing:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_appraisal');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label">Estimates of Sampling Error</th><td><?php echo ($row[0]->appraisal_error != '')?$row[0]->appraisal_error:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Other Forms of Data Appraisal</th><td><?php echo ($row[0]->appraisal_other != '')?$row[0]->appraisal_other:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_access');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Access Authority</th><td><?php echo  $access_authority; ?></td></tr>
        <tr><th class="td-label">Confidentiality</th><td><?php echo ($row[0]->access_confidentiality != '')?$row[0]->access_confidentiality:'&nbsp;' ?></td></tr>
        <tr><th class="td-label">Access Conditions</th><td><?php echo ($row[0]->access_conditions != '')?$row[0]->access_conditions:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Citations Requirement</th><td><?php echo ($row[0]->access_cite_require != '')?$row[0]->access_cite_require:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('disclaimer_and_copyright');?></legend>   
        <div class="field">
        <table>
        <tr><th class="td-label">Disclaimer</th><td><?php echo ($row[0]->disclaimer_disclaimer != '')?$row[0]->disclaimer_disclaimer:'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Copyright</th><td><?php echo ($row[0]->disclaimer_copyright != '')?$row[0]->disclaimer_copyright:'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('contacts');?></legend>   
        <div class="field">
        <table>
        <tr><th class="td-label">Contact Persons</th><td><?php echo $contacts; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    </fieldset>
    <fieldset class="field-expandedx">
    <legend><?php echo t('data_files');?></legend>
      
	<div class="field">
   <table class="grid-table" style="margin-top:5px;">
<tr valign="top" align="left" class="header">
    <th style="width:80px"><?php echo t('type');?></th>
    <th style="width:200px"><?php echo t('name');?></th>
    <th style="width:500px"><?php echo t('description');?></th>	
    </tr>
<?php $prefix = ""; ?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
        <tr valign="top">
            <td><?php echo (isset($file['dctype'])) ? $file['dctype'] : 'N/A';?></td>
            <td><?php echo $file['filename']; ?></td>
			<td><?php echo isset($file['description']) ? $file["description"] : 'N/A';?></td>            
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
    </div>
    
    
    <fieldset class="field-expandedx">
    <legend><?php echo t('citations');?></legend> 
	<div class="field">
    <table>
	<tr><th>Total citations</th></tr>
    <tr><td><?php echo $row[0]->citations; ?></td></tr>
    </table>
    </div>
    
    </fieldset>
    
</div>
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