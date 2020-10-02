<?php 
if (isset($_GET['print']) && $_GET['print'] == 'yes'): ?>
<style type="text/css">
	th {
		text-align: left;
	}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link type="text/css" rel="stylesheet" href="<?php echo site_url(); ?>/../themes/opendata/datadeposit.css" />
<script type="text/javascript" src="<?php echo site_url(); ?>/../javascript/jquery/jquery.js"></script>
<?php endif; ?>
<style type="text/css">
.error, .success {
	margin-left: 10px !important;
	width: 966px !important;
}
.field { margin: 1px 0 10px 0!important; padding:0!important }
fieldset {margin-bottom:-10px!important}
legend{margin-bottom:0!important;margin-top:0!important}
.tab-header {
	margin-top: 73px ;
}
<?php if ($project[0]->status == 'draft'): ?>
.tab-header {
	margin-top: 91px !important;
}
<?php endif; ?>
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

h2{font-size:16px;font-weight:bold;margin-top:20px;}
fieldset{border:0px}
.red{ color: #ff0000; }
</style>
<script type="text/javascript"> 
	$(function() {
	// Open print dialog box after 2 seconds 
	<?php if ($this->uri->segment(1) != 'admin'): ?>
	if (!$('.bodycontainer').length) {
		setTimeout(function() {
			window.print();
		}, 2000);
	}
	<?php else: ?>
	if (!$('#content').length) {
		setTimeout(function() {
			window.print();
		}, 2000);
	}
	<?php endif; ?>
		
	// Email to friend
	$('#email_to_friend').click(function(e) {
		e.preventDefault();
		email=prompt("<?php echo t('provide_email'); ?>", null);
		if (!email) {
			return;
		}
		re= /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		if (re.test(email)) {
			$.post("<?php echo site_url('datadeposit/email_to_friend'), '/', $this->uri->segment(3); ?>", 
				{email: email},
				function(data) {
					if (data) {
						alert("<?php echo t('email_sent_successful'); ?>");
					}
				}
			);
		} else {
			alert("<?php echo t('invalid_email'); ?>");
		}
	});
	/* Required fields */
	var toHighlight = [];
		$.each($('.td-label'), function() {
			var d = $.trim($(this).next().html());
			if (d == "" || d == null || d == "&nbsp;" || d == "--" || d == '0000-00-00' || d == '0') {
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
    <?php //if ($this->uri->segment(1) == 'admin'): ?>
      <div style="font-size:14px;width:100%;text-align:right">
        Generate: <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=ddi">DDI</a>
        |
        <a href="<?php echo site_url('datadeposit'); ?>/export/<?php echo $project[0]->id; ?>?format=rdf">RDF</a>
        |
        <a target="_blank" href="<?php echo current_url();?>?print=yes">Print Preview</a>
		|
     	<a href="javascript:void(0);" id="email_to_friend"><?php echo t('email_to_friend'); ?></a>
     </div>
    <?php // endif; ?>
    <?php if ($project[0]->status == 'draft' && $this->uri->segment(1) != 'admin'): ?>
	<?php foreach($fields['merged'] as $key => $value):
		if (!isset($row[0]->$key) || empty($row[0]->$key) || $row[0]->$key == '[]' || $row[0]->$key == ' ' || $row[0]->$key == '--' || $row[0]->$key == '0000-00-00'): ?>
        <?php echo '<span class="mandatory" style="color:#ff0000;margin:5px;font-size:10pt;">', $value, ' is a mandatory field that is not filled.</span><br />', PHP_EOL; ?>
        <?php else: 
            $key = str_replace('coverage_country', 'country', $key); 
            $key = str_replace('coll_dates', 'dates_datacollection', $key); ?> 
        <?php echo '<script>$(function() {$("li.', strtolower($key), '").css("display", "none");});', '</script>', PHP_EOL; ?>
        <?php endif; ?>
		<?php endforeach; ?> 
    <?php endif; ?>
       <div id="toTop">^ Back to Top</div>
<div class="contents">

    <div class="field"> 

        <fieldset style="" class="field-expandedx">
        <legend><?php echo t('project_info');?></legend> 
 
    <table>
        <tr><th align="left" class="td-label">Title</th><td><?php echo $project[0]->title; ?></td></tr>
        <tr><th align="left" class="td-label">Description</th><td><?php echo $project[0]->description; ?></td></tr>
        <tr><th align="left" class="td-label">Collaboration</th><td><?php echo $project[0]->collaborators; ?></td></tr>
        <tr><th align="left" class="td-label">Project ID</th><td><?php echo $project[0]->id; ?></td></tr>
        <tr><th align="left" class="td-label">Created By</th><td><?php echo $project[0]->created_by; ?></td></tr>
        <tr><th align="left" class="td-label">Date Created On</th><td><?php echo date('Y-m-d', $project[0]->created_on) ?></td></tr>
        <tr><th align="left" class="td-label">Status</th><td><?php echo $project[0]->status; ?></td></tr>
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
        <tr><th align="left" class="td-label">Title</th><td><?php echo $row[0]->ident_title; ?></td></tr>
        <tr><th align="left" class="td-label">Subtitle</th><td><?php echo $row[0]->ident_subtitle; ?></td></tr>
        <tr><th align="left" class="td-label">Abbreviation</th><td><?php echo ($row[0]->ident_abbr != '')?nl2br($row[0]->ident_abbr):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Study Type</th><td><?php echo ($row[0]->ident_study_type != '')?nl2br($row[0]->ident_study_type):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Series Information</th><td><?php echo ($row[0]->ident_ser_info != '')?nl2br($row[0]->ident_ser_info):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Translated Title</th><td><?php echo ($row[0]->ident_trans_title != '')?nl2br($row[0]->ident_trans_title):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">ID Number</th><td><?php echo ($row[0]->ident_id != '')?nl2br($row[0]->ident_id):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('versions');?></legend> 
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Description</th><td><?php echo ($row[0]->ver_desc != '')?nl2br($row[0]->ver_desc):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Production Date</th><td> <?php 
					$obj = $row[0]->ver_prod_date;
					
					$obj = is_object($obj) ? $obj->format('Y-m-d') : date('Y-m-d', $obj) ;
					echo $obj;
		?></td></tr>
        <tr><th align="left" class="td-label">Notes</th><td><?php echo ($row[0]->ver_notes != '')?nl2br($row[0]->ver_notes):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('overview');?></legend>  
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Abstract</th><td><?php echo ($row[0]->overview_abstract != '')?nl2br($row[0]->overview_abstract):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Kind of Data</th><td><?php echo ($row[0]->overview_kind_of_data != '')?nl2br($row[0]->overview_kind_of_data):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Unit of Analysis</th><td><?php echo ($row[0]->overview_analysis != '')?nl2br($row[0]->overview_analysis):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Impact Evaluation Methods</th><td><?php echo $methods; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('scope');?></legend>   
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Description of Scope</th><td><?php echo ($row[0]->scope_definition != '')?nl2br($row[0]->scope_definition):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Topics Classifications</th><td><?php echo $topic_class ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('coverage');?></legend> 
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Country</th><td><?php echo $country ?></td></tr>
        <tr><th align="left" class="td-label">Geographic Coverage</th><td><?php echo ($row[0]->coverage_geo != '')?nl2br($row[0]->coverage_geo):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Universe</th><td><?php echo ($row[0]->coverage_universe != '')?nl2br($row[0]->coverage_universe):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('producers_and_sponsors');?></legend>  
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Primary Investigator</th><td><?php echo $prim_investigator; ?></td></tr>
        <tr><th align="left" class="td-label">Other Producers</th><td><?php echo $other_producers; ?></td></tr>
        <tr><th align="left" class="td-label">Funding</th><td><?php echo $funding ?></td></tr>
        <tr><th align="left" class="td-label">Other Acknowledgements</th><td><?php echo $acknowledgements; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('sampling');?></legend> 
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Sampling Procedure</th><td><?php echo ($row[0]->sampling_procedure != '')?nl2br($row[0]->sampling_procedure):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Deviations from Sample Design</th><td><?php echo ($row[0]->sampling_dev != '')?nl2br($row[0]->sampling_dev):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Response Rates</th><td><?php echo ($row[0]->sampling_rates != '')?nl2br($row[0]->sampling_rates):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Weighting</th><td><?php echo ($row[0]->sampling_weight != '')?nl2br($row[0]->sampling_weight):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_collection');?></legend>
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Dates of Collection</th><td><?php echo $dates_datacollection; ?></td></tr>
        <tr><th align="left" class="td-label">Time Periods</th><td><?php echo $time_periods; ?></td></tr>
        <tr><th align="left" class="td-label">Mode of Data Collection</th><td><?php echo ($row[0]->coll_mode != '')?nl2br($row[0]->coll_mode):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Notes of Data Collection</th><td><?php echo ($row[0]->coll_notes != '')?nl2br($row[0]->coll_notes):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Questionnaires</th><td><?php echo $row[0]->coll_questionnaire; ?></td></tr>
        <tr><th align="left" class="td-label">Data Collectors</th><td><?php echo $data_collectors; ?></td></tr>
        <tr><th align="left" class="td-label">Supervision</th><td><?php echo ($row[0]->coll_supervision != '')?nl2br($row[0]->coll_supervision):'&nbsp;'; ?></td></tr>
   		</table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_processing');?></legend>
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Data Editing</th><td><?php echo ($row[0]->process_editing != '')?nl2br($row[0]->process_editing):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Other Processing</th><td><?php echo ($row[0]->process_editing != '')?nl2br($row[0]->process_editing):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_appraisal');?></legend>
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Estimates of Sampling Error</th><td><?php echo ($row[0]->appraisal_error != '')?nl2br($row[0]->appraisal_error):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Other Forms of Data Appraisal</th><td><?php echo ($row[0]->appraisal_other != '')?nl2br($row[0]->appraisal_other):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('data_access');?></legend> 
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Access Authority</th><td><?php echo  $access_authority; ?></td></tr>
        <tr><th align="left" class="td-label">Confidentiality</th><td><?php echo ($row[0]->access_confidentiality != '')?nl2br($row[0]->access_confidentiality):'&nbsp;' ?></td></tr>
        <tr><th align="left" class="td-label">Access Conditions</th><td><?php echo ($row[0]->access_conditions != '')?nl2br($row[0]->access_conditions):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Citation Requirement</th><td><?php echo ($row[0]->access_cite_require != '')?nl2br($row[0]->access_cite_require):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>
    
    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('disclaimer_and_copyright');?></legend>   
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Disclaimer</th><td><?php echo ($row[0]->disclaimer_disclaimer != '')?nl2br($row[0]->disclaimer_disclaimer):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label">Copyright</th><td><?php echo ($row[0]->disclaimer_copyright != '')?nl2br($row[0]->disclaimer_copyright):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('operational_information');?></legend>
        <div class="field">
        <table>
        <tr><th align="left" class="td-label"><?php echo t('operational_wb_name');?></th><td><?php echo ($row[0]->operational_wb_name != '')?nl2br($row[0]->operational_wb_name):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('operational_wb_id');?></th><td><?php echo ($row[0]->operational_wb_id != '')?nl2br($row[0]->operational_wb_id):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('operational_wb_net');?></th><td><?php echo ($row[0]->operational_wb_net != '')?nl2br($row[0]->operational_wb_net):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('operational_wb_sector');?></th><td><?php echo ($row[0]->operational_wb_sector != '')?nl2br($row[0]->operational_wb_sector):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('operational_wb_summary');?></th><td><?php echo ($row[0]->operational_wb_summary != '')?nl2br($row[0]->operational_wb_summary):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('operational_wb_objectives');?></th><td><?php echo ($row[0]->operational_wb_objectives != '')?nl2br($row[0]->operational_wb_objectives):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('impact-evaluation');?></legend>
        <div class="field">
        <table>
        <tr><th align="left" class="td-label"><?php echo t('impact_wb_name');?></th><td><?php echo ($row[0]->impact_wb_name != '')?nl2br($row[0]->impact_wb_name):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('impact_wb_id');?></th><td><?php echo ($row[0]->impact_wb_id != '')?nl2br($row[0]->impact_wb_id):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('impact_wb_area');?></th><td><?php echo ($row[0]->impact_wb_area != '')?nl2br($row[0]->impact_wb_area):'&nbsp;'; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('impact_wb_lead');?></th><td><?php echo $impact_wb_lead; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('impact_wb_members');?></th><td><?php echo $impact_wb_members; ?></td></tr>
        <tr><th align="left" class="td-label"><?php echo t('impact_wb_description');?></th><td><?php echo ($row[0]->impact_wb_description != '')?nl2br($row[0]->impact_wb_description):'&nbsp;'; ?></td></tr>
        </table>
        </div>
        </fieldset>
    </div>

    <div class="field"> 
        <fieldset class="field-expandedx">
        <legend><?php echo t('contacts');?></legend>   
        <div class="field">
        <table>
        <tr><th align="left" class="td-label">Contact Persons</th><td><?php echo $contacts; ?></td></tr>
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
    <th align="left" style="width:200px"><?php echo t('name');?></th>
    <th align="left" style="width:500px"><?php echo t('description');?></th>	
    <th align="left" style="width:80px"><?php echo t('type');?></th>
    <?php if ($this->uri->segment(1) == 'admin'): ?>
    <th align="left" style="width:100px"><?php echo t('download'); ?></th>
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
            <td><?php echo "<a style='text-decoration:underline' href=", site_url('datadeposit/download'), '/', $file['id'], ">Download</a>"; ?> </td> 
        	<?php endif; ?>
        </tr>
    <?php endforeach;?>        
<?php endif;?>
</table>
<?php endif; ?>
    </fieldset>
    <?php if (sizeof($this->active_citations)): ?>
    <fieldset class="field-expandedx">
    <legend><?php echo t('citations');?></legend> 
	<div class="field">
 <table style="margin-top:20px;margin-bottom:10px" class="grid-table" width="100%" cellspacing="0" cellpadding="0">
    	<tr style="height:10px"  class="header">
            <th><?php echo t('citation_type'); ?></th>
            <th><?php echo t('title'); ?></th>
			<th><?php echo t('date'); ?></th>
			<th><?php echo t('created'); ?></th>            
            <th><?php echo t('modified'); ?></th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
        </tr>
	<?php $tr_class=""; ?>
	<?php foreach($this->active_citations as $row): ?>
    	<?php $row=(object)$row; //var_dump($row);exit;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">
            <td><?php echo t($row->ctype); ?></td>
            <td><?php echo $row->title;?></td>
            <td nowrap="nowrap"><?php echo $row->pub_year; ?>&nbsp;</td>
            <td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->created); ?></td>
			<td nowrap="nowrap"><?php echo date($this->config->item('date_format'), $row->changed); ?></td>            
			
            <td nowrap="nowrap">&nbsp;
            


            </td>
            <td nowrap="nowrap">&nbsp;
			
            </td>
            <td>&nbsp;    
            

            </td>
            <td>&nbsp;    
            
            </td>
        </tr>
    <?php endforeach;?>
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