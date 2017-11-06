<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH."../modules/phpmailer/class.phpmailer.php");

/**
* PHPMailer wrapper (beta)
*
*
*/

class MY_Email{

	private $_config;
	private $phpmailer_obj;
	private $_attach=array();
	private $error="";
	private $ci;


	/**
	 * Constructor - Sets Email Preferences
	 *
	 * @config	- loads settings from email.php file by default if file exists
	 */
	function __construct($config = array())
	{
		$this->_config=array();

		if (count($config)==0)
		{
			//load configurations from db
			$this->initialize($this->_load_settings());
		}
		else
		{
			//intialize with configurations passed by user or email.php file
			$this->initialize($config);
		}
		
		$this->ci =& get_instance();
	}


	function initialize($conf=array())
	{
		if (count($conf)==0)
		{
			return;
		}
		
		$this->_config = array_merge($this->_config,$conf);

		$this->phpmailer_obj = new PHPMailer();
		$this->phpmailer_obj->isHTML();
		$this->phpmailer_obj->isSMTP();
		
		//1=errors and messages, 2=messages only
		//$this->phpmailer_obj->SMTPDebug  = 2;

		$this->phpmailer_obj->SMTPAuth = true; 
		$this->phpmailer_obj->Timeout = 20;//$this->_config['smtp_timeout'];		
		$this->phpmailer_obj->Host = $this->_config['smtp_host'];
		$this->phpmailer_obj->Username = $this->_config['smtp_user'];
		$this->phpmailer_obj->Password = $this->_config['smtp_pass'];
		$this->phpmailer_obj->Port = $this->_config['smtp_port'];		
	}
	

	/**
	 * Get settings already loaded from database
	 *
	 */	
	function _load_settings()
	{
		$ci =& get_instance();
		$config['protocol']  = $ci->config->item("mail_protocol");
		$config['smtp_host'] = $ci->config->item("smtp_host");
		$config['smtp_user'] = $ci->config->item("smtp_user");
		$config['smtp_pass'] = $ci->config->item("smtp_pass");
		$config['smtp_port'] = $ci->config->item("smtp_port");
		$config['mailtype']  = 'html';
		$config['charset']   = 'utf-8';
		
		return $config;
	}

	/**
	*
	* 0 = disable debugging
	* 1 = error + messages
	* 2 = messages only
	**/
	public function set_debug($level=0)
	{
		$this->phpmailer_obj->SMTPDebug  = $level;
	}
	
	
	/**
	 * Initialize the Email Data
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear($clear_attachments = FALSE)
	{
		$this->phpmailer_obj->ClearAllRecipients();		
	}

	// --------------------------------------------------------------------

	/**
	 * Set FROM
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function from($from, $name = '')
	{
		$this->phpmailer_obj->SetFrom($from, $name);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Reply-to
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function reply_to($replyto, $name = '')
	{
		$this->phpmailer_obj->AddReplyTo($replyto,$name);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Recipients
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function to($to)
	{
		$this->phpmailer_obj->AddAddress($to);
	}

	// --------------------------------------------------------------------

	/**
	 * Set CC
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function cc($cc)
	{
		$this->phpmailer_obj->AddCC($cc);
	}

	// --------------------------------------------------------------------

	/**
	 * Set BCC
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function bcc($bcc, $limit = '')
	{
		if (is_array($bcc))
		{
			foreach($bcc as $email)
			{
				$this->phpmailer_obj->AddBCC($email);
			}
		}
		else
		{
			$this->phpmailer_obj->AddBCC($bcc);
		}	
	}

	// --------------------------------------------------------------------

	/**
	 * Set Email Subject
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function subject($subject)
	{
		$this->phpmailer_obj->Subject=$subject;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Body
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function message($body)
	{
		$this->phpmailer_obj->Body=$body;
	}

	// --------------------------------------------------------------------

	/**
	 * Assign file attachments
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function attach($filename, $disposition = 'attachment')
	{
	}


	/**
	 * Set Multipart Value
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_alt_message($str = '')
	{
		$this->phpmailer_obj->AltBody($str);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Mailtype
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_mailtype($type = 'text')
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set Wordwrap
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_wordwrap($wordwrap = TRUE)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set Protocol
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_protocol($protocol = 'mail')
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set Priority
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	public function set_priority($n = 3)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set Newline Character
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_newline($newline = "\n")
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set CRLF
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function set_crlf($crlf = "\n")
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set Message Boundary
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _set_boundaries()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Get the Message ID
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_message_id()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mail Protocol
	 *
	 * @access	protected
	 * @param	bool
	 * @return	string
	 */
	protected function _get_protocol($return = TRUE)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mail Encoding
	 *
	 * @access	protected
	 * @param	bool
	 * @return	string
	 */
	protected function _get_encoding($return = TRUE)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Get content type (text/html/attachment)
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_content_type()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Set RFC 822 Date
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _set_date()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Mime message
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_mime_message()
	{
		return "This is a multi-part message in MIME format.".$this->newline."Your email application may not support this format.";
	}

	// --------------------------------------------------------------------

	/**
	 * Validate Email Address
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validate_email($email)
	{
		if ( ! is_array($email))
		{
			$this->_set_error_message('lang:email_must_be_array');
			return FALSE;
		}

		foreach ($email as $val)
		{
			if ( ! $this->valid_email($val))
			{
				$this->_set_error_message('lang:email_invalid_address', $val);
				return FALSE;
			}
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Email Validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function valid_email($address)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? FALSE : TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Clean Extended Email Address: Joe Smith <joe@smith.com>
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function clean_email($email)
	{
		if ( ! is_array($email))
		{
			if (preg_match('/\<(.*)\>/', $email, $match))
			{
				return $match['1'];
			}
			else
			{
				return $email;
			}
		}

		$clean_email = array();

		foreach ($email as $addy)
		{
			if (preg_match( '/\<(.*)\>/', $addy, $match))
			{
				$clean_email[] = $match['1'];
			}
			else
			{
				$clean_email[] = $addy;
			}
		}

		return $clean_email;
	}

	// --------------------------------------------------------------------

	/**
	 * Build alternative plain text message
	 *
	 * This public function provides the raw message for use
	 * in plain-text headers of HTML-formatted emails.
	 * If the user hasn't specified his own alternative message
	 * it creates one by stripping the HTML
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_alt_message()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Word Wrap
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	public function word_wrap($str, $charlim = '')
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Build final headers
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _build_headers()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Write Headers as a string
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _write_headers()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Build Final Body and attachments
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _build_message()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 *
	 * @access	protected
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	protected function _prep_quoted_printable($str, $charlim = '')
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.  It's related
	 * but not identical to quoted-printable, so it has its own method
	 *
	 * @access	public
	 * @param	str
	 * @param	bool	// set to TRUE for processing From: headers
	 * @return	str
	 */
	protected function _prep_q_encoding($str, $from = FALSE)
	{
		$str = str_replace(array("\r", "\n"), array('', ''), $str);

		// Line length must not exceed 76 characters, so we adjust for
		// a space, 7 extra characters =??Q??=, and the charset that we will add to each line
		$limit = 75 - 7 - strlen($this->charset);

		// these special characters must be converted too
		$convert = array('_', '=', '?');

		if ($from === TRUE)
		{
			$convert[] = ',';
			$convert[] = ';';
		}

		$output = '';
		$temp = '';

		for ($i = 0, $length = strlen($str); $i < $length; $i++)
		{
			// Grab the next character
			$char = substr($str, $i, 1);
			$ascii = ord($char);

			// convert ALL non-printable ASCII characters and our specials
			if ($ascii < 32 OR $ascii > 126 OR in_array($char, $convert))
			{
				$char = '='.dechex($ascii);
			}

			// handle regular spaces a bit more compactly than =20
			if ($ascii == 32)
			{
				$char = '_';
			}

			// If we're at the character limit, add the line to the output,
			// reset our temp variable, and keep on chuggin'
			if ((strlen($temp) + strlen($char)) >= $limit)
			{
				$output .= $temp.$this->crlf;
				$temp = '';
			}

			// Add the character to our temporary line
			$temp .= $char;
		}

		$str = $output.$temp;

		// wrap each line with the shebang, charset, and transfer encoding
		// the preceding space on successive lines is required for header "folding"
		$str = trim(preg_replace('/^(.*)$/m', ' =?'.$this->charset.'?Q?$1?=', $str));

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Send Email
	 *
	 * @access	public
	 * @return	bool
	 */
	public function send()
	{
		$result=$this->phpmailer_obj->Send();
		
		if(!$result) 
		{
   			$this->error= $this->phpmailer_obj->ErrorInfo;
			$this->ci->db_logger->write_log($type='email-failed', $message=$this->phpmailer_obj->Subject . ' ERROR: '. $this->print_debugger());
  		 	return false;
		}
		
		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Batch Bcc Send.  Sends groups of BCCs in batches
	 *
	 * @access	public
	 * @return	bool
	 */
	public function batch_bcc_send()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Unwrap special elements
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _unwrap_specials()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Strip line-breaks via callback
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _remove_nl_callback($matches)
	{
		if (strpos($matches[1], "\r") !== FALSE OR strpos($matches[1], "\n") !== FALSE)
		{
			$matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
		}

		return $matches[1];
	}

	// --------------------------------------------------------------------

	/**
	 * Spool mail to the mail server
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _spool_email()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Send using mail()
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _send_with_mail()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Send using Sendmail
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _send_with_sendmail()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Send using SMTP
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _send_with_smtp()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * SMTP Connect
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _smtp_connect()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Send SMTP command
	 *
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	protected function _send_command($cmd, $data = '')
	{
	}

	// --------------------------------------------------------------------

	/**
	 *  SMTP Authenticate
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _smtp_authenticate()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Send SMTP data
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _send_data($data)
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Get SMTP data
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_smtp_data()
	{
	}

	// --------------------------------------------------------------------

	/**
	 * Get Hostname
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_hostname()
	{
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
	}

	// --------------------------------------------------------------------

	/**
	 * Get IP
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function _get_ip()
	{
		if ($this->_IP !== FALSE)
		{
			return $this->_IP;
		}

		$cip = (isset($_SERVER['HTTP_CLIENT_IP']) AND $_SERVER['HTTP_CLIENT_IP'] != "") ? $_SERVER['HTTP_CLIENT_IP'] : FALSE;
		$rip = (isset($_SERVER['REMOTE_ADDR']) AND $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : FALSE;
		$fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR'] : FALSE;

		if ($cip && $rip)	$this->_IP = $cip;
		elseif ($rip)		$this->_IP = $rip;
		elseif ($cip)		$this->_IP = $cip;
		elseif ($fip)		$this->_IP = $fip;

		if (strpos($this->_IP, ',') !== FALSE)
		{
			$x = explode(',', $this->_IP);
			$this->_IP = end($x);
		}

		if ( ! preg_match( "/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $this->_IP))
		{
			$this->_IP = '0.0.0.0';
		}

		unset($cip);
		unset($rip);
		unset($fip);

		return $this->_IP;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Debug Message
	 *
	 * @access	public
	 * @return	string
	 */
	public function print_debugger()
	{
		return $this->error;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Message
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _set_error_message($msg, $val = '')
	{
		$CI =& get_instance();
		$CI->lang->load('email');

		if (substr($msg, 0, 5) != 'lang:' || FALSE === ($line = $CI->lang->line(substr($msg, 5))))
		{
			$this->_debug_msg[] = str_replace('%s', $val, $msg)."<br />";
		}
		else
		{
			$this->_debug_msg[] = str_replace('%s', $val, $line)."<br />";
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Mime Types
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _mime_types($ext = "")
	{
		$mimes = array(	'hqx'	=>	'application/mac-binhex40',
						'cpt'	=>	'application/mac-compactpro',
						'doc'	=>	'application/msword',
						'bin'	=>	'application/macbinary',
						'dms'	=>	'application/octet-stream',
						'lha'	=>	'application/octet-stream',
						'lzh'	=>	'application/octet-stream',
						'exe'	=>	'application/octet-stream',
						'class'	=>	'application/octet-stream',
						'psd'	=>	'application/octet-stream',
						'so'	=>	'application/octet-stream',
						'sea'	=>	'application/octet-stream',
						'dll'	=>	'application/octet-stream',
						'oda'	=>	'application/oda',
						'pdf'	=>	'application/pdf',
						'ai'	=>	'application/postscript',
						'eps'	=>	'application/postscript',
						'ps'	=>	'application/postscript',
						'smi'	=>	'application/smil',
						'smil'	=>	'application/smil',
						'mif'	=>	'application/vnd.mif',
						'xls'	=>	'application/vnd.ms-excel',
						'ppt'	=>	'application/vnd.ms-powerpoint',
						'wbxml'	=>	'application/vnd.wap.wbxml',
						'wmlc'	=>	'application/vnd.wap.wmlc',
						'dcr'	=>	'application/x-director',
						'dir'	=>	'application/x-director',
						'dxr'	=>	'application/x-director',
						'dvi'	=>	'application/x-dvi',
						'gtar'	=>	'application/x-gtar',
						'php'	=>	'application/x-httpd-php',
						'php4'	=>	'application/x-httpd-php',
						'php3'	=>	'application/x-httpd-php',
						'phtml'	=>	'application/x-httpd-php',
						'phps'	=>	'application/x-httpd-php-source',
						'js'	=>	'application/x-javascript',
						'swf'	=>	'application/x-shockwave-flash',
						'sit'	=>	'application/x-stuffit',
						'tar'	=>	'application/x-tar',
						'tgz'	=>	'application/x-tar',
						'xhtml'	=>	'application/xhtml+xml',
						'xht'	=>	'application/xhtml+xml',
						'zip'	=>	'application/zip',
						'mid'	=>	'audio/midi',
						'midi'	=>	'audio/midi',
						'mpga'	=>	'audio/mpeg',
						'mp2'	=>	'audio/mpeg',
						'mp3'	=>	'audio/mpeg',
						'aif'	=>	'audio/x-aiff',
						'aiff'	=>	'audio/x-aiff',
						'aifc'	=>	'audio/x-aiff',
						'ram'	=>	'audio/x-pn-realaudio',
						'rm'	=>	'audio/x-pn-realaudio',
						'rpm'	=>	'audio/x-pn-realaudio-plugin',
						'ra'	=>	'audio/x-realaudio',
						'rv'	=>	'video/vnd.rn-realvideo',
						'wav'	=>	'audio/x-wav',
						'bmp'	=>	'image/bmp',
						'gif'	=>	'image/gif',
						'jpeg'	=>	'image/jpeg',
						'jpg'	=>	'image/jpeg',
						'jpe'	=>	'image/jpeg',
						'png'	=>	'image/png',
						'tiff'	=>	'image/tiff',
						'tif'	=>	'image/tiff',
						'css'	=>	'text/css',
						'html'	=>	'text/html',
						'htm'	=>	'text/html',
						'shtml'	=>	'text/html',
						'txt'	=>	'text/plain',
						'text'	=>	'text/plain',
						'log'	=>	'text/plain',
						'rtx'	=>	'text/richtext',
						'rtf'	=>	'text/rtf',
						'xml'	=>	'text/xml',
						'xsl'	=>	'text/xml',
						'mpeg'	=>	'video/mpeg',
						'mpg'	=>	'video/mpeg',
						'mpe'	=>	'video/mpeg',
						'qt'	=>	'video/quicktime',
						'mov'	=>	'video/quicktime',
						'avi'	=>	'video/x-msvideo',
						'movie'	=>	'video/x-sgi-movie',
						'doc'	=>	'application/msword',
						'word'	=>	'application/msword',
						'xl'	=>	'application/excel',
						'eml'	=>	'message/rfc822'
					);

		return ( ! isset($mimes[strtolower($ext)])) ? "application/x-unknown-content-type" : $mimes[strtolower($ext)];
	}

	
}//end-class
