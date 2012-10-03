<?php if (isset($this->blocks['rightsidebar'])):?>
<?php //var_dump($this->blocks);?>

<?php 
/*
$data['survey_count']=$this->Stats_model->get_survey_count(); 
$data['variable_count']=$this->Stats_model->get_variable_count();
$data['citation_count']=$this->Stats_model->get_citation_count();
$this->load->view("blocks/catalog_status",$data);
*/

 ?>

	<?php foreach($this->blocks['rightsidebar'] as $block):?>
    <div class="grey-module" id="sidebar-faq">
        <div class="m-head"> 
            <h2><?php echo $block['title'];?></h2>
        </div>
        
        <div class="m-body">
            <div class="right-border">
            <?php if ($block['block_format']=='php'):?>            
                    <?php
                    $filepath='cache/block-'.$block['block_name'].'.php';	
                    if (file_exists($filepath))
					{
						include $filepath;
					}
					else if (!file_exists($filepath))
					{
						if (@file_put_contents($filepath,$block['body']))
						{
                    		include $filepath;
						}
						{
							log_message('ERROR', "Failed to create block file - ".$filepath);
						}							
					}	
                    ?>
            <?php else:?>
                <?php echo $block['body'];?>
            <?php endif; ?>
            <br/>
            </div>
            
        </div>        
        <div class="m-footer"><span>&nbsp;</span></div>
    </div>
    <?php endforeach;?>
<?php endif;?>

<?php /*

<!--sidebar-reference-owner-->
<div class="grey-module" id="sidebar-faq">
    <div class="m-head"> 
        <h2>FAQ'S</h2>
    </div>
    <div class="m-body">
     <ul>
      <li><a href="http://microdata.worldbank.org/index.php/faqs#improve">How can I contribute to improving the catalog?</a></li>
      <li><a href="http://microdata.worldbank.org/index.php/faqs#analyze">Can you help with analyzing the data?</a></li>
      <li><a href="http://microdata.worldbank.org/index.php/faqs#tools">Can I get help in implementing a survey catalog in my agency?</a></li>	  
	</ul>
    </div><div class="m-footer"><span>&nbsp;</span></div>
</div>
<!--end-sidebar-reference-owner-->
*/ ?>


