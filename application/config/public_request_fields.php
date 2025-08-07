<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Public Request Fields Configuration
 * 
 * This file allows you to define custom fields for public data access request forms.
 * Each field can be configured with various properties like type, validation, etc.
 * 
 * Field Types Supported:
 * - text: Single line text input
 * - textarea: Multi-line text input
 * - select: Dropdown with predefined options
 * - checkbox: Boolean field
 * - radio: Radio button group
 * - email: Email input with validation
 * - number: Numeric input
 * - date: Date picker
 * - url: URL input with validation
 * - phone: Phone number input
 * 
 * Database Field Types (data_type):
 * - string: Variable length string (VARCHAR(500))
 * - text: Long text field (TEXT)
 * - int: Integer field (INT)
 * 
 * Validation Rules Supported:
 * - required: Field is mandatory
 * - email: Valid email format
 * - url: Valid URL format
 * - numeric: Numeric values only
 * - min_length[X]: Minimum character length
 * - max_length[X]: Maximum character length
 * - alpha: Alphabetic characters only
 * - alpha_numeric: Alphanumeric characters only
 * - alpha_dash: Alphanumeric with dashes and underscores
 * - regex_match[pattern]: Custom regex pattern
 */

$config['public_request_fields'] = array(
    
    // Example: Research Institution
    'institution' => array(
        'name' => 'institution',
        'type' => 'text',
        'title' => 'Research Institution',
        'required' => true,
        'validation' => 'required|max_length[150]',
        'help_text' => 'Name of your research institution or organization',
        'placeholder' => 'Enter institution name',
        'order' => 1,
        'enable' => false,
        'data_type' => 'string',
    ),
    
    // Example: Research Type
    'research_type' => array(
        'name' => 'research_type',
        'type' => 'select',
        'title' => 'Type of Research',
        'required' => true,
        'validation' => 'required',
        'help_text' => 'Select the type of research you will be conducting',
        'enum' => array(
            'academic' => 'Academic Research',
            'policy' => 'Policy Analysis',
            'commercial' => 'Commercial Research',
            'government' => 'Government Research',
            'ngo' => 'NGO/Non-profit Research',
            'other' => 'Other'
        ),
        'order' => 2,
        'enable' => false,
        'data_type' => 'string',
    ),
    
   
);


/* End of file public_request_fields.php */ 