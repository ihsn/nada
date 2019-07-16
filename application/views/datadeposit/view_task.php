<style>
    .table-form td{padding:5px;vertical-align: top;}
    .label-0{background-color:orange}
    .label-1{background-color:#00CC00}
</style>

<?php
$task_codes=array(
    '0'=>'Work in progress',
    '1'=>'Completed'
);

?>

<div class="content-container container-fluid">

    <?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

    <h1 class="page-title"><?php echo t('Task info'); ?></h1>

    <?php echo form_open_multipart('', array('class'=>'form') ); ?>


    <table class="table-form table table-striped">

        <tr>
            <td><label>Project:</label></td>
            <td>
                <a href="<?php echo site_url('admin/datadeposit/id/'.$project->id);?>"><?php echo $project->title;?></a>
            </td>
        </tr>

        <tr>
            <td><label>Status:</label></td>
            <td>
                <span class="label label-<?php echo $task['status'];?>"><?php echo $task_codes[$task['status']];?></span>
            </td>
        </tr>

        <tr>
            <td><label>Assigned to:</label></td>
            <td><?php echo $task['task_user'];?></td>
        </tr>

        <tr>
            <td><label>Assigned by:</label></td>
            <td><?php echo $task['assigner'];?></td>
        </tr>


        <tr>
            <td><label>Date assigned:</label></td>
            <td><?php echo date("M-d-Y",$task['date_assigned']);?></td>
        </tr>

        <tr>
            <td><label>Date completed:</label></td>
            <td><?php if($task['date_completed']):?>
                <?php echo date("M-d-Y",$task['date_completed']);?>
                    <?php else:?>
                    -
                <?php endif;?>
            </td>
        </tr>


        <tr>
            <td><label>Update status</label></td>
            <td>

                <?php if ($task['status']==0):?>
                <!--complete task-->
                <a href="<?php echo site_url('admin/datadeposit/tasks/update/'.$task['id'].'/1');?>" type="button" class="btn btn-success">Resolve</a>
                <?php else:?>
                    <!--re-open task-->
                    <a href="<?php echo site_url('admin/datadeposit/tasks/update/'.$task['id'].'/0');?>" type="button" class="btn btn-warning">Re-open</a>
                <?php endif;?>

                <!--cancel (un-assign) task -->
                <a href="<?php echo site_url('admin/datadeposit/tasks/delete/'.$task['id']);?>" type="button" class="btn btn-default">Delete</a>

            </td>
        </tr>

    </table>



    <?php echo form_close();?>
</div>
