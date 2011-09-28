<?php
	$field1=$this->input->get('field1');
	if (!is_array($field1) )
	{
		$field1=array($field1);
	}
?>

<table width="100%" class="catalog-page-title" cellpadding="0" cellspacing="0" border="0">
<tr valign="baseline">
<td><h1><?php echo $title;?></h1></td>
<td>
<div class="page-links">
	<?php 
	//anchor_popup attributes
	$atts = array(
              'width'      => '800',
              'height'     => '600',
              'scrollbars' => 'yes',
              'status'     => 'yes',
              'resizable'  => 'yes',
              'screenx'    => '0',
              'screeny'    => '0'
            );
	?>
	<a href="<?php echo site_url();?>/data-catalog/export"><img src="images/export.gif" border="0"/></a>
	<?php echo anchor_popup(site_url().'/data-catalog/help','<img src="images/linkto_help.gif" border="0"/>',$atts);?>
    <a href="<?php echo site_url();?>/data-catalog/rss"><img src="images/rss_icon.png" border="0"/></a>
</div>
</td>
</tr>
</table>

<form id="catalog_search_form" style="padding:0px;margin:0px;">
<div class="search-options">
<table cellspacing="0" cellpadding="2" >
<tr>
	<td>&nbsp;</td>
    <td>
    	<select name="country" style="width:175px">
        	<option>--select country--</option>
        </select>
    </td>
    <td>&nbsp;</td>
    <td style="text-align:right"><a href="<?php echo site_url(); ?>/data-catalog">Display all</a></td>
</tr>
<tr>
	<td>Search</td>
    <td><input type="text"  id="keyword1" name="keyword1" value="<?php echo $this->input->get('keyword1'); ?>" style="width:170px"/></td>
    <td>&nbsp;in&nbsp;</td>
    <td>
        <select id="field1" name="field1[]" >
	    <option value="all">All fields</option>
        <optgroup label="Study Description">
            <option value="titl" 		<?php echo in_array('titl',$field1) ? 'selected="selected"' : ''; ?>	>Title</option>
            <option value="nation" 		<?php echo in_array('nation',$field1) ? 'selected="selected"' : ''; ?>	>Country, geographic coverage</option>
            <option value="type"		<?php echo in_array('type',$field1) ? 'selected="selected"' : ''; ?>	>Study type</option>
            <option value="producer"	<?php echo in_array('producer',$field1) ? 'selected="selected"' : ''; ?>>Producers, sponsors</option>
            <option value="proddate"	<?php echo ($this->input->get('field1')=='proddate') ? 'selected="selected"' : ''; ?>>Year of data collection</option>
            <option value="scope"		<?php echo ($this->input->get('field1')=='scope') ? 'selected="selected"' : ''; ?>	>Description of scope</option>
            <option value="refno"		<?php echo ($this->input->get('field1')=='refno') ? 'selected="selected"' : ''; ?>	>Reference number</option>
        </optgroup>
        <optgroup label="Variable Description">
            <option value="name,labl,qstn"		<?php echo ($this->input->get('field1')=='name,labl,qstn') ? 'selected="selected"' : ''; ?>	>Name, label, literal question</option>
            <option value="catgry"	<?php echo ($this->input->get('field1')=='catgry') ? 'selected="selected"' : ''; ?>	>Categories</option>
        </optgroup>
        </select>
    </td>
    <td>&nbsp;</td>
</tr>
<tr>
	<td>
    	<select name="op">
        	<option>AND</option>
            <option>OR</option>
        </select>
    </td>
    <td><input type="text"  id="keyword2" name="keyword2" value="<?php echo $this->input->get('keyword2'); ?>" style="width:170px"/></td>
    <td>&nbsp;in&nbsp;</td>
    <td>
        <select id="field2" name="field2[]" >
            <option value="all">All fields</option>
            <optgroup label="Study Description">
                <option value="titl" 		<?php echo ($this->input->get('field2')=='titl') ? 'selected="selected"' : ''; ?>	>Title</option>
                <option value="nation" 		<?php echo ($this->input->get('field2')=='nation') ? 'selected="selected"' : ''; ?>	>Country, geographic coverage</option>
                <option value="type"		<?php echo ($this->input->get('field2')=='type') ? 'selected="selected"' : ''; ?>	>Study type</option>
                <option value="producer"	<?php echo ($this->input->get('field2')=='producer') ? 'selected="selected"' : ''; ?>>Producers, sponsors</option>
                <option value="proddate"	<?php echo ($this->input->get('field2')=='proddate') ? 'selected="selected"' : ''; ?>>Year of data collection</option>
                <option value="scope"		<?php echo ($this->input->get('field2')=='scope') ? 'selected="selected"' : ''; ?>	>Description of scope</option>
                <option value="refno"		<?php echo ($this->input->get('field2')=='refno') ? 'selected="selected"' : ''; ?>	>Reference number</option>
            </optgroup>
            <optgroup label="Variable Description">
                <option value="name,labl,qstn"		<?php echo ($this->input->get('field2')=='name,labl,qstn') ? 'selected="selected"' : ''; ?>	>Name, label, literal question</option>
                <option value="catgry"	<?php echo ($this->input->get('field2')=='catgry') ? 'selected="selected"' : ''; ?>	>Categories</option>
            </optgroup>
        </select>
    </td>
    <td><input type="submit" id="btn-search" name="go" value="Go"/></td>    
</tr>
</table>
</div>
</form>
<div id="surveys">
<?php print $survey_list; ?>
</div>