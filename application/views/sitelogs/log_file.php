<style>
	.line{border-bottom:1px solid gainsboro;clear:both;overflow:auto;padding:5px;}
	.line .num{font-size:11px;display:block;float:left;width:50px; color:gray;}
	.line .log{display:block;float:left;}
	.error {color:red;}
</style>
<?php $handle = @fopen($log_file, "r"); ?>
<?php if ($handle):?>
<?php $k=1;?>
	<?php while (!feof($handle)):?>
    <?php 
		$line=fgets($handle, 4096);
		$line_arr=explode(" - ",$line);
		$class="";
		
		if (count($line_arr)>0 && trim($line_arr[0])=='ERROR')
		{
			$class="error";
		}
	?>
    	<div class="line <?php echo $class;?>">
        	<span class="num"><?php echo $k;?></span>
            <span class="log"><?php echo $line;?></span>
        </div>   
           <?php $k++;?>
    <?php endwhile;?>
   <?php fclose($handle); ?>
<?php endif;?>
?>