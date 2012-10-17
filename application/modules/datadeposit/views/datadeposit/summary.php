<?php 
if (isset($_GET['print']) && $_GET['print'] == 'yes'): ?>
<script type="text/javascript" src="<?php echo site_url(); ?>/../javascript/jquery.js"></script>
<?php endif; ?>
<style type="text/css">
.field { margin: 1px 0 10px 0!important; padding:0!important }
fieldset {margin-bottom:-10px!important}
legend{margin-bottom:0!important;margin-top:0!important}
#toTop {
	width:100px;
        border:1px solid #ccc;
        background:#f7f7f7;
        text-align:center;
        padding:5px;
        position:fixed; 
				z-index: 500;
        bottom:20px; 
        right:10px;
        cursor:pointer;
		font-weight:bold;
        display:none;
        color:#333;
        font-family:verdana;
        font-size:11px;
}
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
h2{font-size:16px;font-weight:bold;margin-top:20px;}
fieldset{border:0px}
.red{ color: #ff0000; }
</style>
<script type="text/javascript"> 
	$(function() {
	/* Required fields */
	var toHighlight = [<?php /* next($fields); $fields = current($fields); foreach ($fields as $field) echo "'$field', "; */?>];
		$.each($('.td-label'), function() {
			var d = $.trim($(this).next().html());
			if (d == "" || d == null || d == "&nbsp;" || d == "--" || d == '0000-00-00') {
				$(this).parent().css('display', 'none').remove();
			}
			var _self = $(this);
		//	$.each(toHighlight, function(index, value) {
		//		if(_self.html() == value) {
		//		_self.addClass('red');
		//	}
		//	});
		});
        $('fieldset .field table tbody').each(function() {
            if ($(this).children().length == 0) {
                $(this).parent().parent().parent().remove();
            }
        });
	/* Back to Top */
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
	});
</script>
    <?php //echo $toolbar; ?>
    <h1 class="page-title"><?php echo t('summary')?></h1>
    <?php if ($this->uri->segment(1) == 'admin'): ?>
      <div style="font-size:14px;margin-top:-35px!important;float:right;">
     	Export To <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=ddi">DDI</a>
        |
        <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=rdf">RDF</a>
        |
        <a href="<?php echo current_url();?>?print=yes"><img src="<?php echo site_url(); ?>/../images/print.gif" alt="print" /></a>
     </div>
    <?php endif; ?>
    <?php $message=$this->session->flashdata('message');?>
	<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?> 
    <?php if ($project[0]->status == 'draft' && $this->uri->segment(1) != 'admin'): ?>
	<?php foreach($fields['merged'] as $key => $value):
		if (!isset($row[0]->$key) || empty($row[0]->$key) || $row[0]->$key == '[]' || $row[0]->$key == ' ' || $row[0]->$key == '--' || $row[0]->$key == '0000-00-00'): ?>
        <?php echo '<span style="color:#ff0000;margin:5px;font-size:10pt;">', $value, ' is a recommended field that is not filled.</span><br />', PHP_EOL; ?>
        <?php endif; ?>
		<?php endforeach; ?> 
    <?php endif; ?>
       <div id="toTop">^ Back to Top</div>
<div class="contents">

    <div class="field"> 

        <fieldset style="" class="field-expandedx">
        <legend><?php echo t('project_info');?></legend> 
 
    <table>
        <tr><th class="td-label">Title</th><td><?php echo $project[0]->title; ?></td></tr>
        <tr><th class="td-label">Description</th><td><?php echo $project[0]->description; ?></td></tr>
        <tr><th class="td-label">Collaboration</th><td><?php echo $project[0]->collaborators; ?></td></tr>
        <tr><th class="td-label">Project ID</th><td><?php echo $project[0]->id; ?></td></tr>
        <tr><th class="td-label">Created By</th><td><?php echo $project[0]->created_by; ?></td></tr>
        <tr><th class="td-label">Date Created On</th><td><?php echo $project[0]->created_on ?></td></tr>
        <tr><th class="td-label">Status</th><td><?php echo $project[0]->status; ?></td></tr>
	</table>
        </fieldset>
     </div>
     <?php if (!isset($row[0]->id)): ?>
     <p><?php echo t('no_study_found'); ?></p>
     <?php else: ?>
     <fieldset style="margin-bottom:-10px!important">
    <legend style="font-size:14.4px"><?php echo t('study_desc');?></legend>
  	</fieldset>
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('identification');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Title</th><td><?php echo $row[0]->ident_title; ?></td></tr>
        <tr><th class="td-label">Subtitle</th><td><?php echo $row[0]->ident_subtitle; ?></td></tr>
        <tr><th class="td-label">Abbreviation</th><td><?php echo ($row[0]->ident_abbr != '')?nl2br($row[0]->ident_abbr):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Study Type</th><td><?php echo ($row[0]->ident_study_type != '')?nl2br($row[0]->ident_study_type):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Series Information</th><td><?php echo ($row[0]->ident_ser_info != '')?nl2br($row[0]->ident_ser_info):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Translated Title</th><td><?php echo ($row[0]->ident_trans_title != '')?nl2br($row[0]->ident_trans_title):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">ID Number</th><td><?php echo ($row[0]->ident_id != '')?nl2br($row[0]->ident_id):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('versions');?></legend> 
        <div class="field">
        <table>
        <tr><th class="td-label">Description</th><td><?php echo ($row[0]->ver_desc != '')?nl2br($row[0]->ver_desc):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Production Date</th><td><?php echo $row[0]->ver_prod_date; ?></td></tr>
        <tr><th class="td-label">Notes</th><td><?php echo ($row[0]->ver_notes != '')?nl2br($row[0]->ver_notes):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('overview');?></legend>  
        <div class="field">
        <table>
        <tr><th class="td-label">Abstract</th><td><?php echo ($row[0]->overview_abstract != '')?nl2br($row[0]->overview_abstract):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Kind of Date</th><td><?php echo ($row[0]->overview_kind_of_data != '')?nl2br($row[0]->overview_kind_of_data):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Unit of Analysis</th><td><?php echo ($row[0]->overview_analysis != '')?nl2br($row[0]->overview_analysis):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Impact Evaluation Methods</th><td><?php echo $methods; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('scope');?></legend>   
        <div class="field">
        <table>
        <tr><th class="td-label">Description of Scope</th><td><?php echo ($row[0]->scope_definition != '')?nl2br($row[0]->scope_definition):'&nbsp;'; ?></td></tr>
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
        <tr><th class="td-label">Geographic Coverage</th><td><?php echo ($row[0]->coverage_geo != '')?nl2br($row[0]->coverage_geo):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Universe</th><td><?php echo ($row[0]->coverage_universe != '')?nl2br($row[0]->coverage_universe):'&nbsp;'; ?></td></tr>
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
        <tr><th class="td-label">Sampling Procedure</th><td><?php echo ($row[0]->sampling_procedure != '')?nl2br($row[0]->sampling_procedure):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Deviations from Sample Design</th><td><?php echo ($row[0]->sampling_dev != '')?nl2br($row[0]->sampling_dev):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Response Rates</th><td><?php echo ($row[0]->sampling_rates != '')?nl2br($row[0]->sampling_rates):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Weighting</th><td><?php echo ($row[0]->sampling_weight != '')?nl2br($row[0]->sampling_weight):'&nbsp;'; ?></td></tr>
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
        <tr><th class="td-label">Mode of Data Collection</th><td><?php echo ($row[0]->coll_mode != '')?nl2br($row[0]->coll_mode):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Notes of Data Collection</th><td><?php echo ($row[0]->coll_notes != '')?nl2br($row[0]->coll_notes):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Questionnaires</th><td><?php echo $row[0]->coll_questionnaire; ?></td></tr>
        <tr><th class="td-label">Data Collectors</th><td><?php echo $data_collectors; ?></td></tr>
        <tr><th class="td-label">Supervision</th><td><?php echo ($row[0]->coll_supervision != '')?nl2br($row[0]->coll_supervision):'&nbsp;'; ?></td></tr>
   		</table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_processing');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label">Data Editing</th><td><?php echo ($row[0]->process_editing != '')?nl2br($row[0]->process_editing):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Other Processing</th><td><?php echo ($row[0]->process_editing != '')?nl2br($row[0]->process_editing):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_appraisal');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label">Estimates of Sampling Error</th><td><?php echo ($row[0]->appraisal_error != '')?nl2br($row[0]->appraisal_error):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Other Forms of Data Appraisal</th><td><?php echo ($row[0]->appraisal_other != '')?nl2br($row[0]->appraisal_other):'&nbsp;'; ?></td></tr>
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
        <tr><th class="td-label">Confidentiality</th><td><?php echo ($row[0]->access_confidentiality != '')?nl2br($row[0]->access_confidentiality):'&nbsp;' ?></td></tr>
        <tr><th class="td-label">Access Conditions</th><td><?php echo ($row[0]->access_conditions != '')?nl2br($row[0]->access_conditions):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Citations Requirement</th><td><?php echo ($row[0]->access_cite_require != '')?nl2br($row[0]->access_cite_require):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('disclaimer_and_copyright');?></legend>   
        <div class="field">
        <table>
        <tr><th class="td-label">Disclaimer</th><td><?php echo ($row[0]->disclaimer_disclaimer != '')?nl2br($row[0]->disclaimer_disclaimer):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label">Copyright</th><td><?php echo ($row[0]->disclaimer_copyright != '')?nl2br($row[0]->disclaimer_copyright):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('operational_information');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label"><?php echo t('operational_wb_name');?></th><td><?php echo ($row[0]->operational_wb_name != '')?nl2br($row[0]->operational_wb_name):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('operational_wb_id');?></th><td><?php echo ($row[0]->operational_wb_id != '')?nl2br($row[0]->operational_wb_id):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('operational_wb_net');?></th><td><?php echo ($row[0]->operational_wb_net != '')?nl2br($row[0]->operational_wb_net):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('operational_wb_sector');?></th><td><?php echo ($row[0]->operational_wb_sector != '')?nl2br($row[0]->operational_wb_sector):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('operational_wb_summary');?></th><td><?php echo ($row[0]->operational_wb_summary != '')?nl2br($row[0]->operational_wb_summary):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('operational_wb_objectives');?></th><td><?php echo ($row[0]->operational_wb_objectives != '')?nl2br($row[0]->operational_wb_objectives):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('impact-evaluation');?></legend>
        <div class="field">
        <table>
        <tr><th class="td-label"><?php echo t('impact_wb_name');?></th><td><?php echo ($row[0]->impact_wb_name != '')?nl2br($row[0]->impact_wb_name):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('impact_wb_id');?></th><td><?php echo ($row[0]->impact_wb_id != '')?nl2br($row[0]->impact_wb_id):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('impact_wb_area');?></th><td><?php echo ($row[0]->impact_wb_area != '')?nl2br($row[0]->impact_wb_area):'&nbsp;'; ?></td></tr>
        <tr><th class="td-label"><?php echo t('impact_wb_lead');?></th><td><?php echo $impact_wb_lead; ?></td></tr>
        <tr><th class="td-label"><?php echo t('impact_wb_members');?></th><td><?php echo $impact_wb_members; ?></td></tr>
        <tr><th class="td-label"><?php echo t('impact_wb_description');?></th><td><?php echo ($row[0]->impact_wb_description != '')?nl2br($row[0]->impact_wb_description):'&nbsp;'; ?></td></tr>
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
<?php if (!empty($files)): ?>    
    </fieldset>
    <fieldset class="field-expandedx">
    <legend><?php echo t('data_files');?></legend>
<table style="margin-top:20px;margin-bottom:10px" class="grid-table" cellspacing="0" cellpadding="0">
<tr valign="top" align="left" style="height:10px" class="header">
    <th style="width:200px"><?php echo t('name');?></th>
    <th style="width:500px"><?php echo t('description');?></th>	
    <th style="width:80px"><?php echo t('type');?></th>
    <?php if ($this->uri->segment(1) == 'admin'): ?>
    <th style="width:100px"><?php echo t('download'); ?></th>
    <?php endif; ?>
    <!--<th>Exists</th>-->
</tr>
<?php $prefix = ""; ?>
<?php if (!empty($files)): ?>
	<?php foreach( $files as $file): ?>
        <tr valign="top">
            <td><?php echo $file['filename']; ?></td>
			<td><?php echo isset($file['description']) ? $file["description"] : 'N/A';?></td>            
            <td><?php echo (isset($file['dctype'])) ? preg_replace('#\[.*?\]#', '', $file['dctype']) : 'N/A';?></td>
		    <?php if ($this->uri->segment(1) == 'admin'): ?>
            <td><?php echo "<a href=", site_url('datadeposit/download'), '/', $file['id'], ">Download</a>"; ?> </td> 
        	<?php endif; ?>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
<?php endif; ?>
    </fieldset>
    <?php if (isset($row[0]->citations)): ?>
    <fieldset class="field-expandedx">
    <legend><?php echo t('citations');?></legend> 
	<div class="field">
    <table>
    <tr><td><?php echo $row[0]->citations; ?></td></tr>
    </table>
    </div>

    
    </fieldset>
<?php endif; ?>
    <?php endif; ?>
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