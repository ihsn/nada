<div class="col-sm-12">

    <!--<div class="col">-->
    <div class="row">

        <div class="col-sm-12">
            <h1><?php echo t('profile');?></h1>
        </div>
    </div>

    <div class="row wb-tab-heading mt-lg-3 mb-5">
        <!-- tab-heading -->

        <div class="col-12 col-sm-12">

            <div class="row">

                <div class="col-12 col-sm-12">
                    <table class="table table-sm wb-table-space">
                        <tbody>
                        <tr>
                            <td><?php echo $user->first_name. ' ' . $user->last_name; ?></td>
                            <td align="right"><?php echo anchor('auth/edit_profile',t('edit'));?></td>
                        </tr>
                        <tr>
                            <td><?php echo t('name');?></td>
                            <td><?php echo $user->first_name. ' ' . $user->last_name; ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('email');?></td>
                            <td><?php echo $user->email; ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('company');?></td>
                            <td><?php echo $user->company; ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('phone');?></td>
                            <td><?php echo $user->phone; ?></td>
                        </tr>

                        <tr>
                            <td><?php echo t('country');?></td>
                            <td><?php echo $user->country; ?></td>
                        </tr>

                        </tbody>
                    </table>
                </div>

            </div> <!-- /row  -->

        </div>
    </div>


    <div  class="row">
        <div class="col-md-8">
            <h3><?php echo t('api_keys');?></h3>
        </div>
        <div class="col-md-4">
            <a href="<?php echo site_url('auth/generate_api_key');?>" class="btn btn-primary btn-sm float-right"><?php echo t('generate_api_key');?></a>
        </div>            
    </div>

    <div class="row wb-tab-heading mt-lg-3 mb-5">
        <?php if (is_array($api_keys) && count($api_keys)>0):?>
        <table class="table table-striped">
        <?php foreach($api_keys as $api_key):?>
            <tr>
                <td><?php echo $api_key;?></td>
                <td><a href="<?php echo site_url('auth/delete_api_key').'?api_key='.urlencode($api_key);?>"><?php echo t('delete');?></a></td>
            </tr>
        <?php endforeach;?>
        </table>
        <?php else:?>
            <div style="padding:15px;" ><?php echo t('no_api_keys_found');?></div>
        <?php endif;?>
    </div>

    <?php $this->load->view('access_licensed/request_list',array('data'=>$lic_requests));?>
</div>

