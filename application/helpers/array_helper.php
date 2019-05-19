<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 
 *  get plain text from multidimensional array
 * 
 */	
if ( ! function_exists('array_to_plain_text'))
{
	function array_to_plain_text($data)
	{
		$output = array();
        $item = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
        foreach($item as $value) {
            $output[] = $value;
        }  
        return implode(' ', $output);        
	}
}


/**
 * 
 * 
 * Return array value by key
 * 
 */
if ( ! function_exists('array_get_value'))
{
	function array_get_value($data,$key)
	{
        if(array_key_exists($key,$data)){
            return $data[$key];
        }
        return false;
    }
}


/**
 * 
 * 
 * return an array with the nested path and value
 * 
 * @data - array that needs to be searched
 * @path - xpath like path to data e.g. study_desc/title_statement/title or study_desc.title_statement.title 
 * 
 * 
 **/ 
if ( ! function_exists('get_array_nested_value'))
{
    function get_array_nested_value($data, $path, $glue = '/')
    {
        $paths = explode($glue, (string) $path);
        $reference = $data;
        foreach ($paths as $key) {
            if (!array_key_exists($key, $reference)) {
                return false;
            }
            $reference = $reference[$key];
        }
        return $reference;
    }
}