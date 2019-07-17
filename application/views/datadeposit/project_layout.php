<style>

</style>
<?php
/*
Define the layout for the DD front end edit pages
*/

$tabs=array(
	'datafiles'				=> 'Data files',
	'update'				=> 'Project information',
	'study'					=> 'Study description',
	'citations'				=> 'Citations',
	'add_citations'         => 'Citations > Add new',
	'edit_citations'        => 'Citations > Edit',
	'submit_review'         => 'Review and submit'
);

?>
<div class="dd-content-container container-fluid">
<div class="project-title"><h1><?php echo isset($this->active_project[0]->title) ? $this->active_project[0]->title : 'Create new project' ; ?></h1></div>
    <div class="row">

        <div class="yui3-u-5-24" style="border:0px solid blue;">
            <div class="col-md-3">
                <?php echo $pending_tasks;?>
                <?php echo $this->load->view('datadeposit/user_projects',null,true);?>
            </div>

        </div>

        <div class="col-md-9" style="border:0px solid red;">
            <div class="dd-content">
                <?php $this->load->view('datadeposit/project_progress_bar',array('pending_tasks_arr'=>$pending_tasks_arr));?>

                <div class="dd-header">
                    <?php if (array_key_exists($this->uri->segment(2),$tabs)):?>
                        <?php echo $tabs[$this->uri->segment(2)];?>
                    <?php else:?>
                        <?php echo $this->uri->segment(2);?>
                    <?php endif;?>
                </div>
                <div class="dd-body">

                    <?php echo $content;?></div>
            </div>

        </div>

    </div>


    


</div>
