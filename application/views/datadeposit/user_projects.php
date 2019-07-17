<?php if($this->user_projects):?>
<div class="dd-sidebar-box user-projects" id="dd_user_projects">
    <div class="box-header">
        <h2><a class="my-projects" href="<?php echo site_url("datadeposit/projects");?>">My Projects</a></h2>
    </div>
    
    <div class="box-body">   	
  		<ul class="bullet">
            <?php foreach($this->user_projects as $project):?>
            	<li><a href="<?php echo site_url('datadeposit/study/'.$project->id);?>" title="<?php echo $project->title;?>"><?php echo substr($project->title,0,100);?></a>
                    <span class="label label-default">(<?php echo $project->status;?>)</span></li>
            <?php endforeach;?>
        </ul>  
   </div>
</div>
<?php endif;?>
