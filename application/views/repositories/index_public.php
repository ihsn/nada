<style>
.repo-table td{padding-top:10px;padding-bottom:10px;border-bottom:1px solid gainsboro;}
.thumb{padding-right:10px;padding-bottom:5px;}
.page-title{border-bottom:1px solid gainsboro;}
</style>
<div class="body-container" style="padding:10px;">

<h1><a href="<?php echo site_url();?>/catalog">Central Microdata Catalog</a></h1>
<p>The Central Microdata Catalog is a portal for all datasets held in catalogs maintained by the World Bank and a number of contributing external repositories. As of August 02, 2011, our central catalog contains 651 surveys. Users who wish to go directly to a specific catalog can visit the specific contributing repository through the links below.</p>

<h1 class="page-title"><?php echo t('contributing_repositories');?></h1>
<?php if ($rows):?>
    <table class="repo-table" width="100%" cellspacing="0" cellpadding="4">
	<?php $tr_class=""; ?>
	<?php foreach($rows as $row): ?>
    	<?php $row=(object)$row;?>
		<?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
        <?php if (!$row->ispublished){continue;} //skip unpublished?>
    	<tr class="<?php echo $tr_class; ?>" valign="top">
            <td class="thumb"><img src="<?php echo base_url();?>/<?php echo $row->thumbnail; ?>"/></td>
            <td>
			<h3><a href="<?php echo site_url();?>/catalog/<?php echo $row->repositoryid;?>"><?php echo $row->title; ?></a></h3>
			<?php echo $row->short_text; ?>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
<?php else: ?>
<?php echo t('no_records_found'); ?>
<?php endif; ?>
</div>