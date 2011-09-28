<?php if (!$this->input->get("print")) :?>
<?php if (isset($survey)):?>
<div style="text-align:right;">
	<a target="_blank" href="<?php echo site_url();?>/catalog/citations/<?php echo $this->uri->segment(3); ?>/?print=yes" ><img src="images/print.gif" border="0"/> <?php echo t('print');?></a>
</div>
<?php endif;?>
<?php endif;?>

<?php if ($citations): ?>
	<div style="padding-top:20px;"/>
    <?php if (isset($survey)):?>
		<h1><?php echo $survey['nation'] . ' - ' . $survey['titl']; ?></h1>
    <?php endif;?>

	<h2><?php echo t('citations_of_publications');?></h2>
    <table class="grid-table">
	<?php $tr_class=""; ?>
	<?php foreach($citations as $row): ?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr class="<?php echo $tr_class; ?>">    	      
            <td><div class="citation-row">
                &raquo; <?php echo $this->chicago_citation->format($row,$row['ctype']);?>
              </div>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
	</div>
<?php endif; ?>

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