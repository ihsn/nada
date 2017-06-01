<style>
.ui-widget-overlay {background:black; }
.ui-dialog .ui-dialog-content{padding:0px;}
</style>  
<?php if ($rows): ?>

<?php		
	//sorting
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");	
	
	//current page url with query strings
	$page_url=site_url().$this->uri->uri_string();		

	//page querystring for variable sub-search
	$variable_qs=get_querystring(array('keyword1','field1','keyword2','field2','op') );	
?>
<table style="width:100%;" border="0" cellpadding="0" cellspacing="0">
<tr>
<td>
<div class="catalog-sort-links">
<?php echo t('sort_results');?>:
<?php 
	//sort links
	
	//titl
	echo create_sort_link($sort_by,$sort_order,'titl','Title',$page_url,array('keyword1','field1','keyword2','field2','op') ); 
	
	//year	
	echo create_sort_link($sort_by,$sort_order,'proddate','Year',$page_url,array('keyword1','field1','keyword2','field2','op') ); 

	//nation	
	echo create_sort_link($sort_by,$sort_order,'nation','Country',$page_url,array('keyword1','field1','keyword2','field2','op') ); 
?>
</div>
</td>
<td align="right"><span class="catalog-sort-links" style="padding-left:20px;">Page size: 5 10 15 20</span></td>
</tr>
</table>
<?php
	//create pagination bar
	$pagination='<table width="100%" class="pagination">';
	$pagination.='<tr>';
	$pagination.='<td>'.$this->pagination->get_total_rows().' records found</td>';
	$pagination.='<td align="right">'.$this->pagination->create_links().'</td>';
	$pagination.='</tr>';
	$pagination.='</table>';
	echo $pagination;
?>

<?php foreach($rows as $row): ?>
	<div class="survey-row">
        <div class="title"><?php echo anchor('data-catalog/survey/'.$row['id'],$row['titl']);?></div>
        <div class="sub-title"><?php echo $row['authenty'];?>, <em><?php echo $row['refno'];?></em></div>
		<?php if ( isset($row['totalFound']) ): ?>
        <div class="variables-found">
        		<a href="<?php echo site_url(); ?>/data-catalog/<?php echo $row['id']; ?>/vsearch/?<?php echo $variable_qs; ?>">
        		Keyword(s) found in <?php echo $row['totalFound'];?> variable(s) out of <?php echo $row['varcount'];?>
                </a>
        </div>
        <?php endif; ?>
        <div class="survey-icons">
            <div style="float: left;">                
            
                <a target="_blank" title="Browse metadata" href="<?php echo site_url().'/ddibrowser/'.$row['id'];?>">
                <span><img src="images/page_white_cd.png" />Browse metadata</span>
                </a>
                
                <a target="_blank" id="ap-<?php echo $row['id'];?>" class="accesspolicy"  title="Microdata access policy" href="<?php echo site_url().'/data-catalog/'.$row['id'];?>/accesspolicy">
                	<span><img src="images/page_white_cd.png" />Access policy</span>
                </a>
                
                <?php if($row['form_model']=='direct'): ?>
                    <a href="<?php echo site_url().'/access_direct/'.$row['id'];?>" class="accessform" title="Microdata request form(Direct access)">
                    <span><img src="images/form_direct.gif" />Data</span>
                    </a>                    
                <?php elseif($row['form_model']=='public'): ?>                    
                    <a href="<?php echo site_url().'/access_public/'.$row['id'];?>" class="accessform"  title="Microdata request form(Public access)">
                    <span><img src="images/form_public.gif" />Data</span>
                    </a>                    
                <?php elseif($row['form_model']=='licensed'): ?>
                    <a href="<?php echo site_url().'/access_licensed/'.$row['id'];?>" class="accessform"  title="Microdata request form(Licensed access)">
                    <span><img src="images/form_licensed.gif" />Data</span>
                    </a>                    
                <?php elseif($row['form_model']=='data_enclave'): ?>
                    <a href="<?php echo site_url().'/access_enclave/'.$row['id'];?>" class="accessform"  title="Microdata request form(Data enlclave)">
                    <span><img src="images/form_enclave.gif" />Data</span>
                    </a>                    
                <?php endif; ?>
                
            </div>
            <div style="float: right;">

				<?php if($row['link_report']!=''): ?>
                    <a href="<?php echo site_url().'/data-catalog/'.$row['id'].'/download/?file='.urlencode($row['link_report']);?>" title="Reports and analytical output">
                        <img border="0" title="Reports and analytical output" alt="Reports" src="images/report.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_indicator']!=''): ?>
                    <a href="<?php echo site_url().'/data-catalog/'.$row['id'].'/download/?file='.urlencode($row['link_indicator']);?>" title="Indicators and tables (database)">
                        <img border="0" alt="Indicators and tables (database)" src="images/page_white_database.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_questionnaire']!=''): ?>
                    <a href="<?php echo site_url().'/data-catalog/'.$row['id'].'/download/?file='.urlencode($row['link_questionnaire']);?>" title="Questionnaire">
                        <img border="0" alt="Questionnaire" title="Questionnaire" src="images/page_question.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_technical']!=''): ?>
                    <a href="<?php echo site_url().'/data-catalog/'.$row['id'].'/download/?file='.urlencode($row['link_technical']);?>" title="Technical documentation in PDF">
                        <img border="0" alt="Documentation" title="Technical documentation in PDF" src="images/page_white_compressed.png" />
                    </a>
                <?php endif; ?>

                <?php if($row['link_technical']!=''): ?>
                    <a href="<?php echo site_url().'/data-catalog/'.$row['id'].'/download/?file='.urlencode($row['link_study']);?>" title="Study website (with all available documentation)">
                        <img border="0" title="Study website (with all available documentation)" alt="Study URL" src="images/page_white_world.png" />
                    </a>
                <?php endif; ?>
			    <?php if($row['isshared']!=''): ?>
                    <a href="<?php echo site_url().'/data-catalog/'.$row['id'].'/ddi';?>" title="Survey metadata in DDI format">
                        <img border="0" title="Survey metadata in DDI" alt="DDI" src="images/ddi2.gif" />
                    </a>
                <?php endif; ?>            
            </div>
            <br/>
        </div>      		
    </div>
<?php endforeach;?>
<?php echo $pagination; ?>
<?php else: ?>
	No surveys found
<?php endif; ?>
	<div style="display:none;">
    <span id="dialog-contents">
    	<iframe 
        	id="dialog-iframe" width="100%" height="100%" 
            marginWidth="0" marginHeight="0" frameBorder="0" 
            scrolling="auto" src="javascript/iframeloading.html"></iframe>
        </span>
	</div>
<script type="text/javascript">
$(document).ready(function() 
{
	//access policy dialog
	$('.accesspolicy, .accessform').click(function(event) 
	{
			iframe_dialog($(this).attr('href')+'?ajax=1', $(this).attr('title')+ ' - '+ $(this).parents(".survey-row").find(".title a").html());
			return false;
	});
	

	//iframe dialog	
	function iframe_dialog(url,title)
	{
		$( "#dialog-contents" ).dialog( "option", "title", title );

		if ($.browser.msie) //ie
		{
			$( "#dialog-contents" ).dialog( "option", "width", $(window).width()-100 );
			$( "#dialog-contents" ).dialog( "option", "height", $(window).height()-100 );
		}
		else
		{
			$( "#dialog-contents" ).dialog( "option", "width", $(window).width()-100 );
			$("#dialog-iframe").width( $( "#dialog-contents" ).dialog( "option", "width" ));
			$("#dialog-iframe").height($(window).height()-100);	
		}	
	    $("#dialog-iframe").attr("src",url);
		$("#dialog-contents").dialog("open");		
	}
	
	//initialize dialog
	function iframe_dialog_init(){		
		$("#dialog-contents").dialog({
			beforeclose: iframe_before_close,
           autoOpen: false,
           modal: true,
           height: $(window).height()-100,
           width: $(window).width()-100
       });

		if (!$.browser.msie) //for non-ie
		{
		    $("#dialog-iframe").width($(window).width()-100);	
			$("#dialog-iframe").height($(window).height()-100);				
		}
	}
	
	function iframe_before_close()
	{
		$("#dialog-iframe").attr("src",'javascript/iframeloading.html');
	}
	//initialize_dialog
	iframe_dialog_init();	
});
</script>
