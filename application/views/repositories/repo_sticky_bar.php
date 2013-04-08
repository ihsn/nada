<style>
.collection-content-container{margin-left:20px;padding-top:10px;}
.collection-content-container .thumb{float:left;width:80px;}
.collection-content-container .thumb img{box-shadow:1px 1px 1px #888;border:1px solid #CCCCCC;padding:2px;}
.collection-content-container .collection-body{margin-top:6px;}
.collection-content-container .collection-body{float:left;}
.collection-content-container .collection-title{font-size: 24px;text-shadow: 0 1px 3px rgba(0, 0, 0, .4), 0 0 30px rgba(0, 0, 0, .075);color: #47475C;}
.collection-content-container .collection-id{font-weight:bold;color:#A5A1A1}
</style>
<div class="collection-content-container">
	<div class="thumb"><img src="<?php echo $thumbnail;?>" alt="Repository" width="60px"/></div>
    <div class="collection-body">
        <div class="collection-title"><?php echo $title;?></div>
        <div class="collection-id"><?php echo $repositoryid;?></div>
    </div>
</div>