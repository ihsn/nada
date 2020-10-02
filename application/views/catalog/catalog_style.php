<style>
.filter-box{margin:5px;margin-right:20px;}
.filter-box li{font-size:11px;}
.filter-box a{text-decoration:none;color:black;display:block;padding:3px;padding-left:15px;background:url('images/bullet_green.png') left top no-repeat;}
.filter-box a:hover{background:black;color:white;}
.filter-field{
border: 1px solid gainsboro;
-moz-border-radius: 5px;
-webkit-border-radius: 5px;
color: #333;
margin-bottom:10px;
}
.filter-title {
	font-size: 14px;
	text-transform: uppercase;
	padding: 5px;
	background: gainsboro;
}

span.link-change{font-size:10px;padding-left:5px;}
.unlink-study .linked{padding-left:20px;}
.survey-row .links{text-align:left;margin-right:10px;font-size:smaller;margin-top:10px;}
/*.survey-row h3{font-size:16px;margin-top:0px;margin-bottom:5px;width:80%;}*/
.filter{font-size:smaller;}
.result-count{color:gray;font-size:smaller}

/*box*/
.box{
	border:1px solid gainsboro;
	margin-right:5px;
	line-height:150%;
	margin-bottom:10px;
	-webkit-border-radius:3px;
	border-radius:3px;
}

.box label{
	font-weight:normal;
}

.box-header{
	font-weight:normal;
	padding:5px;
	font-size:14px;
	background: #F1F1F1;
	background-image: -webkit-gradient(linear,left bottom,left top,from(#ECECEC),to(#F9F9F9));
	background-image: -webkit-linear-gradient(bottom,#ECECEC,#F9F9F9);
	background-image: -moz-linear-gradient(bottom,#ECECEC,#F9F9F9);
	background-image: -o-linear-gradient(bottom,#ECECEC,#F9F9F9);
	background-image: linear-gradient(to top,#ECECEC,#F9F9F9);
	border-bottom: 1px solid #DFDFDF;
	text-shadow: white 0 1px 0;
	-webkit-box-shadow: 0 1px 0 white;
	box-shadow: 0 1px 0 white;
	position:relative;
	cursor:pointer;
}

.box-header .sh{
	position:absolute;
	right:3px;
	top:5px;
	background: url('images/blue-remove.png') no-repeat left top;
	display:block;
	width:16px;
	height:16px;
	cursor:pointer;
}
.filter-da tr{
	vertical-align:top;
}

.iscollapsed .sh{background: url('images/blue-add.png') no-repeat left top;}
.pad5{padding:5px;}
.pad10{padding:10px;}
.box-body .input-flex{width:85%;}
.box-body{padding:10px;}
.vscroll{overflow:auto;overflow-x:hidden;height:150px;}
.mini{width:75%;}
.btn-tiny{font-size:11px;}
.box .field{margin-bottom:10px;}
.sort_by{display:inline;}
.sort_by li {list-style:none;display:inline;margin-right:5px; border-left:1px solid gainsboro;padding-left:5px;}
.sort_by a{color:gray;font-size:11px;}
.sort_by li a.selected{color:black;font-weight:bold;}
.survey-options{float:right;}
.survey-options .label{font-weight:normal;}
.apply-filter:hover{cursor:pointer;}
.table-row{font-size:smaller;color:#333333;line-height:150%;clear:both;overflow:auto;}
.table-row .cell-label{display:block;width:100px;float:left;}
.table-row .cell-value{display:block;float:left}
.filter-info{display:inline;padding:5px;}
.filter-container{overflow:hidden;background-color:#eeeeee;padding:5px;margin-bottom:10px;display:block;font-weight:normal;}
.filter-container .filter{background:white;color:#666666;text-shadow:none;}
.data-access img{vertical-align:middle;}
.table-row .repo-owner{font-weight:bold;margin-right:5px;}
.table-row .repo-link{font-weight:normal;color:#666666;margin-right:5px;}
.survey-options .notice{font-weight:bold;font-size:8px;color:red;}


.survey-row{position:relative;padding-left:100px;}
.survey-row h3{margin-top:0px;margin-bottom:0px;font-size:18px;}
.survey-row .sub-title{margin-bottom:10px;font-size:14px;}
.survey-row .dataset-idno{color:gray;font-size:small;font-weight:normal;}
.survey-row .data-access-type{border:1px solid gray;color:gray;font-weight:normal;padding:3px; margin-left:10px;font-size:12px;}
.survey-row .subgroup{margin-right:15px;}
.survey-row .label{font-weight:normal;font-size:11px;text-transform: uppercase;}

.survey-row .data-access-icon{width:82px;height:82px;position:absolute;left:0px;border:0px solid gainsboro;}
.survey-row .data-access-public{background: url(themes/adminbt/data-access.gif) no-repeat 0% 18%;}
.survey-row .data-access-data_na{background: url(themes/adminbt/data-access.gif) no-repeat 0% 67%;}
.survey-row .data-access-licensed{background: url(themes/adminbt/data-access.gif) no-repeat 0% 34%;}
.survey-row .data-access-data_enclave{background: url(themes/adminbt/data-access.gif) no-repeat 0% 85%;}
.survey-row .data-access-remote{background: url(themes/adminbt/data-access.gif) no-repeat 0% 51%;}
.survey-row .data-access-direct{background: url(themes/adminbt/data-access.gif) no-repeat 0% 0%;}
.survey-row .data-access-open{background: url(themes/adminbt/data-access.gif) no-repeat 0% 100%;}

</style> 
