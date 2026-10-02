<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('var_dump_pre'))
{
	function nada_dump($data) {
			echo '<pre>';
			var_dump($data);
			echo '</pre>';
	}
}

if ( ! function_exists('requested_path_for_log'))
{
	/**
	 * Best-effort requested path for error logs (raw REQUEST_URI when available).
	 *
	 * @return string
	 */
	function requested_path_for_log()
	{
		if (is_cli())
		{
			$argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
			return count($argv) > 1 ? implode(' ', array_slice($argv, 1)) : '(cli)';
		}

		if (!empty($_SERVER['REQUEST_URI']))
		{
			return $_SERVER['REQUEST_URI'];
		}

		if (function_exists('get_instance'))
		{
			$ci =& get_instance();
			if (isset($ci->uri))
			{
				$uri_string = $ci->uri->uri_string();
				if ($uri_string !== '')
				{
					return '/'.ltrim($uri_string, '/');
				}

				$segments = $ci->uri->segment_array();
				if (!empty($segments))
				{
					return '/'.implode('/', $segments);
				}
			}
		}

		return '(unknown)';
	}
}

if ( ! function_exists('log_404_not_found'))
{
	/**
	 * Log a 404 with the requested path and optional context (route, controller, etc.).
	 *
	 * @param string $context Optional extra detail, e.g. "route=admin/missing"
	 */
	function log_404_not_found($context = '')
	{
		$log = '404 Page Not Found: '.requested_path_for_log();
		if ($context !== '')
		{
			$log .= ' | '.$context;
		}
		log_message('error', $log);
	}
}

/* End of file debug_helper.php */
/* Location: ./application/helpers/debug_helper.php */
