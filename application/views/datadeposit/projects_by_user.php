<style type="text/css">
.yui-t2 #yui-main .yui-b {
	margin-left: 0 !important;
}
.datadeposit-start-project{margin:50px;padding:15px;text-align:center;background:#F8F8F8;}
.project-status{width:80px;height:80px;padding:10px;background:gainsboro;text-align:center;}
.project-status .glyphicon{display:block;padding:10px;font-size:25px;}
.project-row{min-height:100px;}
.project-row .status-draft{background:orange;}
.project-row .status-submitted{background:deepskyblue;}
.project-row .status-accepted{background:limegreen;}
.project-row .status-processed{background:royalblue}
.project-row .status-closed{background-color:darkgreen}
    .project-row .project-status{color:white; text-transform: uppercase}
.text-right{text-align:right;}
    .project-row .actions a {font-size:12px;color:gray;}
    /*.project-row .actions {display:none;}*/
    .project-row .title{margin-right:235px;}
    .dd-pager{
        border-top:1px solid gainsboro;
        border-bottom:1px solid #dcdcdc;padding:10px;
        margin-top:10px;
    }
    .my-deposits{border-top: 1px solid #dcdcdc; margin-top:10px;}
</style>

<?php $error=$this->session->flashdata('error');?>
<?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

<?php $message=$this->session->flashdata('message');?>
<?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>
<h1 class="page-title"><?php echo t('My Projects'); ?></h1>

<div class="text-right">
<a href="<?php echo site_url('/datadeposit/create'); ?>" class="btn btn-primary text-right">
  <?php echo t('new_project');?>
</a>
</div>

<?php if (isset($projects[0]->id)): ?>

<?php
//icons for project status codes
    $status_classes=array(
        'draft'=>'glyphicon-file',
        'submitted'=>'glyphicon-inbox',
        'accepted'=>'glyphicon-ok-sign',
        'processed'=>'glyphicon-refresh',
        'closed'=>'glyphicon-saved'
    );
?>

<div class="data-deposit my-deposits">

    <?php foreach($projects as $project):?>
    <div class="project-row">
        <div class="project-status status-<?php echo $project->status;?>">
            <span class="glyphicon <?php echo $status_classes[$project->status];?>" aria-hidden="true"></span>
            <?php echo $project->status;?>
        </div>

        <div class="project-body">
            <div class="title"><?php echo anchor("datadeposit/study/".$project->id,$project->title);?></div>



            <div class="subtitle"><?php echo ($project->description=='') ? 'N/A':  substr($project->description,0,200);?></div>
            <div class="author">

                <span class="created-by">Created by: <?php echo $project->created_by;?></span>
                <!--<span class="date-created">Created on: <?php echo date("M d,Y",$project->created_on);?></span>-->
                <span class="date-modified">Last modified on: <?php echo date("M d,Y",$project->last_modified);?></span>

                <span class="actions">

                <?php if ($project->status=='draft'):?>
                <span>
                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/study/'.$project->id);?>">Edit</a>
                </span>
                <?php endif;?>

                <?php if ($project->status=='draft'):?>
                <span>
                    <span class="glyphicon glyphicon-print" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/summary'.$project->id);?>">Summary</a>
                </span>
                <?php endif;?>

                <span>
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/delete/'.$project->id);?>">Delete</a>
                </span>

                <?php if ($project->status!='draft'):?>
                <span>
                    <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/request_reopen/'.$project->id);?>">Reopen</a></span>
                </span>
                <?php endif;?>

            </div>
            <?php /*
            <div class="actions">
                <span>
                    <span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/study/'.$project->id);?>">Edit</a>
                </span>
                <span>
                    <span class="glyphicon glyphicon-print" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/summary'.$project->id);?>">Summary</a></span>
                <span>
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/delete/'.$project->id);?>">Delete</a></span>
                <span>
                    <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                    <a href="<?php echo site_url('datadeposit/request_reopen/'.$project->id);?>">Reopen</a></span>
            </div>
            */ ?>
        </div>
    </div>
    <?php endforeach;?>

</div>
<?php return;?>


<table class="grid-table" style="width:100%" cellspacing="0" cellpadding="0">

		<tr valign="top" align="left" style="height:5px" class="header">


						<th style="text-align:left;;" >

				                <a href="<?php echo site_url('datadeposit/projects'); ?>?sort_by=title&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>"><?php echo t('title'); ?></a>			</th>


						<th style="text-align:left">

				                <a href="<?php echo site_url('datadeposit/projects'); ?>?sort_by=created_by&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>"><?php echo t('created_by'); ?></a>			</th>


						<th style="text-align:left">

				                <a href="<?php echo site_url('datadeposit/projects'); ?>?sort_by=status&sort_order=<?php if (isset($_GET['sort_order'])) echo ($_GET['sort_order']=='asc') ? 'desc' : 'asc';?>">Status</a>			</th>

			            <th style="text-align:left">Actions</th>

		</tr>

		<tbody>
			<?php foreach($projects as $project): 
				if (!isset($project->id)) {
					continue;
				}
			?>
			<tr id="<?php echo $project->id; ?>">
				<?php foreach($fields as $field_name => $field_display): ?>
                <?php if ($field_name == 'created_on') {
					$change = &$project->$field_name;
					$change = explode(' ', $change);
					$change = $change[0]; 
				}?>
				<?php if(strcmp($field_name,'title')===0):?>
					<?php if($project->status != 'Pending'):?>
                    <td>
                    <a href="<?php echo site_url('datadeposit');?>/study/<?php echo $project->id;?>"><?php echo isset($_POST['title'])? $_POST['title']:$project->title; ?></a>
                    </td>
                    <?php else: ?>
                    <td><?php echo $project->title; ?></td>
                    <?php endif; ?>
                <?php elseif(strcmp($field_name,'access')===0):?>
                	 <td><?php echo str_replace(",","<br/>",$project->access); ?></td>
                <?php elseif(strcmp($field_name,'status')===0):?>
                	   <?php if($project->status != 'Draft'):?>
                       <td><?php echo isset($project->status)?$project->status:'&nbsp;';?></td>
                       <?php else: ?>
                       <td><?php echo isset($project->status)?$project->status:'&nbsp;';?></td>
                       <?php endif; ?>
                <?php else: ?>
				<td>
					<?php echo ($project->$field_name != '')?$project->$field_name:'N/A'; ?>
				</td>
                <?php endif;?>
				<?php endforeach; ?>
                
                <?php if($project->status != 'Pending'):?>
                    <td nowrap="nowrap">
                    
                    <?php if ($project->status=='draft'):?>
                        <a href="<?php echo site_url('datadeposit');?>/update/<?php echo $project->id;?>"><?php echo t('edit');?></a> 
                    <?php else:?>
                        <span class="action-disabled"><?php echo t('edit');?></span>
                    <?php endif;?>
                    |
                    <a href="<?php echo site_url('datadeposit');?>/summary/<?php echo $project->id;?>"><?php echo t('summary');?></a> 
                    |
                    <?php if($project->access == 'owner' && $project->status == 'draft'): ?>
                    	<a href="<?php echo site_url('datadeposit');?>/confirm/<?php echo $project->id;?>" id="<?php echo $project->id;?>" class="delete">Delete</a>
                    <?php else:?>
                    	<span class="actiona-disabled"><?php echo t('delete');?></span>
					<?php endif; ?>
                    
                    <?php if($project->status !=='draft'):?>
                    	| <a href="<?php echo site_url('datadeposit/request_reopen/'.$project->id);?>" id="<?php echo $project->id;?>" class="reopen">Reopen</a>
                    <?php endif;?>
                    
                    </td>
                <?php else: ?>
                <td nowrap="nowrap">
                <?php echo t('edit');?> | 
            	<?php echo t('delete');?>
            	</td>
                <?php endif; ?>
			</tr>
            <!-- This is when user is new , with no projects in his list -->
            <?php if(sizeof($projects)===0):?>
            <tr><td></td><td></td><td></td><td></td></tr>
            <?php endif;?>
			<?php endforeach; ?>			
		</tbody>
</table>
<?php else:?>
<div class="datadeposit-start-project">
To start depositing data, click here to <a href="<?php echo site_url('datadeposit/create');?>">start a new project</a>.
</div>
<?php endif; ?>
