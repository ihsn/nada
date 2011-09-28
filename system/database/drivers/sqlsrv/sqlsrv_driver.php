<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		Rick Ellis
 * @copyright	Copyright (c) 2006, EllisLab, Inc.
 * @license		http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * SQLSRV Database Adapter Class
 * (Note: This driver is intended for MSSQL Server 2005 onwards)
 *
 * NOTES:
 * ------
 *
 * This driver was written as Microsoft have announced that they do not
 * support the php_mssql.dll driver that utilises ntwdblib.dll in conjunction
 * with SQL Server 2005.  It is designed to use the new php_sqlsrv.dll driver
 * produced by the SQL Connectivity group at Microsoft.
 *
 * There is a user guide posted at MSDN at:
 * http://msdn.microsoft.com/en-us/library/ee229548%28SQL.10%29.aspx
 *
 * I strongly recommend you check the requirements at the URL below before you
 * start using this - it may save you a bunch of time.
 * http://msdn.microsoft.com/en-us/library/cc296170%28SQL.90%29.aspx
 *
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		Jon Ellis-Jones <jon@kaweb.co.uk>
 * @link		http://www.phrenzy.org/code/sql-server-and-php
 * @link		http://www.kaweb.co.uk/blog/mssql-server-2005-and-codeigniter
 * @link		http://codeigniter.com/forums/viewthread/86023/
 * @version		1.1
 *
 * -----------------------------------------------------------------------------
 * 
 * Special thanks to Julian Magnone <jmagnone@mgnn.com> for some additional code
 * and fixes to the 1.0 driver.
 *
 */
class CI_DB_sqlsrv_driver extends CI_DB {

var	$_escape_char = '';
    var $_like_escape_str = " ESCAPE '%s' ";
    var $_like_escape_chr = '!';    
    var $_count_string = "SELECT COUNT(*) AS ";
    var $_random_keyword = ' ASC'; // not currently supported 	
	/**
	 * db_connect
	 * 
	 * Connect to a database and product a connection resource
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */	
	function db_connect($pooling = false)
	{
		// Check for a UTF-8 charset being passed as CI's default 'utf8'.
		$character_set = (0 === strcasecmp('utf8', $this->char_set)) ? 'UTF-8' : $this->char_set;

		$connection = array(
			'UID'				=> empty($this->username) ? '' : $this->username,
			'PWD'				=> empty($this->password) ? '' : $this->password,
			'Database'			=> $this->database,
			'ConnectionPooling' => $pooling ? 1 : 0,
			'CharacterSet'		=> $character_set
		);
		
		// If the username and password are both empty, assume this is a 
		// 'Windows Authentication Mode' connection.
		if(empty($connection['UID']) && empty($connection['PWD'])) {
			unset($connection['UID'], $connection['PWD']);
		}

		return sqlsrv_connect($this->hostname, $connection);
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Persistent database connection
	 *
	 *	You cannot really specify persistance using the SQLSRV driver.  The
	 * closest is connection pooling, so we pass this to the main connect
	 * function instead.
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */	
	function db_pconnect()
	{
		return $this->db_connect(true);
	}

	
	// --------------------------------------------------------------------

	/**
	 * Select the database
	 *
	 *	Not required by the SQLSRV driver, as a database is selected on
	 * connection.  You cannot switch databases, a new connection must be made.
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */	
	function db_select()
	{
		return true;
	}


	// --------------------------------------------------------------------

	/**
	 * Set client character set
	 *
	 *	Not required by SQLSRV driver.  The character set is passed during the
	 * initial connection.  You cannot switch character sets, a new connection
	 * must be made.
	 *
	 * @access	private
	 * @param	string
	 * @return	boolean
	 */
	function _db_set_charset($charset)
	{
		return true;
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Version number query string
	 *
	 * @access	public
	 * @return	string
	 */
	function _version()
	{
		$info = sqlsrv_server_info($this->conn_id);
		return sprintf("select '%s' as ver", $info['SQLServerVersion']);
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Execute the query
	 *
	 * @todo	allow for parameterized queries
	 * @access	private called by the base class
	 * @param	string	an SQL query
	 * @return	resource
	 */	
	function _execute($sql)
	{
		$sql = $this->_prep_query($sql);
		return sqlsrv_query($this->conn_id, $sql, null, array(
			'Scrollable'				=> SQLSRV_CURSOR_STATIC,
			'SendStreamParamsAtExec'	=> true
		));
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Prep the query
	 *
	 * If needed, each database adapter can prep the query string
	 *
	 * @access	private called by execute()
	 * @param	string	an SQL query
	 * @return	string
	 */	
	function _prep_query($sql)
	{
		return $sql;
	}


	// --------------------------------------------------------------------

	/**
	 * Begin Transaction
	 *
	 * @access	public
	 * @return	bool		
	 */	
	function trans_begin($test_mode = FALSE)
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}
		
		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		// Reset the transaction failure flag.
		// If the $test_mode flag is set to TRUE transactions will be rolled back
		// even if the queries produce a successful result.
		$this->_trans_failure = ($test_mode === TRUE) ? TRUE : FALSE;
		
		return sqlsrv_begin_transaction($this->conn_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Commit Transaction
	 *
	 * @access	public
	 * @return	bool		
	 */	
	function trans_commit()
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}
		
		return sqlsrv_commit($this->conn_id);
	}

	// --------------------------------------------------------------------

	/**
	 * Rollback Transaction
	 *
	 * @access	public
	 * @return	bool		
	 */	
	function trans_rollback()
	{
		if ( ! $this->trans_enabled)
		{
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0)
		{
			return TRUE;
		}

		return sqlsrv_rollback($this->conn_id);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Escape String
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function escape_str($str)	
	{	
		// Escape single quotes
		return str_replace("'", "''", $str);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Affected Rows
	 *
	 * @access	public
	 * @return	integer
	 */
	function affected_rows()
	{
		return @sqlsrv_rows_affected($this->conn_id);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Insert ID
	 *
	 * @access	public
	 * @return	integer
	 */
	function insert_id()
	{
		return $this->query('select @@IDENTITY as insert_id')->row('insert_id');
	}

	// --------------------------------------------------------------------

	/**
	 * "Count All" query
	 *
	 * Generates a platform-specific query string that counts all records in
	 * the specified database
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function count_all($table = '')
	{
		if ($table == '')
			return '0';
	
		$query = $this->query("SELECT COUNT(*) AS numrows FROM " . $this->dbprefix . $table);
		
		if ($query->num_rows() == 0)
			return '0';

		$row = $query->row();
		return $row->numrows;
	}

	// --------------------------------------------------------------------

	/**
	 * List table query
	 *
	 * Generates a platform-specific query string so that the table names can be fetched
	 *
	 * @access	private
	 * @return	string
	 */
	function _list_tables()
	{
		return "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";		
	}

	// --------------------------------------------------------------------

	/**
	 * List column query
	 *
	 * Generates a platform-specific query string so that the column names can be fetched
	 *
	 * @access	private
	 * @param	string	the table name
	 * @return	string
	 */
	function _list_columns($table = '')
	{
		return "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '".$this->_escape_table($table)."'";	
	}

	// --------------------------------------------------------------------

	/**
	 * Field data query
	 *
	 * Generates a platform-specific query so that the column data can be retrieved
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	object
	 */
	function _field_data($table)
	{
		return "SELECT TOP 1 * FROM " . $this->_escape_table($table);	
	}

	// --------------------------------------------------------------------

	/**
	 * The error message string
	 *
	 * @access	private
	 * @return	string
	 */
	function _error_message()
	{
		$errors_array=sqlsrv_errors();
		$error=false;
		if (is_array($errors_array))
		{
			$error = array_shift($errors_array);
		}	
		return !empty($error['message']) ? $error['message'] : null;
	}
	
	// --------------------------------------------------------------------

	/**
	 * The error message number
	 *
	 * @access	private
	 * @return	integer
	 */
	function _error_number()
	{
		$error = array_shift(sqlsrv_errors());
		return isset($error['SQLSTATE']) ? $error['SQLSTATE'] : null;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Escape Table Name
	 *
	 * This function adds backticks if the table name has a period
	 * in it. Some DBs will get cranky unless periods are escaped
	 *
	 * @access	private
	 * @param	string	the table name
	 * @return	string
	 */
	function _escape_table($table)
	{
		return $table;
	}	



	/**
	 * Escape the SQL Identifiers
	 *
	 * This function escapes column and table names
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	function _escape_identifiers($item)
	{
		return $item;
	}



	// --------------------------------------------------------------------

	/**
	 * From Tables
	 *
	 * This function implicitly groups FROM tables so there is no confusion
	 * about operator precedence in harmony with SQL standards
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	function _from_tables($tables)
	{
		if(!is_array($tables)) {
			$tables = array($tables);
		}
		
		return implode(', ', $tables);
	}


	
	// --------------------------------------------------------------------

	/**
	 * Insert statement
	 *
	 * Generates a platform-specific insert string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the insert keys
	 * @param	array	the insert values
	 * @return	string
	 */
	function _insert($table, $keys, $values)
	{	
		return "INSERT INTO ".$this->_escape_table($table)." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
	}
	
	// --------------------------------------------------------------------

	/**
	 * Update statement
	 *
	 * Generates a platform-specific update string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the update data
	 * @param	array	the where clause
	 * @return	string
	 */
	function _update($table, $values, $where)
	{
		foreach($values as $key => $val)
		{
			$valstr[] = $key." = ".$val;
		}
	
		return "UPDATE ".$this->_escape_table($table)." SET ".implode(', ', $valstr)." WHERE ".implode(" ", $where);
	}
	
	// --------------------------------------------------------------------

	/**
	 * Delete statement
	 *
	 * Generates a platform-specific delete string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the where clause
	 * @return	string
	 */	
	function _delete($table, $where)
	{
		return "DELETE FROM ".$this->_escape_table($table)." WHERE ".implode(" ", $where);
	}

	// --------------------------------------------------------------------

	/**
	 * Limit string
	 *
	 * Generates a platform-specific LIMIT clause
	 *
	 * @access	public
	 * @param	string	the sql query string
	 * @param	integer	the number of rows to limit the query to
	 * @param	integer	the offset value
	 * @return	string
	 */
	function _xlimit($sql, $limit, $offset)
	{
		$i = $limit + $offset;
	
		return preg_replace('/(^\SELECT (DISTINCT)?)/i','\\1 TOP '.$i.' ', $sql);		
	}

	function _limit($sql, $limit, $offset)
    {
		//default order if no order by is defined. 
		//limit queries require an ORDER BY
		$OrderBy="ORDER BY RAND()"; 
		
        if (count($this->ar_orderby) > 0)
        {
            $OrderBy  = "ORDER BY ";
            $OrderBy .= implode(', ', $this->ar_orderby);

            if ($this->ar_order !== FALSE)
            {
                $OrderBy .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
            }
        }

        $sql = preg_replace('/(\\'. $OrderBy .'\n?)/i','', $sql);
        $sql = preg_replace('/(^\SELECT (DISTINCT)?)/i','\\1 row_number() OVER ('.$OrderBy.') AS rownum, ', $sql);

        $NewSQL = "SELECT * \nFROM (\n" . $sql . ") AS A \nWHERE A.rownum BETWEEN (" .($offset + 1) . ") AND (".($offset + $limit).")";

        return     $NewSQL;
    } 	

	// --------------------------------------------------------------------

	/**
	 * Close DB Connection
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	function _close($conn_id)
	{
		sqlsrv_close($conn_id);
	}	
	
	/**
	 * "Count All Results" query
	 *
	 * Generates a platform-specific query string that counts all records 
	 * returned by an Active Record query.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function count_all_results($table = '')
	{
		if ($table != '')
		{
			$this->_track_aliases($table);
			$this->from($table);
		}
		
		$sql = $this->_compile_select($this->_count_string . $this->_protect_identifiers('num_rows'));
		
		//remove order by
		$pos=strpos(strtolower($sql),"order ");

		if ($pos>0)
		{
			$sql=substr($sql,0,$pos-1);
		}
		
		$query = $this->query($sql);
		$this->_reset_select();
	
		if (!$query)
		{
			return '0';
		}
	
		if ($query->num_rows() == 0)
		{
			return '0';
		}

		$row = $query->row();
		return $row->num_rows;
	}
	
}