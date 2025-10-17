<?php

namespace Email\Drivers;

defined('BASEPATH') OR exit('No direct script access allowed');

use PHPMailer\PHPMailer\PHPMailer;

class SendgridDriver implements EmailInterface {
    private $phpmailer;
    private $error_info = '';
    
    public function __construct() {
        $this->phpmailer = new PHPMailer();
        
        // Set default properties for SendGrid
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
            // Set SendGrid SMTP configuration
            if (isset($config['smtp_host']) && !empty($config['smtp_host'])) {
                $this->phpmailer->Host = $config['smtp_host'];
            } else {
                // Default SendGrid host
                $this->phpmailer->Host = 'smtp.sendgrid.net';
            }
            
            if (isset($config['smtp_port'])) {
                $this->phpmailer->Port = (int)$config['smtp_port'];
            } else {
                // Default SendGrid port
                $this->phpmailer->Port = 587;
            }
            
            if (isset($config['smtp_auth'])) {
                $this->phpmailer->SMTPAuth = (bool)$config['smtp_auth'];
            } else {
                $this->phpmailer->SMTPAuth = true; // SendGrid requires authentication
            }
            
            if (isset($config['smtp_user']) && !empty($config['smtp_user'])) {
                $this->phpmailer->Username = $config['smtp_user'];
            } else {
                // Default SendGrid username
                $this->phpmailer->Username = 'apikey';
            }
            
            if (isset($config['smtp_pass']) && !empty($config['smtp_pass'])) {
                $this->phpmailer->Password = $config['smtp_pass'];
            } elseif (isset($config['sendgrid_api_key']) && !empty($config['sendgrid_api_key'])) {
                // Use SendGrid API key as password
                $this->phpmailer->Password = $config['sendgrid_api_key'];
            }
            
            if (isset($config['smtp_crypto']) && !empty($config['smtp_crypto'])) {
                $this->phpmailer->SMTPSecure = $config['smtp_crypto'];
            } else {
                // Default SendGrid encryption
                $this->phpmailer->SMTPSecure = 'tls';
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
            if (is_array($email)) {
                foreach ($email as $addr) {
                    $this->phpmailer->addAddress($addr);
                }
            } else {
                $this->phpmailer->addAddress($email);
            }
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function cc($email) {
        try {
            if (is_array($email)) {
                foreach ($email as $addr) {
                    $this->phpmailer->addCC($addr);
                }
            } else {
                $this->phpmailer->addCC($email);
            }
            return $this;
        } catch (Exception $e) {
            $this->error_info = $e->getMessage();
            return $this;
        }
    }
    
    public function bcc($email) {
        try {
            if (is_array($email)) {
                foreach ($email as $addr) {
                    $this->phpmailer->addBCC($addr);
                }
            } else {
                $this->phpmailer->addBCC($email);
            }
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
            $this->phpmailer->Body = $message;
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
        $this->phpmailer->clearAddresses();
        $this->phpmailer->clearAttachments();
        $this->phpmailer->clearBCCs();
        $this->phpmailer->clearCCs();
        $this->phpmailer->clearReplyTos();
        $this->phpmailer->clearAllRecipients();
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
    }
    
    public function getErrorInfo() {
        return $this->error_info ?: $this->phpmailer->ErrorInfo;
    }
    
    public function getPhpMailer() {
        return $this->phpmailer;
    }
    
    public function print_debugger() {
        return $this->phpmailer->ErrorInfo;
    }
    
    public function setHeader($header, $value) {
        try {
            $this->phpmailer->addCustomHeader($header, $value);
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
}
