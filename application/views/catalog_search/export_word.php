<?php
/*
* Export search result to word document
*/
?>
<style>
	body,html{font-family:Verdana, Arial, Helvetica, sans-serif;font-size:11pt;}
	.title{font-size:15pt;}
	.header{border-bottom:1px solid gray;}
</style>
<?php if ($rows && $this->input->get("view")!='v'):?>
	<div>
    	<h1><?php echo t('data_catalog');?>: <?php echo site_url().'/catalog/';?></h1>
        <h2><?php echo t('created_on');?>: <?php echo date("M-d-Y",date("U"));?></h2>
    </div>
	<br/>
    <br/>
	<table>
	<?php foreach ($rows as $row):?>
    <?php $row=(object)$row;?>
    	<tr>
	    	<td colspan="2" class="title"><?php echo utf8_decode($row->titl);?></td>
        </tr>
    	<tr>
	    	<td class="">Primary Inv.</td>
            <td class=""><?php echo utf8_decode($row->authoring_entity);?></td>
        </tr>    
    	<tr>
	    	<td class="">Country</td>
            <td class=""><?php echo utf8_decode($row->nation);?></td>
        </tr>    
        <?php if ($row->proddate):?>
    	<tr>
	    	<td class="">Year</td>
            <td class=""><?php echo $row->proddate;?></td>
        </tr>
        <?php endif;?>
    	<tr>
	    	<td class="">Reference no.</td>
            <td class=""><?php echo $row->refno;?></td>
        </tr>
    	<tr>
	    	<td class="">&nbsp;</td>
            <td class=""><a href="<?php echo site_url().'/catalog/'.$row->id;?>"><?php echo site_url().'/catalog/'.$row->id;?></a></td>
        </tr>
    	<tr>
	    	<td colspan="2" style="border-bottom:1px solid gainsboro;">&nbsp;</td>
        </tr>        
	<?php endforeach;?>
    </table>
<?php elseif($rows && $this->input->get("view")=='v'):?>
		<table class="grid-table" cellpadding="0" cellspacing="0" width="100%">
        	<tr class="header">
            <td>Name</td>
            <td>Label</td>
            <td>Study</td>
            <td>Link to question</td>
        </tr>	
	<?php $tr_class="";?>
	<?php foreach($rows as $row):?>
  		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
    	<tr  class="<?php echo $tr_class; ?>" valign="top">
            <td><?php echo $row['name'];?></td>
            <td><?php echo ($row['labl']!=='') ? utf8_decode($row['labl']) : $row['name']; ?></td>
            <td><?php echo utf8_decode($row['nation']). ' - '.utf8_decode($row['title']); ?></td>
            <td><a href="<?php echo site_url().'/ddibrowser/'.$row['sid'].'/variable/'.$row['vid'];?>"><?php echo site_url().'/ddibrowser/'.$row['sid'].'/variable/'.$row['vid'];?></a></td>
        </tr>
    <?php endforeach;?>
	</table>

<?php else:?>
	No records were found
<?php endif;?>
