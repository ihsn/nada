<?php

// from hanzatzu
function ago($time, $rcs = 0)
{
    $currentTime = time();
    $difference  = $currentTime - $time;
    $parse       = array('second','minute','hour','day','week','month','year','decade');
    $length      = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);

    for ($value = sizeof($length)-1; ($value >= 0)
        && (($number = $difference / $length[$value]) <= 1);
            $value--);

    $value         = ($value < 0) ? 0 : $value;
    $time2         = $currentTime - ($difference % $length[$value]);
    $number        = floor($number);
    $parse[$value] = ($number != 1) ? $parse[$value] . 's' : $parse[$value];
    $formatted     = $number.' '.$parse[$value];

    if (($rcs == 1) && ($value >= 1)
       && (($currentTime-$time2) > 0)) {
         $formatted .= formatTime($time2);
    }

    return $formatted;
}
?>

<style>
    .dashboard-box-body li { padding: 4px 8px; }
    .dashboard-box{border:1px solid gainsboro; -moz-border-radius: 5px; -webkit-border-radius: 5px; color:#333333}
    .dashboard-box-title{font-size:16px; text-transform:uppercase;padding:5px;background:gainsboro}
    .dashboard-box-body{padding:5px;}
    .dashboard-box-footer{padding:5px;font-size:12px;}
    .dashboard-box-spacer{height:10px;}
    .dashboard-box a{color:#000066;text-decoration:none; font-weight:normal;}
    .dashboard-box a:hover{color:maroon;}
</style>
<div class="content-container">
<h1><?php echo t('dashboard');?></h1>
<div  class="yui-gc">
    <div class="yui-u first" >      
            <div class="dashboard-box">
                <div class="dashboard-box-title"><?php echo t('submitted_projects');?></div>

                <div class="dashboard-box-body">
  				  <?php if (!empty($submitted)): ?>
                    <?php $tr_class=""; ?>
                    <table style="width:100%;" class="grid-table">
                    <?php foreach($submitted as $row):?>
                    <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                        <tr class="<?php echo $tr_class;?>">
                        <td><?php echo anchor(site_url('admin/datadeposit/id/'.+$row->id), $row->title)?></td>
                        <td style="width:296px"><?php echo ago($row->submitted_on), ' ago'; ?></td>
                        </tr>
                    <?php endforeach;?>
                    </table>                
   				 <?php else: ?>
                 <?php echo t('no_pending_submitted'); ?>
                 <?php endif; ?>
               </div>
            </div>
   
        <div class="dashboard-box">
        <div class="dashboard-box-title"><?php echo t('requests_to_reopen');?></div>
        <div class="dashboard-box-body">
        <?php if (!empty($requested)): ?>
            <?php $tr_class=""; ?>
            <table style="width:100%;" class="grid-table">
            <?php foreach($requested as $row):?>
            <?php if($tr_class=="") {$tr_class="alternate";} else{ $tr_class=""; } ?>
                <tr class="<?php echo $tr_class;?>">
                <td><?php echo anchor(site_url('admin/datadeposit/id/'.+$row->id), $row->title)?></td>
                <td style="width:296px"><?php echo ago($row->requested_when), ' ago'; ?></td>
                </tr>
            <?php endforeach;?>
            </table>    
	     <?php else:  ?>
         <?php echo t('no_requesting_reopen'); ?>
         <?php endif; ?>
        </div>
    </div>

   </div>


    <div class="yui-u">
                <div class="dashboard-box">
                    <div class="dashboard-box-title"><?php echo t('project_options');?></div>
                    <div class="dashboard-box-body">
                    <ul style="list-style-type:none">
                    
                    <li><a href="<?php echo site_url('admin/datadeposit/projects'), '?filter=all'; ?>"><?php echo t("manage_all"); ?></a></li>
                    <li><a href="<?php echo site_url('admin/datadeposit/projects'), '?filter=submitted'; ?>"><?php echo t("view_submitted"); ?></a></li>
                    <li><a href="<?php echo site_url('admin/datadeposit/projects'), '?filter=accepted'; ?>"><?php echo t("view_accepted"); ?></a></li>
                    <li><a href="<?php echo site_url('admin/datadeposit/projects'), '?filter=processed'; ?>"><?php echo t("view_processed"); ?></a></li>
                    <li><a href="<?php echo site_url('admin/datadeposit/projects'), '?filter=requested'; ?>"><?php echo t("view_requested_reopen"); ?></a></li>
                    <li><a href="<?php echo site_url('admin/datadeposit/projects'), '?filter=draft'; ?>"><?php echo t("view_draft"); ?></a></li>
     
                    </ul>
                        
                    </div>
                </div>
                <div class="dashboard-box-spacer"></div>
                <div class="dashboard-box">
                    <div class="dashboard-box-title"><?php echo t('stats');?></div>
                    <div class="dashboard-box-body">
                    <ul style="list-style-type:none">
                    <li><?php echo sprintf(t('awaiting_review'), $stats['submitted']); ?></li>
                    <li><?php echo sprintf(t('awaiting_reopen'), $stats['requested']); ?></li>
                    <li><?php echo sprintf(t('count_processed'), $stats['processed']); ?></li>
                    <li><?php echo sprintf(t('count_draft'), $stats['draft']); ?></li>
     
                    </ul>                    </div>
                </div>           
      


    </div>
</div>