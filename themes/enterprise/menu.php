<?php 
	$page_=isset($_REQUEST['page']) ? $_REQUEST['page'] : 'home.html';

	foreach ($page_menu as $item){
		if ($item['published']=='yes'){//shows published pages only
			$css_class='menu-item';
			if ($item['page']==$page_){
				$css_class='menu-item-selected';
			}
			$target=$item['target'];
			if ($target!='same'){
				$target="target=_blank";
			}
			else{
				$target='';
			}
			printf('<div class="%s"><a href="?page=%s" %s>%s</a></div>'
					,$css_class, 
					$item['page'],
					$target, 
					getmsg(stripslashes($item['text']))
					);
		}
	}
?>
