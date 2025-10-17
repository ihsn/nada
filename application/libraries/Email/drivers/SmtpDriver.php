<?php

namespace Email\Drivers;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SMTP Email Driver
 * 
 * Implements email sending using PHPMailer with SMTP protocol.
 * This driver maintains compatibility with the existing PHPMailer implementation.
 */

use PHPMailer\PHPMailer\PHPMailer;

class SmtpDriver implements EmailInterface {
    
    private $phpmailer;
    private $debug_output = '';
    private $error_info = '';
    
    public function __construct() {
        $this->phpmailer = new PHPMailer();
        
        // Set default properties
        $this->phpmailer->isSMTP();
        $this->phpmailer->CharSet = 'UTF-8';
        $this->phpmailer->Encoding = '8bit';
        $this->phpmailer->SMTPAutoTLS = true;
        $this->phpmailer->SMTPKeepAlive = false;
        $this->phpmailer->SMTPTimeout = 30;
        $this->phpmailer->WordWrap = true;
        $this->phpmailer->WrapChars = 76;
        $this->phpmailer->Priority = 3;
        $this->phpmailer->isHTML(false);
    }
    
    public function initialize($config) {
        try {
            // Set SMTP configuration
            if (isset($config['smtp_host']) && !empty($config['smtp_host'])) {
                $this->phpmailer->Host = $config['smtp_host'];
            }
            
            if (isset($config['smtp_port'])) {
                $this->phpmailer->Port = (int)$config['smtp_port'];
            }
            
            if (isset($config['smtp_auth'])) {
                $this->phpmailer->SMTPAuth = (bool)$config['smtp_auth'];
            }
            
            if (isset($config['smtp_user']) && !empty($config['smtp_user'])) {
                $this->phpmailer->Username = $config['smtp_user'];
            }
            
            if (isset($config['smtp_pass']) && !empty($config['smtp_pass'])) {
                $this->phpmailer->Password = $config['smtp_pass'];
            }
            
            if (isset($config['smtp_crypto']) && !empty($config['smtp_crypto'])) {
                $this->phpmailer->SMTPSecure = $config['smtp_crypto'];
            }
            
            if (isset($config['smtp_timeout'])) {
                $this->phpmailer->Timeout = (int)$config['smtp_timeout'];
            }
            
            if (isset($config['smtp_keepalive'])) {
                $this->phpmailer->SMTPKeepAlive = (bool)$config['smtp_keepalive'];
            }
            
            if (isset($config['smtp_auto_tls'])) {
                $this->phpmailer->SMTPAutoTLS = (bool)$config['smtp_auto_tls'];
            }
            
            if (isset($config['smtp_conn_options']) && is_array($config['smtp_conn_options'])) {
                $this->phpmailer->SMTPOptions = $config['smtp_conn_options'];
            }
            
            if (isset($config['smtp_debug'])) {
                $this->phpmailer->SMTPDebug = (int)$config['smtp_debug'];
            }
            
            if (isset($config['debug_output'])) {
                if ($config['debug_output'] === 'codeigniter') {
                    // Use CodeIgniter's logging system
                    $this->phpmailer->Debugoutput = function($str, $level) {
                        log_message('debug', 'PHPMailer: ' . $str);
                    };
                } else {
                    $this->phpmailer->Debugoutput = $config['debug_output'];
                }
            }
            
            // Set other configuration
            if (isset($config['charset'])) {
                $this->phpmailer->CharSet = $config['charset'];
            }
            
            if (isset($config['encoding'])) {
                $this->phpmailer->Encoding = $config['encoding'];
            }
            
            if (isset($config['wordwrap'])) {
                $this->phpmailer->WordWrap = (bool)$config['wordwrap'];
            }
            
            if (isset($config['wrapchars'])) {
                $this->phpmailer->WrapChars = (int)$config['wrapchars'];
            }
            
            if (isset($config['priority'])) {
                $this->phpmailer->Priority = (int)$config['priority'];
            }
            
            if (isset($config['mailtype'])) {
                $this->phpmailer->isHTML($config['mailtype'] === 'html');
            }
            
            // Note: Line ending (LE) is automatically set by PHPMailer based on mailer type
            // SMTP uses CRLF, mail() uses PHP_EOL
            // Manual setting of LE is not supported in PHPMailer 6.5
            
            // DKIM configuration
            if (isset($config['dkim_domain']) && !empty($config['dkim_domain'])) {
                $this->phpmailer->DKIM_domain = $config['dkim_domain'];
            }
            
            if (isset($config['dkim_private']) && !empty($config['dkim_private'])) {
                $this->phpmailer->DKIM_private = $config['dkim_private'];
            }
            
            if (isset($config['dkim_private_string']) && !empty($config['dkim_private_string'])) {
                $this->phpmailer->DKIM_private_string = $config['dkim_private_string'];
            }
            
            if (isset($config['dkim_selector']) && !empty($config['dkim_selector'])) {
                $this->phpmailer->DKIM_selector = $config['dkim_selector'];
            }
            
            if (isset($config['dkim_passphrase']) && !empty($config['dkim_passphrase'])) {
                $this->phpmailer->DKIM_passphrase = $config['dkim_passphrase'];
            }
            
            if (isset($config['dkim_identity']) && !empty($config['dkim_identity'])) {
                $this->phpmailer->DKIM_identity = $config['dkim_identity'];
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return false;
        }
    }
    
    public function from($email, $name = '') {
        try {
            $this->phpmailer->setFrom($email, $name);
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function to($email) {
        try {
            $this->phpmailer->addAddress($email);
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function cc($email) {
        try {
            $this->phpmailer->addCC($email);
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function bcc($email) {
        try {
            $this->phpmailer->addBCC($email);
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function subject($subject) {
        try {
            $this->phpmailer->Subject = $subject;
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function message($message) {
        try {
            if ($this->phpmailer->isHTML()) {
                $this->phpmailer->Body = $message;
                // Set AltBody for HTML emails
                $alt_message = strip_tags($message);
                $alt_message = str_replace(array('{unwrap}', '{/unwrap}'), '', $alt_message);
                $this->phpmailer->AltBody = $alt_message;
            } else {
                $this->phpmailer->Body = $message;
            }
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function send() {
        try {
            $result = $this->phpmailer->send();
            if (!$result) {
                $this->error_info = $this->phpmailer->ErrorInfo;
            }
            return $result;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return false;
        }
    }
    
    public function clear() {
        try {
            $this->phpmailer->clearAddresses();
            $this->phpmailer->clearCCs();
            $this->phpmailer->clearBCCs();
            $this->phpmailer->clearReplyTos();
            $this->phpmailer->clearAllRecipients();
            $this->phpmailer->clearAttachments();
            $this->phpmailer->clearCustomHeaders();
            
            // Clear FROM address
            $this->phpmailer->From = '';
            $this->phpmailer->FromName = '';
            $this->phpmailer->Sender = '';
            
            $this->phpmailer->Subject = '';
            $this->phpmailer->Body = '';
            $this->phpmailer->AltBody = '';
            $this->error_info = '';
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function getErrorInfo() {
        return $this->error_info ?: $this->phpmailer->ErrorInfo;
    }
    
    public function setHeader($header, $value) {
        try {
            $this->phpmailer->addCustomHeader($header, str_replace(array("\n", "\r"), '', $value));
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function setCharset($charset) {
        try {
            $this->phpmailer->CharSet = $charset;
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function setMailtype($mailtype) {
        try {
            $this->phpmailer->isHTML($mailtype === 'html');
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function setNewline($newline) {
        // Note: Line ending setting is not supported in PHPMailer 6.5
        // PHPMailer automatically sets appropriate line endings based on mailer type
        // SMTP uses CRLF, mail() uses PHP_EOL
        return $this;
    }
    
    public function setDebug($debug) {
        try {
            $this->phpmailer->SMTPDebug = $debug ? 2 : 0;
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function getDebugOutput() {
        return $this->phpmailer->ErrorInfo;
    }
    
    /**
     * Get the underlying PHPMailer instance for advanced usage
     * 
     * @return PHPMailer
     */
    public function getPhpMailer() {
        return $this->phpmailer;
    }
}
