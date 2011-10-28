<style>
.stats-surveys, .stats-variables, .stats-citations{font-weight:bold;color:#999999;font-size:18px;}
.block-stats{text-align:center;line-height:200%;}
.stats-date{color:gray;font-weight:bold;}
.block-stats-button a{
	color:#e5eff9;
	margin:30px;
	margin-bottom:0px;
	margin-top:10px;
	background-color:#306caa;
	color:#e5eff9;
	font-size:18px;
	padding:5px;
	text-align:center;
	display:block;
	text-decoration:none;
	-moz-border-radius: 10px;
	-webkit-border-radius: 10px
	}
.block-stats-button a:hover{background:black;text-decoration:none;}
</style>
<div class="block-stats">
    <div class="stats-text">As of <span class="stats-date"><?php echo date("F d, Y",date("U")); ?></span> our catalog contains</div>
    <div class="stats-surveys" ><?php echo number_format($survey_count);?> surveys</div>
    <?php if ($this->config->item("hide_citations")!=='yes'  &&  $citation_count>0):?>
    <div class="stats-citations"><?php echo number_format($citation_count);?> citations</div>
    <?php endif;?>
    <div class="stats-variables"><?php echo number_format($variable_count);?> variables</div>
</div>
<div class="block-stats-button" ><a  href="index.php/catalog">Visit Catalog</a></div>