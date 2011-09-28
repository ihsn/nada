<style>
	.sr{padding-right:10px;}
	label{font-weight:bold;}
	fieldset{margin-top:20px;margin-bottom:10px;}
</style>
<?php
// Export form for producing reports in PDF or Word format
?>
<h1 class="xsl-title"><?php echo t('export_documentation');?></h1>
<p><?php echo t('download_instructions');?></p>
<form method="post" action="<?php echo site_url().'/catalog/'.$this->uri->segment(2); ?>/export">
	<fieldset>
	    <legend><?php echo t('select_output_format');?></legend>
        <div>
    	<span class="sr"><input type="radio" name="format" value="pdf" checked="checked"/>PDF</span>
    	<span><input type="radio" name="format" value="html"/>HTML</span>
        </div>
    </fieldset>
    <input type="submit" value="<?php echo t('generate');?>" name="generate"/>
</form>