<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$menu=array();
$menu[]=array(
			'0'=>array(
				'title'	=>'dashboard',
				'url'	=>'/admin/catalog'
			),
		);
$menu[]=array(
			'0'=>array(
				'title'	=>'catalog',
				'url'	=>'/admin/catalog'
			),
			'1'=>array(
				'title'	=>'catalog',
				'url'	=>'/admin/catalog'
			),
			'2'=>array(
				'title'	=>'upload survey',
				'url'	=>'/admin/catalog/upload'
			),
			'3'=>array(
				'title'	=>'batch import',
				'url'	=>'/admin/catalog/import'
			),
		);
$menu[]=array(
			'0'=>array(
				'title'	=>'vocabularies',
				'url'	=>'/admin/catalog'
			),
			'1'=>array(
				'title'	=>'add',
				'url'	=>'/admin/catalog'
			),
			'2'=>array(
				'title'	=>'upload survey',
				'url'	=>'/admin/catalog/upload'
			),
			'0'=>array(
				'title'	=>'batch import',
				'url'	=>'/admin/catalog/import'
			),
		);


/* End of file site_menu.php */
/* Location: ./system/application/config/site_menu.php */