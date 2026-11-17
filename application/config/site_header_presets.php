<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Site header layouts
|--------------------------------------------------------------------------
|
| Starting layouts for Admin > Settings > Site header. Each one sets the rows (top, main, bottom): what goes in
| the left, center and right of each row, and the row's style and size. Values not given here use the row
| defaults in Site_header_builder::row_defaults(). Brand, logo, colours and other settings are kept when an admin
| picks a layout.
|
| Items: brand | menu (login, language: put them inside the menu) | login | language | text (text, url)
|
| 'classic' is the default header and must match the standard NADA header.
*/

$light_bar = '#eef2f6';

$config['site_header_presets'] = array(

	'classic' => array(
		'title'       => 'Classic',
		'description' => 'Brand on the left, menu and login on the right, languages above. The standard NADA header.',
		'settings'    => array(
			'background_scope' => 'header',
			'rows' => array(
				'top'    => array('enabled' => true, 'right' => array(array('type' => 'language')),
					'style' => array('min_height' => 27, 'padding_y' => 0, 'padding_y_mobile' => 0)),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'right' => array(array('type' => 'menu', 'login' => true))),
				'bottom' => array('enabled' => false),
			),
		),
	),

	'minimal' => array(
		'title'       => 'One line',
		'description' => 'Everything on one line: brand on the left; menu, languages and login on the right.',
		'settings'    => array(
			'background_scope' => 'header',
			'rows' => array(
				'top'    => array('enabled' => false),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'right' => array(array('type' => 'menu', 'login' => true, 'language' => true))),
				'bottom' => array('enabled' => false),
			),
		),
	),

	'utility_bar' => array(
		'title'       => 'Utility bar',
		'description' => 'A thin bar on top with languages and login. Brand and menu below.',
		'settings'    => array(
			'background_scope' => 'header',
			'rows' => array(
				'top'    => array('enabled' => true, 'right' => array(array('type' => 'language'), array('type' => 'login')),
					'style' => array('min_height' => 32, 'padding_y' => 0, 'padding_y_mobile' => 0, 'text_size' => 'small', 'border_bottom' => true)),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'right' => array(array('type' => 'menu'))),
				'bottom' => array('enabled' => false),
			),
		),
	),

	'split' => array(
		'title'       => 'Menu in the middle',
		'description' => 'Brand on the left, menu in the middle, languages and login on the right.',
		'settings'    => array(
			'background_scope' => 'header',
			'rows' => array(
				'top'    => array('enabled' => false),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'center' => array(array('type' => 'menu')),
					'right' => array(array('type' => 'language'), array('type' => 'login'))),
				'bottom' => array('enabled' => false),
			),
		),
	),

	'centered' => array(
		'title'       => 'Centered',
		'description' => 'Brand in the middle, menu centered on the line below.',
		'settings'    => array(
			'background_scope' => 'header',
			'rows' => array(
				'top'    => array('enabled' => true, 'right' => array(array('type' => 'language')),
					'style' => array('min_height' => 24, 'padding_y' => 0, 'padding_y_mobile' => 0, 'text_size' => 'small')),
				'main'   => array('enabled' => true, 'center' => array(array('type' => 'brand')),
					'style' => array('padding_y' => 12, 'border_bottom' => true)),
				'bottom' => array('enabled' => true, 'center' => array(array('type' => 'menu', 'login' => true))),
			),
		),
	),

	'menu_bar' => array(
		'title'       => 'Menu bar',
		'description' => 'Brand and languages on top, a full-width menu bar below with login on the right.',
		'settings'    => array(
			'background_scope' => 'rows',
			'rows' => array(
				'top'    => array('enabled' => false),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'right' => array(array('type' => 'language')), 'style' => array('padding_y' => 12)),
				'bottom' => array('enabled' => true, 'left' => array(array('type' => 'menu')),
					'right' => array(array('type' => 'login')),
					'style' => array('bg_color' => $light_bar, 'full_width' => false, 'padding_y' => 4, 'padding_y_mobile' => 4)),
			),
		),
	),

	'three_rows' => array(
		'title'       => 'Three rows',
		'description' => 'Languages and login on top, then the brand, then a menu bar.',
		'settings'    => array(
			'background_scope' => 'rows',
			'rows' => array(
				'top'    => array('enabled' => true, 'right' => array(array('type' => 'language'), array('type' => 'login')),
					'style' => array('min_height' => 32, 'padding_y' => 0, 'padding_y_mobile' => 0, 'text_size' => 'small', 'border_bottom' => true)),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'style' => array('padding_y' => 16, 'padding_y_mobile' => 8)),
				'bottom' => array('enabled' => true, 'left' => array(array('type' => 'menu')),
					'style' => array('bg_color' => $light_bar, 'padding_y' => 4, 'padding_y_mobile' => 4)),
			),
		),
	),

	'banner' => array(
		'title'       => 'Banner',
		'description' => 'A tall band for a background image, with the brand, languages and login. Menu bar below.',
		'settings'    => array(
			'background_scope' => 'rows',
			'rows' => array(
				'top'    => array('enabled' => false),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'right' => array(array('type' => 'language'), array('type' => 'login')),
					'style' => array('bg_color' => $light_bar, 'min_height' => 140, 'min_height_mobile' => 90)),
				'bottom' => array('enabled' => true, 'left' => array(array('type' => 'menu')),
					'style' => array('padding_y' => 4, 'padding_y_mobile' => 4, 'border_bottom' => true)),
			),
		),
	),

	'dark_banner' => array(
		'title'       => 'Dark banner',
		'description' => 'A lighter top bar, a dark main band for the brand, and a black menu bar below.',
		'settings'    => array(
			'background_scope' => 'rows',
			'header_style' => array('bg_color' => '#1f3b57', 'text_color' => '#ffffff'),
			'rows' => array(
				'top'    => array('enabled' => true, 'right' => array(array('type' => 'language'), array('type' => 'login')),
					'style' => array(
						'bg_color' => '#2d5478', 'text_color' => '#ffffff',
						'min_height' => 32, 'padding_y' => 0, 'padding_y_mobile' => 0, 'text_size' => 'small', 'full_width' => true,
					)),
				'main'   => array('enabled' => true, 'left' => array(array('type' => 'brand')),
					'style' => array(
						'bg_color' => '#1f3b57', 'text_color' => '#ffffff',
						'min_height' => 110, 'min_height_mobile' => 70,
					)),
				'bottom' => array('enabled' => true, 'left' => array(array('type' => 'menu')),
					'style' => array('bg_color' => '#000000', 'text_color' => '#ffffff')),
			),
		),
	),
);
