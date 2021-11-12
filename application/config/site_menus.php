<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$menu=array();
$menu[]=array(
			'title'	=>'Dashboard',
			'url'	=>'admin',
		);
$menu[]=array(
		'title'	=>'Studies',
		'url'	=>'admin/catalog',
		'items'	=>array(
			array(
				'title'	=>'Manage studies',
				'url'	=>'admin/catalog'
			),
			array(
				'type'	=>'divider'
			),
			array(
				'title'	=>'Licensed requests',
				'url'	=>'admin/licensed_requests'
			),
			array(
				'type'	=>'divider'
			),
			array(
				'title'	=>'Manage collections',
				'url'	=>'admin/repositories'
			),
			array(
				'type'	=>'divider'
			),
			array(
				'title'	=>'Bulk data access',
				'url'	=>'admin/da_collections'
			)
		)
);
$menu[]=array(
	'title'	=>'Citations',
	'url'	=>'admin/citations',
	'items'	=>array(
		array(
			'title'	=>'All citations',
			'url'	=>'admin/citations'
		),
		array(
			'title'	=>'Import citations',
			'url'	=>'admin/citations/import'
		),
		array(
			'title'	=>'Export citations',
			'url'	=>'admin/citations/export'
		)
	)
);
$menu[]=array(
	'title'	=>'Users',
	'url'	=>'admin/users',
	'items'	=>array(
		array(
			'title'	=>'All users',
			'url'	=>'admin/users'
		),
		array(
			'title'	=>'Add user',
			'url'	=>'admin/users/add'
		),
		/*array(
			'title'	=>'Impersonate user',
			'url'	=>'admin/users/impersonate'
		)*/
	)
);

$menu[]=array(
	'title'	=>'Menu',
	'url'	=>'admin/menu',
	'items'	=>array(
		array(
			'title'	=>'All pages',
			'url'	=>'admin/menu'
		)
	)
);

$menu[]=array(
	'title'	=>'Data deposit',
	'url'	=>'admin/datadeposit',
);


$menu[]=array(
	'title'	=>'Reports',
	'url'	=>'admin/reports',
	'items'	=>array(
		array(
			'title'	=>'All reports',
			'url'	=>'admin/reports'
		)
	)
);



$menu[]=array(
	'title'	=>'Settings',
	'url'	=>'admin/configurations',
	'items'	=>array(
		array(
			'title'	=>'Settings',
			'url'	=>'admin/configurations'
		),
		array(
			'type'	=>'divider'
		),
		array(
			'title'	=>'Regions',
			'url'	=>'admin/Regions'
		),
		array(
			'type'	=>'divider'
		),
		array(
			'title'	=>'Countries',
			'url'	=>'admin/countries'
		),
		array(
			'type'	=>'divider'
		),
		array(
			'title'	=>'Translate',
			'url'	=>'admin/translate'
		),
		array(
			'type'	=>'divider'
		),
		/*array(
			'title'	=>'Vocabularies',
			'url'	=>'admin/vocabularies'
		),
		array(
			'type'	=>'divider'
		),*/
		array(
			'title'	=>'Facets',
			'url'	=>'admin/facets'
		),
	)
);

$config['site_menu']=$menu;
/* End of file site_menu.php */
/* Location: ./system/application/config/site_menu.php */