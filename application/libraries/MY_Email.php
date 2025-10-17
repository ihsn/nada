<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email library with driver support
 *
 * This class extends CI_Email and provides a driver-based architecture
 * for sending emails through different providers (SMTP, SendGrid, Microsoft Graph, etc.)
 */

use Email\Drivers\EmailInterface;
use Email\Drivers\EmailFactory;
use Email\Drivers\SmtpDriver;
use Email\Drivers\SendgridDriver;

require_once(APPPATH . 'libraries/Email/drivers/EmailInterface.php');
require_once(APPPATH . 'libraries/Email/drivers/EmailFactory.php');
require_once(APPPATH . 'libraries/Email/drivers/SmtpDriver.php');
require_once(APPPATH . 'libraries/Email/drivers/SendgridDriver.php');

class MY_Email extends CI_Email {

    /**
     * Current email driver instance
     * 
     * @var EmailInterface
     */
    protected $driver;
    
    /**
     * Driver name
     * 
     * @var string
     */
    protected $driver_name;
    
    /**
     * CodeIgniter instance
     * 
     * @var object
     */
    protected $CI;
    
    /**
     * Default properties for backward compatibility
     * 
     * @var array
     */
    protected static $default_properties = array(
        'useragent' => 'CodeIgniter',
        'mailpath' => '/usr/sbin/sendmail',
        'protocol' => 'mail',
        'smtp_host' => '',
        'smtp_auth' => NULL,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_port' => 25,
        'smtp_timeout' => 5,
        'smtp_keepalive' => FALSE,
        'smtp_crypto' => '',
        'wordwrap' => TRUE,
        'wrapchars' => 76,
        'mailtype' => 'text',
        'charset' => 'UTF-8',
        'multipart' => 'mixed',
        'alt_message' => '',
        'validate' => FALSE,
        'priority' => 3,
        'newline' => "\n",
        'crlf' => "\n",
        'dsn' => FALSE,
        'send_multipart' => TRUE,
        'bcc_batch_mode' => FALSE,
        'bcc_batch_size' => 200,
        'debug_output' => '',
        'smtp_debug' => 0,
        'encoding' => '8bit',
        'smtp_auto_tls' => true,
        'smtp_conn_options' => array(),
        'dkim_domain' => '',
        'dkim_private' => '',
        'dkim_private_string' => '',
        'dkim_selector' => '',
        'dkim_passphrase' => '',
        'dkim_identity' => '',
        'email_driver' => 'smtp',
    );

    protected $properties = array();

    protected static $protocols = array('mail', 'smtp');
    protected static $mailtypes = array('html', 'text');
    protected static $encodings_ci = array('8bit', '7bit');
    protected static $encodings_phpmailer = array('8bit', '7bit', 'binary', 'base64', 'quoted-printable');

    /**
     * Constructor
     * 
     * @param array $config Configuration array
     */
    public function __construct(array $config = array()) {
        $this->CI = get_instance();
        $this->CI->load->helper('email');
        $this->CI->load->helper('html');

        self::$default_properties['debug_output'] = (strpos(PHP_SAPI, 'cli') !== false OR defined('STDIN')) ? 'echo' : 'html';

        // remove parent class properties
        // this is needed to avoid conflicts with the parent class properties
        foreach (array_keys(self::$default_properties) as $name) {
            if (property_exists($this, $name)) {
                unset($this->{$name});
            }
        }

        $this->properties = self::$default_properties;
        $this->refresh_properties();

        $this->_safe_mode = (!is_php('5.4') && ini_get('safe_mode'));

        if (!isset($config['charset'])) {
            $config['charset'] = config_item('charset');
        }

        $this->initialize($config);

        log_message('info', 'Email Class Initialized (Driver: '.$this->driver_name.')');
    }

    /**
     * Triggers the setter functions to do their job.
     */
    protected function refresh_properties() {
        foreach (array_keys(self::$default_properties) as $name) {
            $this->{$name} = $this->{$name};
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        if (method_exists(get_parent_class($this), '__destruct')) {
            parent::__destruct();
        }
    }

    /**
     * Extract names from email addresses
     * 
     * @param array $emails Array of email addresses
     * @return array Array of names
     */
    protected function _extract_name($emails) {
        $names = array();
        
        if (!is_array($emails)) {
            $emails = array($emails);
        }
        
        foreach ($emails as $email) {
            if (preg_match('/^(.+)\s*<(.+)>$/', $email, $matches)) {
                $names[] = trim($matches[1], '"\'');
            } else {
                $names[] = '';
            }
        }
        
        return $names;
    }

    /**
     * Magic setter
     * 
     * @param string $name Property name
     * @param mixed $value Property value
     */
    function __set($name, $value) {
        $method = 'set_'.$name;

        if (is_callable(array($this, $method))) {
            $this->$method($value);
        } else {
            $this->properties[$name] = $value;
        }
    }

    /**
     * Magic getter
     * 
     * @param string $name Property name
     * @return mixed Property value
     */
    function __get($name) {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } else {
            throw new OutOfBoundsException('The property '.$name.' does not exists.');
        }
    }

    /**
     * Magic isset
     * 
     * @param string $name Property name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->properties[$name]);
    }

    /**
     * Magic unset
     * 
     * @param string $name Property name
     */
    public function __unset($name) {
        $this->$name = null;

        if (array_key_exists($name, $this->properties)) {
            unset($this->properties[$name]);
        } else {
            unset($this->$name);
        }
    }

    /**
     * An empty method that keeps chaining
     * 
     * @param mixed $expression A (conditional) expression that is to be executed.
     * @return object Returns a reference to the created library instance.
     */
    public function that($expression = NULL) {
        return $this;
    }

    /**
     * Initialize email with configuration
     * 
     * @param array $config Configuration array
     * @return object
     */
    public function initialize(array $config = array()) {
        // If no config provided, load from email.php config file
        if (empty($config)) {
            $this->CI->load->helper('email');
            $config = load_email_settings();
            
            if (empty($config)) {
                // Final fallback: load directly from config
                $this->CI->config->load('email');
                $config = array(
                    'email_driver' => $this->CI->config->item('email_driver') ?: 'smtp',
                    'smtp_host' => $this->CI->config->item('smtp_host'),
                    'smtp_auth' => $this->CI->config->item('smtp_auth'),
                    'smtp_user' => $this->CI->config->item('smtp_user'),
                    'smtp_pass' => $this->CI->config->item('smtp_pass'),
                    'smtp_port' => $this->CI->config->item('smtp_port'),
                    'smtp_crypto' => $this->CI->config->item('smtp_crypto'),
                    'smtp_debug' => $this->CI->config->item('smtp_debug'),
                    'debug_output' => $this->CI->config->item('debug_output'),
                    'mailtype' => $this->CI->config->item('mailtype'),
                    'charset' => $this->CI->config->item('charset')
                );
            }
        }
        
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }

        // Initialize driver if it exists
        if ($this->driver) {
            $this->driver->initialize($config);
        }

        $this->clear();
        
        // Automatically set FROM address from configuration (after clear)
        $this->_set_default_from_address($config);
        
        return $this;
    }
    
    /**
     * Set default FROM address from configuration
     * 
     * @param array $config Configuration array
     * @return void
     */
    protected function _set_default_from_address($config) {
        // Get from email with fallback chain
        $from_email = null;
        $from_name = null;
        
        // Priority 1: smtp_email from email.php config file
        if (!empty($config['smtp_email'])) {
            $from_email = $config['smtp_email'];
        }
        // Priority 2: website_webmaster_email from DB config
        elseif (!empty($this->CI->config->item('website_webmaster_email'))) {
            $from_email = $this->CI->config->item('website_webmaster_email');
        }
        // Final fallback
        elseif (isset($_SERVER['HTTP_HOST'])) {
            $from_email = 'noreply@' . $_SERVER['HTTP_HOST'];
        }
        
        // Get from name with fallback chain
        // Priority 1: smtp_display_name from email.php config file
        if (!empty($config['smtp_display_name'])) {
            $from_name = $config['smtp_display_name'];
        }
        // Priority 2: website_webmaster_name from DB config
        elseif (!empty($this->CI->config->item('website_webmaster_name'))) {
            $from_name = $this->CI->config->item('website_webmaster_name');
        }
        // Final fallback
        else {
            $from_name = 'NADA Administrator';
        }
        
        // Set the from address if we have one
        if ($from_email) {
            $this->from($from_email, $from_name);
        }
    }

    /**
     * Clear email data
     * 
     * @param bool $clear_attachments Whether to clear attachments
     * @return object
     */
    public function clear($clear_attachments = false) {
        $clear_attachments = !empty($clear_attachments);

        parent::clear($clear_attachments);

        if ($this->driver) {
            $this->driver->clear();
        }

        return $this;
    }

    /**
     * Set sender email and name
     * 
     * @param string $from Sender email
     * @param string $name Sender name
     * @param string $return_path Return path
     * @return object
     */
    public function from($from, $name = '', $return_path = NULL) {
        $from = (string) $from;
        $name = (string) $name;
        $return_path = (string) $return_path;

        if ($this->driver) {
            if (preg_match('/\<(.*)\>/', $from, $match)) {
                $from = $match['1'];
            }

            if ($this->validate) {
                $this->validate_email($this->_str_to_array($from));
                if ($return_path) {
                    $this->validate_email($this->_str_to_array($return_path));
                }
            }

            $this->driver->from($from, $name);
        } else {
            parent::from($from, $name, $return_path);
        }

        return $this;
    }

    /**
     * Set reply-to email
     * 
     * @param string $replyto Reply-to email
     * @param string $name Reply-to name
     * @return object
     */
    public function reply_to($replyto, $name = '') {
        $replyto = (string) $replyto;
        $name = (string) $name;

        if ($this->driver) {
            if (preg_match('/\<(.*)\>/', $replyto, $match)) {
                $replyto = $match['1'];
            }

            if ($this->validate) {
                $this->validate_email($this->_str_to_array($replyto));
            }

            if ($name == '') {
                $name = $replyto;
            }

            $this->driver->setHeader('Reply-To', $name . ' <' . $replyto . '>');
            $this->_replyto_flag = TRUE;
        } else {
            parent::reply_to($replyto, $name);
        }

        return $this;
    }

    /**
     * Add recipient
     * 
     * @param string $to Recipient email
     * @return object
     */
    public function to($to) {
        if ($this->driver) {
            $to = $this->_str_to_array($to);
            $names = $this->_extract_name($to);
            $to = $this->clean_email($to);

            if ($this->validate) {
                $this->validate_email($to);
            }

            foreach ($to as $address) {
                $this->driver->to($address);
            }
        } else {
            parent::to($to);
        }

        return $this;
    }

    /**
     * Add CC recipient
     * 
     * @param string $cc CC recipient email
     * @return object
     */
    public function cc($cc) {
        if ($this->driver) {
            $cc = $this->_str_to_array($cc);
            $names = $this->_extract_name($cc);
            $cc = $this->clean_email($cc);

            if ($this->validate) {
                $this->validate_email($cc);
            }

            foreach ($cc as $address) {
                $this->driver->cc($address);
            }
        } else {
            parent::cc($cc);
        }

        return $this;
    }

    /**
     * Add BCC recipient
     * 
     * @param string $bcc BCC recipient email
     * @return object
     */
    public function bcc($bcc, $limit = '') {
        if ($this->driver) {
            $bcc = $this->_str_to_array($bcc);
            $names = $this->_extract_name($bcc);
            $bcc = $this->clean_email($bcc);

            if ($this->validate) {
                $this->validate_email($bcc);
            }

            foreach ($bcc as $address) {
                $this->driver->bcc($address);
            }
        } else {
            parent::bcc($bcc, $limit);
        }

        return $this;
    }

    /**
     * Set email subject
     * 
     * @param string $subject Email subject
     * @return object
     */
    public function subject($subject) {
        if ($this->driver) {
            $this->driver->subject($subject);
        } else {
            parent::subject($subject);
        }

        return $this;
    }

    /**
     * Set email message
     * 
     * @param string $message Email message
     * @return object
     */
    public function message($message) {
        if ($this->driver) {
            $this->driver->message($message);
        } else {
            parent::message($message);
        }

        return $this;
    }

    /**
     * Send email
     * 
     * @param bool $auto_clear Whether to clear after sending
     * @return bool Success status
     */
    public function send($auto_clear = true) {
        $auto_clear = !empty($auto_clear);

        if ($this->driver) {
            $result = $this->driver->send();

            if ($result) {
                $this->_set_error_message('lang:email_sent', $this->_get_protocol());
                if ($auto_clear) {
                    $this->clear();
                }
            } else {
                $error_info = $this->driver->getErrorInfo();
                $this->_set_error_message($error_info);
                
                // Log detailed error information
                log_message('error', 'Email send failed: ' . $error_info);
                log_message('error', 'Email driver: ' . $this->driver_name);
                log_message('error', 'SMTP Host: ' . $this->smtp_host);
                log_message('error', 'SMTP Port: ' . $this->smtp_port);
            }

            return $result;
        } else {
            return parent::send($auto_clear);
        }
    }

    /**
     * Print debugger messages
     * 
     * @return string Debug output
     */
    public function print_debugger($include = array('headers', 'subject', 'body')) {
        if ($this->driver) {
            $output = '';
            
            // Get error information
            $error_info = $this->driver->getErrorInfo();
            if (!empty($error_info)) {
                $output .= "Error Information:\n";
                $output .= $error_info . "\n\n";
            }
            
            // Get debug output from driver
            if (method_exists($this->driver, 'getDebugOutput')) {
                $debug_output = $this->driver->getDebugOutput();
                if (!empty($debug_output)) {
                    $output .= "Debug Output:\n";
                    $output .= $debug_output . "\n\n";
                }
            }
            
            // Add configuration information
            $output .= "Configuration:\n";
            $output .= "Driver: " . $this->driver_name . "\n";
            $output .= "SMTP Host: " . $this->smtp_host . "\n";
            $output .= "SMTP Port: " . $this->smtp_port . "\n";
            $output .= "SMTP User: " . $this->smtp_user . "\n";
            $output .= "SMTP Auth: " . ($this->smtp_auth ? 'Yes' : 'No') . "\n";
            $output .= "SMTP Crypto: " . $this->smtp_crypto . "\n";
            
            return $output;
        } else {
            return parent::print_debugger($include);
        }
    }

    /**
     * Set custom header
     * 
     * @param string $header Header name
     * @param string $value Header value
     * @return object
     */
    public function set_header($header, $value) {
        if ($this->driver) {
            $this->driver->setHeader($header, $value);
        } else {
            parent::set_header($header, $value);
        }

        return $this;
    }

    /**
     * Set email driver
     * 
     * @param string $driver_name Driver name
     * @return object
     */
    public function set_email_driver($driver_name) {
        $driver_name = strtolower($driver_name);
        
        // Map legacy useragent to driver name for backward compatibility
        if (strpos($driver_name, 'phpmailer') !== false) {
            $driver_name = 'smtp';
        } elseif (strpos($driver_name, 'codeigniter') !== false) {
            $driver_name = 'smtp'; // Default to SMTP instead of sendmail
        }

        if ($this->driver_name === $driver_name) {
            return $this;
        }

        $this->driver_name = $driver_name;
        $this->driver = EmailFactory::create($driver_name);

        if ($this->driver) {
            // Initialize driver with current configuration
            $config = $this->getDriverConfig();
            $this->driver->initialize($config);
        }

        $this->refresh_properties();
        $this->clear(true);

        return $this;
    }

    /**
     * Set mailer engine (legacy method for backward compatibility)
     * 
     * @param string $mailer_engine Mailer engine
     * @return object
     */
    public function set_mailer_engine($mailer_engine) {
        // Map legacy mailer engine to driver
        if (strpos(strtolower($mailer_engine), 'phpmailer') !== false) {
            return $this->set_email_driver('smtp');
        } else {
            return $this->set_email_driver('smtp'); // Default to SMTP instead of sendmail
        }
    }

    /**
     * Get driver-specific configuration
     * 
     * @return array Configuration array
     */
    protected function getDriverConfig() {
        $config = array();
        
        // Common configuration
        $config['charset'] = $this->charset;
        $config['mailtype'] = $this->mailtype;
        $config['newline'] = $this->newline;
        $config['crlf'] = $this->crlf;
        $config['wordwrap'] = $this->wordwrap;
        $config['wrapchars'] = $this->wrapchars;
        $config['priority'] = $this->priority;
        $config['encoding'] = $this->encoding;
        $config['validate'] = $this->validate;
        
        // SMTP-specific configuration
        if ($this->driver_name === 'smtp') {
            $config['smtp_host'] = $this->smtp_host;
            $config['smtp_auth'] = $this->smtp_auth;
            $config['smtp_user'] = $this->smtp_user;
            $config['smtp_pass'] = $this->smtp_pass;
            $config['smtp_port'] = $this->smtp_port;
            $config['smtp_timeout'] = $this->smtp_timeout;
            $config['smtp_keepalive'] = $this->smtp_keepalive;
            $config['smtp_crypto'] = $this->smtp_crypto;
            $config['smtp_auto_tls'] = $this->smtp_auto_tls;
            $config['smtp_conn_options'] = $this->smtp_conn_options;
            $config['smtp_debug'] = $this->smtp_debug;
            $config['debug_output'] = $this->debug_output;
            
            // DKIM configuration
            $config['dkim_domain'] = $this->dkim_domain;
            $config['dkim_private'] = $this->dkim_private;
            $config['dkim_private_string'] = $this->dkim_private_string;
            $config['dkim_selector'] = $this->dkim_selector;
            $config['dkim_passphrase'] = $this->dkim_passphrase;
            $config['dkim_identity'] = $this->dkim_identity;
        }
        
        return $config;
    }

    /**
     * Get current driver instance
     * 
     * @return EmailInterface|null
     */
    public function getDriver() {
        return $this->driver;
    }

    /**
     * Get current driver name
     * 
     * @return string
     */
    public function getDriverName() {
        return $this->driver_name;
    }

    /**
     * Get PHPMailer instance (for backward compatibility)
     * 
     * @return PHPMailer|null
     */
    public function getPhpMailer() {
        if ($this->driver && $this->driver instanceof SmtpDriver) {
            return $this->driver->getPhpMailer();
        }
        return null;
    }

    /**
     * Setter methods for configuration properties
     */
    public function set_useragent($useragent) {
        $this->useragent = $useragent;
        return $this->set_mailer_engine($useragent);
    }

    public function set_protocol($protocol = 'mail') {
        $this->protocol = $protocol;
        return $this;
    }

    public function set_smtp_host($smtp_host) {
        $this->smtp_host = $smtp_host;
        return $this;
    }

    public function set_smtp_auth($smtp_auth) {
        $this->smtp_auth = $smtp_auth;
        return $this;
    }

    public function set_smtp_user($smtp_user) {
        $this->smtp_user = $smtp_user;
        return $this;
    }

    public function set_smtp_pass($smtp_pass) {
        $this->smtp_pass = $smtp_pass;
        return $this;
    }

    public function set_smtp_port($smtp_port) {
        $this->smtp_port = $smtp_port;
        return $this;
    }

    public function set_smtp_timeout($smtp_timeout) {
        $this->smtp_timeout = $smtp_timeout;
        return $this;
    }

    public function set_smtp_keepalive($smtp_keepalive) {
        $this->smtp_keepalive = $smtp_keepalive;
        return $this;
    }

    public function set_smtp_crypto($smtp_crypto) {
        $this->smtp_crypto = $smtp_crypto;
        return $this;
    }

    public function set_smtp_auto_tls($smtp_auto_tls) {
        $this->smtp_auto_tls = $smtp_auto_tls;
        return $this;
    }

    public function set_smtp_conn_options($smtp_conn_options) {
        $this->smtp_conn_options = $smtp_conn_options;
        return $this;
    }

    public function set_smtp_debug($smtp_debug) {
        $this->smtp_debug = $smtp_debug;
        return $this;
    }

    public function set_debug_output($debug_output) {
        $this->debug_output = $debug_output;
        return $this;
    }

    public function set_wordwrap($wordwrap = true) {
        $this->wordwrap = $wordwrap;
        return $this;
    }

    public function set_wrapchars($wrapchars) {
        $this->wrapchars = $wrapchars;
        return $this;
    }

    public function set_mailtype($type = 'text') {
        $this->mailtype = $type;
        return $this;
    }

    public function set_charset($charset) {
        $this->charset = $charset;
        return $this;
    }

    public function set_multipart($multipart) {
        $this->multipart = $multipart;
        return $this;
    }

    public function set_alt_message($str) {
        $this->alt_message = $str;
        return $this;
    }

    public function set_validate($validate) {
        $this->validate = $validate;
        return $this;
    }

    public function set_priority($n = 3) {
        $this->priority = $n;
        return $this;
    }

    public function set_newline($newline = "\n") {
        $this->newline = $newline;
        return $this;
    }

    public function set_crlf($crlf = "\n") {
        $this->crlf = $crlf;
        return $this;
    }

    public function set_dsn($dsn) {
        $this->dsn = $dsn;
        return $this;
    }

    public function set_send_multipart($send_multipart) {
        $this->send_multipart = $send_multipart;
        return $this;
    }

    public function set_bcc_batch_mode($bcc_batch_mode) {
        $this->bcc_batch_mode = $bcc_batch_mode;
        return $this;
    }

    public function set_bcc_batch_size($bcc_batch_size) {
        $this->bcc_batch_size = $bcc_batch_size;
        return $this;
    }

    public function set_encoding($encoding) {
        $this->encoding = $encoding;
        return $this;
    }

    public function set_dkim_domain($dkim_domain) {
        $this->dkim_domain = $dkim_domain;
        return $this;
    }

    public function set_dkim_private($dkim_private) {
        $this->dkim_private = $dkim_private;
        return $this;
    }

    public function set_dkim_private_string($dkim_private_string) {
        $this->dkim_private_string = $dkim_private_string;
        return $this;
    }

    public function set_dkim_selector($dkim_selector) {
        $this->dkim_selector = $dkim_selector;
        return $this;
    }

    public function set_dkim_passphrase($dkim_passphrase) {
        $this->dkim_passphrase = $dkim_passphrase;
        return $this;
    }

    public function set_dkim_identity($dkim_identity) {
        $this->dkim_identity = $dkim_identity;
        return $this;
    }
}
