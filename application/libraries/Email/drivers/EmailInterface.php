<?php

namespace Email\Drivers;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Email Interface
 * 
 * Defines the standard interface that all email drivers must implement
 * to ensure consistent behavior across different email providers.
 */
interface EmailInterface {
    
    /**
     * Initialize the email driver with configuration
     * 
     * @param array $config Driver-specific configuration
     * @return bool Success status
     */
    public function initialize($config);
    
    /**
     * Set the sender email address and name
     * 
     * @param string $email Sender email address
     * @param string $name Sender name (optional)
     * @return EmailDriverInterface
     */
    public function from($email, $name = '');
    
    /**
     * Add recipient email address
     * 
     * @param string $email Recipient email address
     * @return EmailDriverInterface
     */
    public function to($email);
    
    /**
     * Add CC recipient email address
     * 
     * @param string $email CC recipient email address
     * @return EmailDriverInterface
     */
    public function cc($email);
    
    /**
     * Add BCC recipient email address
     * 
     * @param string $email BCC recipient email address
     * @return EmailDriverInterface
     */
    public function bcc($email);
    
    /**
     * Set email subject
     * 
     * @param string $subject Email subject
     * @return EmailDriverInterface
     */
    public function subject($subject);
    
    /**
     * Set email message body
     * 
     * @param string $message Email message body
     * @return EmailDriverInterface
     */
    public function message($message);
    
    /**
     * Send the email
     * 
     * @return bool Success status
     */
    public function send();
    
    /**
     * Clear all email data (recipients, subject, message, etc.)
     * 
     * @return EmailDriverInterface
     */
    public function clear();
    
    /**
     * Get error information if send failed
     * 
     * @return string Error message
     */
    public function getErrorInfo();
    
    /**
     * Set custom header
     * 
     * @param string $header Header name
     * @param string $value Header value
     * @return EmailDriverInterface
     */
    public function setHeader($header, $value);
    
    /**
     * Set character set
     * 
     * @param string $charset Character set
     * @return EmailDriverInterface
     */
    public function setCharset($charset);
    
    /**
     * Set mail type (html or text)
     * 
     * @param string $mailtype Mail type
     * @return EmailDriverInterface
     */
    public function setMailtype($mailtype);
    
    /**
     * Set newline character
     * 
     * @param string $newline Newline character
     * @return EmailDriverInterface
     */
    public function setNewline($newline);
    
    /**
     * Set debug mode
     * 
     * @param bool $debug Debug mode
     * @return EmailDriverInterface
     */
    public function setDebug($debug);
    
    /**
     * Get debug output
     * 
     * @return string Debug information
     */
    public function getDebugOutput();
}
