<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Loader: the installer connects to the database with db_debug off.
 *
 * With db_debug on (config/database.php), a database that does not exist yet, or a table the installer has not
 * created yet, stops the request with CodeIgniter's database error page before the installer can run. The installer
 * is how those are fixed, so it connects with db_debug off and reports the problem itself (Db_connection_guard and
 * Db_connection_check say what to fix). Every other request keeps the configured db_debug.
 */
class MY_Loader extends CI_Loader
{
	public function database($params = '', $return = FALSE, $query_builder = NULL)
	{
		if ($params === '' && $this->_is_install_request())
		{
			$install_params = $this->_default_db_params();
			if ($install_params !== NULL)
			{
				$install_params['db_debug'] = FALSE;
				$params = $install_params;
			}
		}

		return parent::database($params, $return, $query_builder);
	}

	/**
	 * Whether this web request is routed to the installer.
	 *
	 * @return bool
	 */
	private function _is_install_request()
	{
		if (is_cli())
		{
			return FALSE;
		}

		$router =& load_class('Router', 'core');
		return strtolower((string) $router->class) === 'install';
	}

	/**
	 * The active group's settings from config/database.php (the file DB() reads when given no params), or NULL to
	 * leave loading to DB() — which then reports a missing or invalid file itself.
	 *
	 * @return array|null
	 */
	private function _default_db_params()
	{
		if ( ! file_exists($file_path = APPPATH.'config/'.ENVIRONMENT.'/database.php')
			&& ! file_exists($file_path = APPPATH.'config/database.php'))
		{
			return NULL;
		}

		include($file_path);

		if (empty($active_group) || ! isset($db[$active_group]) || ! is_array($db[$active_group]))
		{
			return NULL;
		}

		return $db[$active_group];
	}
}
