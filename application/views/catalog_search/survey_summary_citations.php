<?php /*
<?php if (!$this->input->get("print")) :?>
<?php if (isset($survey)):?>
<div style="text-align:right;">
	<a target="_blank" href="<?php echo site_url();?>/catalog/citations/<?php echo $this->uri->segment(3); ?>/?print=yes" ><img src="images/print.gif" border="0"/> <?php echo t('print');?></a>
</div>
<?php endif;?>
<?php endif;?>
*/
?>
<?php if ($citations): ?>

<?php 
	//sort
	$sort_by=$this->input->get("sort_by");
	$sort_order=$this->input->get("sort_order");
	$page_url=site_url().'/catalog/'.(int)$this->uri->segment(2).'/related_citations';
?>

<div class="related-citations">
    <?php if (isset($survey)):?>
		<h1><?php echo $survey['nation'] . ' - ' . $survey['title']; ?></h1>
    <?php endif;?>

	<h2 style="margin-bottom:0px;"><?php echo t('citations_of_publications');?></h2>
    <div class="subtext" style="margin-bottom:20px;"><?php echo t('related_publications_text');?></div>
    
    <div class="citations-found"><?php echo t('Found: '),count($citations);?></div>
    <div class="sort-links" >
		<?php echo t('Sort by:');?>    
        <?php echo create_sort_link($sort_by,$sort_order,'authors',t('Author'),$page_url,array('keywords','field','collection') ); ?>
        <?php echo create_sort_link($sort_by,$sort_order,'pub_year',t('date'),$page_url,array('keywords','field','collection') ); ?>
        <?php echo create_sort_link($sort_by,$sort_order,'title',t('title'),$page_url,array('keywords','field','collection') ); ?>
    
    </div>
    <div class="citation-rows">
	<?php $tr_class=""; ?>
	<?php $k=0;foreach($citations as $row):$k++; ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <div class="citation-row <?php echo $tr_class; ?>" data-url="<?php echo site_url('citations/'.$row['id']);?>" title="<?php echo t('View citation');?>">
            <span class="page-num"><?php echo $k;?></span>
            <span class="row-body"><?php echo $this->chicago_citation->format($row,$row['ctype']);?></span>
        </div>
    <?php endforeach;?>
    </div>	
<?php endif; ?>
</div>
<?php 
//turn all links into ajax links if page was accessed using the ajax parameter
if ($this->input->get("ajax")): ?>
<script type="text/javascript">
$(function() {	
	$(".citation-row a").each(function(index) {
		$(this).attr("href",$(this).attr("href")+"?ajax=true");
  	});	
});
</script>
<?php endif; ?>

<script type="text/javascript">
$(document).ready(function () { 
	$(".citation-row").click(function(){
		window.location=$(this).attr("data-url");
		return false;
	});
});
</script>