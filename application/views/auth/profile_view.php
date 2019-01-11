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
    <?php $this->load->view('access_licensed/request_list',array('data'=>$lic_requests));?>
</div>

