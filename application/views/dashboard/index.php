<div class="container-fluid page-dashboard">

<div class="yui-g row">

    <?php if (isset($news)):?>
    <div class="yui-u first col-md-8">
        	<div class="panel panel-default">
            	<div class="panel-heading"><?php echo t('nada_news_updates');?></div>
                <div class="panel-body"><?php echo $news;?></div>
                <div class="panel-footer"></div>
            </div>
	</div>
    <?php endif;?>


    <?php if (isset($collections)):?>

    <div class="yui-u first col-md-6">
    <?php foreach($collections as $collection):?>
        	<div class="panel panel-default <?php echo $collection['repositoryid'];?>">
            	<div class="panel-heading">
					<?php if($collection['repositoryid']!=='central'):?>
					<?php echo t('Collection');?>:
					<?php endif;?>
					<?php echo $collection['title'];?>
                </div>
                <div class="panel-body">
					<div class="body-row">
                    <div class="col-md-6 main rt-border">
                    	<?php if($collection['repositoryid']=='central'):?>
	                        <div><strong><?php echo sprintf(t('catalog_contains_n_studies'),$collection['stats']['owned']+$collection['stats']['linked']);?></strong></div>
                        <?php else:?>
                        	<div><strong><?php echo sprintf(t('collection_contains_n_studies'),$collection['stats']['owned']+$collection['stats']['linked']);?></strong></div>
                        <?php endif;?>
                        <div class="pull-left">
                        	<div><?php echo t('owned');?>: <strong><?php echo $collection['stats']['owned'];?></strong></div>
                        	<div><?php echo t('linked');?>: <strong><?php echo $collection['stats']['linked'];?></strong></div>
                        </div>
                        <div class="pull-right">
                        <div><?php echo t('published');?>: <strong><?php echo $collection['stats']['published'];?></strong></div>
                        <div><?php echo t('unpublished');?>: <strong><?php echo $collection['stats']['unpublished'];?></strong></div>
                        </div>
                    </div>
                    <div class=" col-md-6">
                    	<div class="warning-box">
                    	<?php
							$total_owned=(int)$collection['stats']['owned'];
							$total_puf=(int)$collection['stats']['total_puf'];
							$no_microdata=$total_puf -(int)$collection['stats']['microdata'];
							$no_questionnaires=$total_owned - (int)$collection['stats']['questionnaires'];
						?>
                        <?php if($no_microdata>0):?>
	                    	<div class="warning"><span class="badge badge-warning"><?php echo $no_microdata;?></span> <?php echo t('studies_with_no_data_files');?></div>
                        <?php endif;?>
						<?php if($no_questionnaires>0):?>
                        	<div class="warning"><span class="badge badge-warning"><?php echo $no_questionnaires;?></span> <?php echo t('studies_with_no_questionnaires');?></div>
                        <?php endif;?>

                       <?php if( (int)$collection['stats']['lic_requests'] > 0 ):?>
                       	<div class="important">
                        	<a href="<?php echo site_url('admin/licensed_requests?collection='.$collection['repositoryid'].'&status=PENDING');?>">
                            <span class="badge badge-important"><?php echo (int)$collection['stats']['lic_requests']; ?></span> <?php echo t('pending requests');?>
                            </a>
                        </div>
                       <?php endif;?>
                       </div>
                    </div>
                    </div>

                </div>
                <div class="panel-footer">
                	<div class="actions">
                	<a class="btn btn-primary btn-sm btn-nada" href="<?php echo site_url('admin/repositories/active/'.$collection['id'].'?destination=admin/catalog');?>"><?php echo t('Maintenance');?></a>
                	<a class="btn btn-sm btn-primary btn-admin btn-nada manage-permissions" href="<?php echo site_url('admin/repositories/permissions/'.$collection['id']);?>"><?php echo t('Administrators');?></a>
                    <a class="btn btn-sm btn-primary btn-nada" href="<?php echo site_url('admin/repositories/history/'.$collection['repositoryid'])?>"><?php echo t('History');?></a>
                    <a class="btn btn-sm btn-primary btn-edit btn-nada manage-collection" href="<?php echo site_url('admin/repositories/edit/'.$collection['id']);?>"><?php echo t('Edit');?></a>
                    </div>
                </div>
            </div>
	<?php endforeach;?>
    </div>

    <?php endif;?>

    <div class="yui-u col-md-6">

    			<?php /*if ($failed_email_count>0):?>
               	<div class="alert alert-error">
                	Check email settings - <?php echo $failed_email_count;?> errors were logged. <a href="<?php echo site_url('admin/logs?keywords=email-failed&field=logtype');?>">View details</a></b>
               	</div>
                <?php endif;*/?>

                <div class="panel panel-default">
                    <div class="panel-heading"><?php echo t('users');?></div>
                    <div class="panel-body">
                    <?php if (isset($user_stats)):?>
                    	<div>
                    	<div><?php echo $user_stats['active']; ?> <?php echo t('user_active');?> </div>
                        <div><?php echo $user_stats['disabled']; ?> <?php echo t('user_disabled');?> </div>
                        <div><?php echo $user_stats['inactive']; ?> <?php echo t('user_inactive');?></div>
                        <div><?php echo $user_stats['anonymous_users']; ?> <?php echo t('anonymous_users');?></div>
                        </div>
                        <div class="users">
	                        	<?php echo count($user_stats['loggedin_users']);?> <?php echo t('logged_in_users');?>:
                            	<span class="user"><?php echo implode(', ',$user_stats['loggedin_users'])?></span>
                        </div>
                    <?php endif;?>
                    </div>
                </div>
                <div class="dashboard-box-spacer"></div>
                <div class="panel panel-default">
                    <div class="panel-heading"><?php echo t('cache_files');?></div>
                    <div class="panel-body">
                    	<?php if (isset($cache_files)):?>
                        <?php if ($cache_files>0):?>
                        	<?php echo sprintf (t("clear_cache_files"),$cache_files,site_url('admin/clear_cache'));?>
                            <?php else:?>
                            <p><?php echo t('no_cache_files_found');?></p>
                        <?php endif;?>
                        <?php endif;?>
                    </div>
                </div>


                <?php if (isset($recent_studies)):?>
                <div class="panel panel-default">
                    <div class="panel-heading"><?php echo t('recent_studies');?></div>
                    <div class="panel-body">
                        <?php $tr_class=""; ?>
                        <table class="table table-striped">
                        <?php foreach($recent_studies as $row):?>
                        <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                            <tr class="<?php echo $tr_class;?>">
                            <td width="10%"><?php echo strtoupper($row['repositoryid']);?></td>
                            <td width="65%"><?php echo anchor('admin/catalog/edit/'.$row['id'],$row['title']);?></td>
                            <td width="25%"><?php echo relative_date($row['changed']); ?></td>
                            </tr>
                        <?php endforeach;?>
                        </table>
                    </div>
                </div>	
                <?php endif;?>
	</div>
</div>
