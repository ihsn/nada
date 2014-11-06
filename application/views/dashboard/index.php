<style>
	.text-shadow{
		text-shadow: 0 1px 3px rgba(0, 0, 0, .4), 0 0 30px rgba(0, 0, 0, .075);
	}
	.box-shadow{
		-webkit-box-shadow: inset 0 3px 7px rgba(0, 0, 0, .2), inset 0 -3px 7px rgba(0, 0, 0, .2);
		-moz-box-shadow: inset 0 3px 7px rgba(0,0,0,.2), inset 0 -3px 7px rgba(0,0,0,.2);
		box-shadow: inset 0 3px 7px rgba(0, 0, 0, .2), inset 0 -3px 7px rgba(0, 0, 0, .2);
	}
	.dashboard-box{border:1px solid gainsboro; -moz-border-radius: 5px;	-webkit-border-radius: 5px; color:#333333;margin-bottom:5px;}
	.dashboard-box-title{
		text-transform: uppercase;
		padding: 5px;
		background: #F1F1F1;
		color: black;
		font-weight: normal;
		padding: 5px;
		font-size: 14px;
		background: #F1F1F1;
		background-image: -webkit-gradient(linear,left bottom,left top,from(#ECECEC),to(#F9F9F9));
		background-image: -webkit-linear-gradient(bottom,#ECECEC,#F9F9F9);
		background-image: -moz-linear-gradient(bottom,#ECECEC,#F9F9F9);
		background-image: -o-linear-gradient(bottom,#ECECEC,#F9F9F9);
		background-image: linear-gradient(to top,#ECECEC,#F9F9F9);
		border-bottom: 1px solid #DFDFDF;
		-webkit-box-shadow: 0 1px 0 white;
		box-shadow: 0 1px 0 white;}
	.dashboard-box-body{padding:5px;}
	.dashboard-box-spacer{height:10px;}
	.dashboard-box a{color:#000066;text-decoration:none; font-weight:normal;}
	.dashboard-box a:hover{color:maroon;}
	.users .user{font-style:italic;}
	.collection {margin-bottom:15px;}
	.collection .body-row{clear:both;overflow:auto;padding:5px;}
	.collection .left{float:left;width:45%;}
	.collection .right{float:right;width:45%;margin-right:10px;text-align:right;}
	.collection .footer{background-color:#F4F4F4;padding:5px 10px;}
	.collection .dashboard-box-body{padding:0px;}
	.collection .main{border-right:1px solid gainsboro;}
	.dashboard-box .actions{padding:5px;}
	.dashboard-box .strong{font-weight:bold;}
	.warning{color:orange;line-height:150%;}
	.content-container{margin-top:15px;}
	.dashboard-box .info{color:green;font-size:large;}
	.warning-box{text-align:left;}
	.important{color:maroon;}
	.dashboard-box-footer{padding:0px;font-size:12px;background:#F1F1F1;}
	.dashboard-box-footer .actions .btn a{color:white;}
	.collection-central .btn-edit,
	.collection-central .btn-admin{display:none;}
</style>
<div class="content-container">

<div class="yui-g row-fluid">
	
    <?php if (isset($news)):?>
    <div class="yui-u first span8" style="border-right:1px solid gainsboro;">		
        	<div class="dashboard-box">
            	<div class="dashboard-box-title"><?php echo t('nada_news_updates');?></div>
                <div class="dashboard-box-body"><?php echo $news;?></div>
                <div class="dashboard-box-footer"></div>
            </div>        	        
	</div>
    <?php endif;?>
    
    
    <?php if (isset($collections)):?>
    
    <div class="yui-u first span6" style="height:400px;">		
    <?php foreach($collections as $collection):?>
        	<div class="dashboard-box collection collection-<?php echo $collection['repositoryid'];?>">
            	<div class="dashboard-box-title text-shadow">
					<?php if($collection['repositoryid']!=='central'):?>
					<?php echo t('Collection');?>: 
					<?php endif;?>
					<?php echo $collection['title'];?>
                </div>
                <div class="dashboard-box-body">
					<div class="body-row">
                    <div class="left main">
                    	<?php if($collection['repositoryid']=='central'):?>
	                        <div class="strong"><?php echo sprintf(t('catalog_contains_n_studies'),$collection['stats']['owned']+$collection['stats']['linked']);?></div>
                        <?php else:?>
                        	<div class="strong"><?php echo sprintf(t('collection_contains_n_studies'),$collection['stats']['owned']+$collection['stats']['linked']);?></div>
                        <?php endif;?>
                        <div class="left">
                        	<div><?php echo t('owned');?>: <span class="strong"><?php echo $collection['stats']['owned'];?></span></div>
                        	<div><?php echo t('linked');?>: <span class="strong"><?php echo $collection['stats']['linked'];?></span></div>
                        </div>    
                        <div class="right">    
                        <div><?php echo t('published');?>: <span class="strong"><?php echo $collection['stats']['published'];?></span></div>
                        <div><?php echo t('unpublished');?>: <span class="strong"><?php echo $collection['stats']['unpublished'];?></span></div>
                        </div>
                    </div>
                    <div class="right">
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
                <div class="dashboard-box-footer">
                	<div class="actions">
                	<span class="btn btn-mini btn-success"><a href="<?php echo site_url('admin/repositories/active/'.$collection['id'].'?destination=admin/catalog');?>"><?php echo t('Maintenance');?></a></span> 
                	<span class="btn btn-mini btn-success btn-admin"><?php echo anchor('admin/repositories/permissions/'.$collection['id'], t('Administrators'));?></span>
                    <span class="btn btn-mini btn-success"><?php echo anchor('admin/repositories/history/'.$collection['repositoryid'], t('History'));?></span>
                    <span class="btn btn-mini btn-success btn-edit"><?php echo anchor('admin/repositories/edit/'.$collection['id'], t('Edit'));?></span>
                    </div>
                </div>
            </div>        	        
	<?php endforeach;?>
    </div>    
    
    <?php endif;?>
    
    <div class="yui-u span6">
    
    			<?php /*if ($failed_email_count>0):?>
               	<div class="alert alert-error">
                	Check email settings - <?php echo $failed_email_count;?> errors were logged. <a href="<?php echo site_url('admin/logs?keywords=email-failed&field=logtype');?>">View details</a></b>
               	</div>
                <?php endif;*/?>
    
                <div class="dashboard-box">
                    <div class="dashboard-box-title"><?php echo t('users');?></div>
                    <div class="dashboard-box-body">
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
                <div class="dashboard-box">
                    <div class="dashboard-box-title"><?php echo t('cache_files');?></div>
                    <div class="dashboard-box-body">
                    	<?php if (isset($cache_files)):?>
                        <?php if ($cache_files>0):?>
                        	<?php echo sprintf (t("clear_cache_files"),$cache_files,site_url('admin/clear_cache'));?>
                            <?php else:?>
                            <p><?php echo t('no_cache_files_found');?></p>
                        <?php endif;?>
                        <?php endif;?>
                    </div>											
                </div>           

	</div>
    
    <?php if (isset($recent_studies)):?>
    <div class="yui-u first span6" >		
        	<div class="dashboard-box">
            	<div class="dashboard-box-title"><?php echo t('recent_studies');?></div>
                <div class="dashboard-box-body">
	                <?php $tr_class=""; ?>
                	<table style="width:100%;" class="grid-table">
					<?php foreach($recent_studies as $row):?>
                    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                    	<tr class="<?php echo $tr_class;?>">
						<td><?php echo strtoupper($row['repositoryid']);?></td>
                        <td><?php echo anchor('admin/catalog/edit/'.$row['id'],$row['titl']);?></td>
                         <td><?php echo relative_date($row['changed']); ?></td>
                        </tr>
                    <?php endforeach;?>
                    </table>
                </div>
                <div class="dashboard-box-footer"></div>
            </div>        	        
	</div>
    <?php endif;?>
</div>