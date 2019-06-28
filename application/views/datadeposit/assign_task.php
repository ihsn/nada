<style>
    .task-member{border-bottom:1px solid gainsboro;padding:10px;width:300px;float:left;margin-right:10px;}
    .task-member table td {vertical-align: top}
    .task-member .input-radio{margin-top:15px;margin-right:15px;}
    .task-member .email{color:gray;font-weight:normal;}
    .task-member h4{font-size:15px;margin-bottom:0px;padding-bottom:5px;}
    .team{clear:both;overflow:auto;}
    .field-submit{clear:both;margin-top:20px;}
</style>

<div class="container-fluid content-container">

    <?php if (validation_errors() ) : ?>
        <div class="error">
            <?php echo validation_errors(); ?>
        </div>
    <?php endif; ?>

    <?php $error=$this->session->flashdata('error');?>
    <?php echo ($error!="") ? '<div class="error">'.$error.'</div>' : '';?>

    <?php $message=$this->session->flashdata('message');?>
    <?php echo ($message!="") ? '<div class="success">'.$message.'</div>' : '';?>

    <h1 class="page-title"><?php echo t('Assign task'); ?></h1>

    <?php echo form_open_multipart('', array('class'=>'form') ); ?>

    <?php /*
    <div class="field">
        <label for="ctype"><?php echo t('Assign to');?></label>
        <?php echo form_dropdown('user_id', $tasks_team, get_form_value("user_id",isset($user_id) ? $user_id : ''),'id="user_id"'); ?>
    </div>
    */ ?>

    <div class="team">
        <?php foreach($tasks_team as $user):$user=(object)$user;?>
            <div class="task-member">
                <table>
                    <tr>
                        <td><input class="input-radio" id="user_<?php echo $user->id;?>" name="user_id" type="radio" value="<?php echo $user->id;?>"></td>
                        <td>
                            <label for="user_<?php echo $user->id;?>">
                                <h4><?php echo $user->first_name;?> <?php echo $user->last_name;?></h4>
                                <span class="email"><?php echo $user->email;?></span>
                            </label>
                        </td>
                    </tr>
                    </table>
                </div>
        <?php endforeach;?>
    </div>


    <div class="field field-submit">
        <input type="submit" name="submit" id="submit" class="btn btn-primary" value="<?php echo t('submit'); ?>" />
        <?php echo anchor('admin/datadeposit/', t('cancel'));?>
    </div>
    <?php echo form_close();?>
</div>
