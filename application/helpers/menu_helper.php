<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('menu_item_url'))
{
	function menu_item_url($item)
	{
		$url = isset($item['url']) ? trim($item['url']) : '';
		if ($url === '') {
			return '#';
		}

		if (isset($item['linktype']) && (int) $item['linktype'] === 1) {
			return preg_match('/^https?:\/\//i', $url) ? $url : 'https://' . $url;
		}

		if (function_exists('is_url') && is_url($url)) {
			return $url;
		}

		return site_url($url);
	}
}

if ( ! function_exists('menu_item_target'))
{
	function menu_item_target($item)
	{
		if (!empty($item['target']) && (int) $item['target'] === 1) {
			return ' target="_blank" rel="noopener noreferrer"';
		}
		return '';
	}
}

if ( ! function_exists('menu_item_is_active'))
{
	function menu_item_is_active($item, $current_page)
	{
		$url = menu_item_url($item);
		if ($url !== '#' && $url === $current_page) {
			return TRUE;
		}

		if (!empty($item['children']) && is_array($item['children'])) {
			foreach ($item['children'] as $child) {
				if (menu_item_is_active($child, $current_page)) {
					return TRUE;
				}
			}
		}

		return FALSE;
	}
}
