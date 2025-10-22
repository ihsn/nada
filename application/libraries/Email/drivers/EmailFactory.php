<?php

namespace Email\Drivers;

defined('BASEPATH') OR exit('No direct script access allowed');

// Include driver files
require_once(__DIR__ . '/EmailInterface.php');
require_once(__DIR__ . '/SmtpDriver.php');
require_once(__DIR__ . '/SendgridDriver.php');
require_once(__DIR__ . '/SendgridApiDriver.php');

/**
 * Email Factory
 * 
 * Factory class for creating email driver instances.
 * Handles driver instantiation and provides fallback mechanisms.
 */
class EmailFactory {
    
    /**
     * Available email drivers
     * 
     * @var array
     */
    private static $available_drivers = array(
        'smtp' => 'Email\\Drivers\\SmtpDriver',
        'sendgrid' => 'Email\\Drivers\\SendgridDriver',
        'sendgrid_smtp' => 'Email\\Drivers\\SendgridDriver',
        'sendgrid_api' => 'Email\\Drivers\\SendgridApiDriver',
        'microsoft_graph' => 'Email\\Drivers\\MicrosoftGraphDriver'
    );
    
    /**
     * Default driver
     * 
     * @var string
     */
    private static $default_driver = 'smtp';
    
    /**
     * Create email driver instance
     * 
     * @param string $driver_name Driver name
     * @return EmailDriverInterface|false Driver instance or false on failure
     */
    public static function create($driver_name = null) {
        // Use default driver if none specified
        if ($driver_name === null) {
            $driver_name = self::$default_driver;
        }
        
        // Normalize driver name
        $driver_name = strtolower($driver_name);
        
        // Check if driver exists
        if (!isset(self::$available_drivers[$driver_name])) {
            log_message('error', 'Email driver "' . $driver_name . '" not found. Using default driver.');
            $driver_name = self::$default_driver;
        }
        
        $driver_class = self::$available_drivers[$driver_name];
        
        // Check if driver class exists
        if (!class_exists($driver_class)) {
            log_message('error', 'Email driver class "' . $driver_class . '" not found. Using default driver.');
            $driver_name = self::$default_driver;
            $driver_class = self::$available_drivers[$driver_name];
        }
        
        try {
            $driver = new $driver_class();
            
            // Verify driver implements the interface
            if (!($driver instanceof EmailInterface)) {
                log_message('error', 'Email driver "' . $driver_class . '" does not implement EmailInterface. Using default driver.');
                $driver_name = self::$default_driver;
                $driver_class = self::$available_drivers[$driver_name];
                $driver = new $driver_class();
            }
            
            log_message('info', 'Email driver "' . $driver_name . '" created successfully.');
            return $driver;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to create email driver "' . $driver_name . '": ' . $e->getMessage());
            
            // Try to fallback to default driver
            if ($driver_name !== self::$default_driver) {
                log_message('info', 'Falling back to default email driver: ' . self::$default_driver);
                return self::create(self::$default_driver);
            }
            
            return false;
        }
    }
    
    /**
     * Get list of available drivers
     * 
     * @return array Available drivers
     */
    public static function getAvailableDrivers() {
        return array_keys(self::$available_drivers);
    }
    
    /**
     * Check if driver is available
     * 
     * @param string $driver_name Driver name
     * @return bool True if available
     */
    public static function isDriverAvailable($driver_name) {
        $driver_name = strtolower($driver_name);
        return isset(self::$available_drivers[$driver_name]) && class_exists(self::$available_drivers[$driver_name]);
    }
    
    /**
     * Register a new driver
     * 
     * @param string $name Driver name
     * @param string $class Driver class name
     * @return bool Success status
     */
    public static function registerDriver($name, $class) {
        $name = strtolower($name);
        
        if (!class_exists($class)) {
            log_message('error', 'Cannot register email driver "' . $name . '": Class "' . $class . '" does not exist.');
            return false;
        }
        
        self::$available_drivers[$name] = $class;
        log_message('info', 'Email driver "' . $name . '" registered successfully.');
        return true;
    }
    
    /**
     * Get driver class name
     * 
     * @param string $driver_name Driver name
     * @return string|false Driver class name or false if not found
     */
    public static function getDriverClass($driver_name) {
        $driver_name = strtolower($driver_name);
        return isset(self::$available_drivers[$driver_name]) ? self::$available_drivers[$driver_name] : false;
    }
    
    /**
     * Set default driver
     * 
     * @param string $driver_name Driver name
     * @return bool Success status
     */
    public static function setDefaultDriver($driver_name) {
        $driver_name = strtolower($driver_name);
        
        if (!isset(self::$available_drivers[$driver_name])) {
            log_message('error', 'Cannot set default email driver "' . $driver_name . '": Driver not available.');
            return false;
        }
        
        self::$default_driver = $driver_name;
        log_message('info', 'Default email driver set to: ' . $driver_name);
        return true;
    }
    
    /**
     * Get default driver name
     * 
     * @return string Default driver name
     */
    public static function getDefaultDriver() {
        return self::$default_driver;
    }
    
    /**
     * Map legacy useragent to driver name
     * 
     * @param string $useragent Legacy useragent value
     * @return string Driver name
     */
    public static function mapUseragentToDriver($useragent) {
        $useragent = strtolower($useragent);
        
        // Map legacy useragent values to new driver names
        if (strpos($useragent, 'phpmailer') !== false) {
            return 'smtp';
        } elseif (strpos($useragent, 'codeigniter') !== false) {
            return 'sendmail';
        }
        
        // Default to SMTP
        return 'smtp';
    }
}
