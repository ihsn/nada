<style>
	.dashboard-box{border:1px solid gainsboro; -moz-border-radius: 5px;	-webkit-border-radius: 5px; color:#333333}
	.dashboard-box-title{font-size:16px; text-transform:uppercase;padding:5px;background:gainsboro}
	.dashboard-box-body{padding:5px;}
	.dashboard-box-footer{padding:5px;font-size:12px;}
	.dashboard-box-spacer{height:10px;}
	.dashboard-box a{color:#000066;text-decoration:none; font-weight:normal;}
	.dashboard-box a:hover{color:maroon;}
	.users .user{font-style:italic;}
</style>
<div class="content-container">
<h1><?php echo t('dashboard');?></h1>
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
    
	<div class="yui-u span6">
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
                        	<?php echo sprintf (t("clear_cache_files"),$cache_files,site_url().'/admin/clear_cache/');?>
                            <?php else:?>
                            <p><?php echo t('no_cache_files_found');?></p>
                        <?php endif;?>
                        <?php endif;?>
                    </div>											
                </div>           

	</div>
</div>
