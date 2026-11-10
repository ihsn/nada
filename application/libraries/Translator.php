<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * NADA language translator (Beta)
 * 
 */
class Translator{
	
	
	var $ci;
	var $config = array();
	var $is_loaded = array();
	
    //constructor
	function __construct()
	{
		$this->ci =& get_instance();
    }
	
	
	/**
	*
	* Loads a language file 
	*
 	* TODO:// copied from the CI translator, need to find the author to give credit
	**/
	function load($file)
	{	
		$file=$file.'_lang.php';
	
		if ( ! file_exists(APPPATH.'language/'.$file))
		{
				return FALSE;
		}
	

		include(APPPATH.'language/'.$file);

		if ( ! isset($lang) OR ! is_array($lang))
		{
				return FALSE;
		}
		
		return $lang;
	}
	
	
	/**
	 * List language files
	 *
	 * @return array
	 */
	function get_language_files_array($language_path ) {
		
		$modules = array();
		
		$dir = $language_path;

		$d = @dir( $dir );

		if ( $d ) {
			while (false !== ($entry = $d->read())) {
			   	$file = $dir . '/' . $entry;
				if ( is_file( $file ) ) {
					$path_parts = pathinfo( $file );
					if ( $path_parts[ 'extension' ] == 'php' ) {
						$modules[] = $entry;
					}
			   }
			}
			$d->close();
		} else {
				return FALSE;
		}

		sort($modules);		
		return $modules;		
	}
	
	
	//returns an array of merge source and target language translations
	function merge_language_keys($source_file,$target_file)
	{		
		//load the translations from language file into an array
		$source_lang_arr=(array)$this->get_translations_array($source_file);
		$target_lang_arr=(array)$this->get_translations_array($target_file);
		
		foreach($source_lang_arr as $key=>$value)
		{
			//add missing translations to the target file 
			if (!array_key_exists($key,$target_lang_arr))
			{
				$target_lang_arr[$key]=$value;
			}
		}
		
		return $target_lang_arr;		
	}
	
	
	//get language translations key/values as array
	function get_translations_array($file_path)
	{		
		$lang=NULL;
		
		if (file_exists($file_path))
		{
			//fills the values in a local variable $lang
			include $file_path;
		}	
		
		return $lang;
	}
	
	
	/**
	 * Returns an array of all available language names.
	 *
	 * Merges languages found in application/language/ and userdata/language/.
	 * English is always listed first (it is the base language).
	 * Folders named 'base', 'index.html', 'index.php', '.DS_Store' are excluded.
	 *
	 * @return array  Flat array of language name strings e.g. ['english','french','spanish']
	 **/
	function get_languages_array()
	{
		$excluded = array('.', '..', 'base', '.DS_Store', 'index.html', 'index.php');

		// Scan application/language/
		$app_folder = APPPATH . 'language';
		$app_langs  = array();
		if (is_dir($app_folder))
		{
			foreach (scandir($app_folder) as $entry)
			{
				if (!in_array($entry, $excluded) && is_dir($app_folder . '/' . $entry))
				{
					$app_langs[] = $entry;
				}
			}
		}

		// Scan userdata/language/ and merge
		$user_langs = array();
		$user_data  = $this->ci->config->item('userdata_path');
		if (!empty($user_data))
		{
			$user_folder = $user_data . '/language';
			if (is_dir($user_folder))
			{
				foreach (scandir($user_folder) as $entry)
				{
					if (!in_array($entry, $excluded) && is_dir($user_folder . '/' . $entry))
					{
						$user_langs[] = $entry;
					}
				}
			}
		}

		$all = array_values(array_unique(array_merge($app_langs, $user_langs)));
		sort($all);

		// Pin English first as it is the base language
		$english_key = array_search('english', $all);
		if ($english_key !== false)
		{
			array_splice($all, $english_key, 1);
			array_unshift($all, 'english');
		}

		return $all;
	}
	
	// Check if a language folder exists in either application/language or userdata/language
	function language_exists($lang_name)
	{
		return in_array($lang_name, $this->get_languages_array());
	}
	
	
	/**
	 * Whether a translation file exists in userdata or application/language.
	 *
	 * @param  string $language
	 * @param  string $translation_file  base name without _lang.php
	 * @return bool
	 */
	function translation_file_exists($language, $translation_file)
	{
		return $this->translation_file_path($language, $translation_file, false) !== false;
	}


	/**
	 * Validate a language folder name (lowercase letters, digits, underscore).
	 *
	 * @param  string $lang
	 * @return bool
	 */
	function is_valid_language_name($lang)
	{
		return (bool) preg_match('/^[a-z][a-z0-9_]{1,29}$/', (string) $lang);
	}


	/**
	 * True when a translation value is considered missing (empty string).
	 *
	 * @param  mixed $value
	 * @return bool
	 */
	function is_missing_value($value)
	{
		return ! is_string($value) || $value === '';
	}


	/**
	 * English language file basename whitelist: "general" => "general_lang.php".
	 *
	 * @return array
	 */
	function get_english_file_map()
	{
		$files = $this->get_language_files_array(APPPATH . 'language/english');
		$map = array();
		if (! is_array($files)) {
			return $map;
		}
		foreach ($files as $f) {
			if (substr($f, -9) !== '_lang.php') {
				continue;
			}
			$map[str_replace('_lang.php', '', $f)] = $f;
		}
		return $map;
	}


	/**
	 * Resolve english PHP filename for a base name, or false.
	 *
	 * @param  string $file_base  e.g. "general"
	 * @return string|false
	 */
	function resolve_english_filename($file_base)
	{
		$map = $this->get_english_file_map();
		return isset($map[$file_base]) ? $map[$file_base] : false;
	}


	/**
	 * Load English keys for a file base name.
	 *
	 * @param  string $file_base
	 * @return array|false
	 */
	function get_english_translations($file_base)
	{
		$filename = $this->resolve_english_filename($file_base);
		if ($filename === false) {
			return false;
		}
		$data = $this->get_translations_array(APPPATH . 'language/english/' . $filename);
		return is_array($data) ? $data : false;
	}


	/**
	 * Effective translations for a language file (userdata overrides application).
	 *
	 * @param  string $language
	 * @param  string $file_base
	 * @return array  key => string (may be empty)
	 */
	function get_effective_translations($language, $file_base)
	{
		$filename = $this->resolve_english_filename($file_base);
		if ($filename === false) {
			return array();
		}

		$merged = array();
		$app = $this->get_translations_array(APPPATH . 'language/' . $language . '/' . $filename);
		if (is_array($app)) {
			$merged = $app;
		}

		$user_data = $this->ci->config->item('userdata_path');
		if (! empty($user_data)) {
			$user = $this->get_translations_array($user_data . '/language/' . $language . '/' . $filename);
			if (is_array($user)) {
				foreach ($user as $k => $v) {
					$merged[$k] = $v;
				}
			}
		}

		return $merged;
	}


	/**
	 * Generate PHP language file contents from a key => value map.
	 * Empty values are omitted so application/language can fill gaps.
	 *
	 * @param  array  $translations
	 * @param  string $language_file  base name for footer comment
	 * @param  string $language
	 * @return string
	 */
	function generate_php_content(array $translations, $language_file = '', $language = '')
	{
		$php = "<?php\n";
		foreach ($translations as $key => $value) {
			if (! is_string($key) || $this->is_missing_value($value)) {
				continue;
			}
			$php .= '$lang[\'' . addslashes($key) . '\'] = \'' . addslashes($value) . '\';' . "\n";
		}
		if ($language_file !== '') {
			$php .= "\n/* End of file " . $language_file . " */\n";
			if ($language !== '') {
				$php .= '/* Location: ./userdata/language/' . $language . '/' . $language_file . "_lang.php */\n";
			}
		}
		return $php;
	}


	/**
	 * Write translations to the userdata save path (never application/language).
	 *
	 * @param  string $language
	 * @param  string $file_base
	 * @param  array  $translations  key => value (only English keys; empty omitted)
	 * @return array  ['type'=>'success'|'error','msg'=>string,'path'=>string]
	 */
	function save_translations($language, $file_base, array $translations)
	{
		if (! $this->is_valid_language_name($language) || ! $this->language_exists($language)) {
			return array('type' => 'error', 'msg' => 'INVALID_LANGUAGE', 'path' => '');
		}

		$en = $this->get_english_translations($file_base);
		if ($en === false) {
			return array('type' => 'error', 'msg' => 'INVALID_FILE', 'path' => '');
		}

		$filtered = array();
		foreach ($en as $key => $en_val) {
			if (! array_key_exists($key, $translations)) {
				continue;
			}
			$val = $translations[$key];
			if (! is_string($val) && ! is_numeric($val)) {
				continue;
			}
			$val = (string) $val;
			if (strpos($val, "\0") !== false) {
				return array('type' => 'error', 'msg' => 'INVALID_VALUE_NULL_BYTE: ' . $key, 'path' => '');
			}
			if (strlen($val) > 10240) {
				return array('type' => 'error', 'msg' => 'VALUE_TOO_LONG: ' . $key, 'path' => '');
			}
			if (! $this->is_missing_value($val)) {
				$filtered[$key] = $val;
			}
		}

		$output_file = $this->translation_file_path($language, $file_base, true);
		if ($output_file === false || $output_file === '') {
			return array('type' => 'error', 'msg' => 'SAVE_PATH_UNAVAILABLE', 'path' => '');
		}

		$dir = dirname($output_file);
		if (! is_dir($dir) && ! @mkdir($dir, 0755, true)) {
			return array('type' => 'error', 'msg' => 'Could not create language folder: ' . $dir, 'path' => $output_file);
		}

		if (file_exists($output_file)) {
			@copy($output_file, $output_file . '.bak');
		}

		$php = $this->generate_php_content($filtered, $file_base, $language);
		if (@file_put_contents($output_file, $php) === false) {
			return array('type' => 'error', 'msg' => 'Could not save file. ' . $output_file, 'path' => $output_file);
		}

		return array('type' => 'success', 'msg' => 'File has been saved. ' . $output_file, 'path' => $output_file);
	}


	/**
	 * Delete userdata override for one language file (official app file remains).
	 *
	 * @param  string $language
	 * @param  string $file_base
	 * @return array  ['type'=>'success'|'error','msg'=>string]
	 */
	function reset_userdata_file($language, $file_base)
	{
		if (! $this->is_valid_language_name($language) || ! $this->language_exists($language)) {
			return array('type' => 'error', 'msg' => 'INVALID_LANGUAGE');
		}
		if ($this->resolve_english_filename($file_base) === false) {
			return array('type' => 'error', 'msg' => 'INVALID_FILE');
		}

		$user_data = $this->ci->config->item('userdata_path');
		if (empty($user_data)) {
			return array('type' => 'error', 'msg' => 'userdata_path is not configured.');
		}

		$path = $user_data . '/language/' . $language . '/' . $file_base . '_lang.php';
		if (! file_exists($path)) {
			return array('type' => 'error', 'msg' => 'No userdata override for this file.');
		}

		if (! @unlink($path)) {
			return array('type' => 'error', 'msg' => 'Could not delete userdata file.');
		}

		@unlink($path . '.bak');

		return array('type' => 'success', 'msg' => 'Userdata override removed.');
	}


	/**
	 * Delete a custom language folder from userdata (not allowed for official languages).
	 *
	 * @param  string $language
	 * @return array
	 */
	function delete_language($language)
	{
		if (! $this->is_valid_language_name($language)) {
			return array('type' => 'error', 'msg' => 'INVALID_LANGUAGE');
		}
		if ($language === 'english') {
			return array('type' => 'error', 'msg' => 'Cannot delete the base language.');
		}

		$info = $this->get_language_info($language);
		if ($info['has_official']) {
			return array('type' => 'error', 'msg' => 'Cannot delete an official language. Reset userdata overrides instead.');
		}
		if (! $info['has_userdata']) {
			return array('type' => 'error', 'msg' => 'Language not found.');
		}

		$user_data = $this->ci->config->item('userdata_path');
		$folder = $user_data . '/language/' . $language;
		if (! $this->_delete_directory($folder)) {
			return array('type' => 'error', 'msg' => 'Could not delete language folder.');
		}

		return array('type' => 'success', 'msg' => 'Language "' . $language . '" deleted.');
	}


	/**
	 * Per-file status for a language vs English.
	 *
	 * @param  string $language
	 * @return array  [file_base => ['complete'=>bool,'total'=>int,'translated'=>int,'missing'=>int,'has_userdata'=>bool]]
	 */
	function get_files_status($language)
	{
		$out = array();
		$map = $this->get_english_file_map();
		$user_data = $this->ci->config->item('userdata_path');

		foreach ($map as $base => $filename) {
			$en = $this->get_translations_array(APPPATH . 'language/english/' . $filename);
			if (! is_array($en) || empty($en)) {
				$out[$base] = array(
					'complete' => true,
					'total' => 0,
					'translated' => 0,
					'missing' => 0,
					'has_userdata' => false,
				);
				continue;
			}

			$effective = $this->get_effective_translations($language, $base);
			$translated = 0;
			foreach ($en as $key => $en_val) {
				$val = array_key_exists($key, $effective) ? $effective[$key] : '';
				if (! $this->is_missing_value(is_string($val) || is_numeric($val) ? (string) $val : '')) {
					$translated++;
				}
			}
			$total = count($en);
			$missing = $total - $translated;
			$has_userdata = ! empty($user_data)
				&& file_exists($user_data . '/language/' . $language . '/' . $filename);

			$out[$base] = array(
				'complete' => $missing === 0,
				'total' => $total,
				'translated' => $translated,
				'missing' => $missing,
				'has_userdata' => $has_userdata,
			);
		}

		return $out;
	}


	/**
	 * Entries for one language file (English + effective translation).
	 *
	 * @param  string $language
	 * @param  string $file_base
	 * @return array|false
	 */
	function get_file_entries($language, $file_base)
	{
		$en = $this->get_english_translations($file_base);
		if ($en === false) {
			return false;
		}

		$effective = $this->get_effective_translations($language, $file_base);
		$entries = array();
		foreach ($en as $key => $english) {
			$translation = array_key_exists($key, $effective)
				? (string) $effective[$key]
				: '';
			$entries[] = array(
				'key' => (string) $key,
				'english' => (string) $english,
				'translation' => $translation,
				'missing' => $this->is_missing_value($translation),
			);
		}

		$user_data = $this->ci->config->item('userdata_path');
		$filename = $file_base . '_lang.php';
		$has_userdata = ! empty($user_data)
			&& file_exists($user_data . '/language/' . $language . '/' . $filename);

		return array(
			'file' => $file_base,
			'language' => $language,
			'path' => $this->translation_file_path($language, $file_base, true),
			'has_userdata' => $has_userdata,
			'entries' => $entries,
		);
	}


	/**
	 * Search keys / English / translations across all files.
	 *
	 * @param  string $language
	 * @param  string $q
	 * @param  bool   $only_missing
	 * @param  int    $limit
	 * @return array  ['results'=>[], 'truncated'=>bool, 'limit'=>int]
	 */
	function search_language($language, $q = '', $only_missing = false, $limit = 500)
	{
		$q = mb_substr(trim((string) $q), 0, 100);
		$limit = max(1, min(2000, (int) $limit));
		$results = array();
		$truncated = false;
		$map = $this->get_english_file_map();

		foreach ($map as $base => $filename) {
			$en = $this->get_translations_array(APPPATH . 'language/english/' . $filename);
			if (! is_array($en)) {
				continue;
			}
			$tr = $this->get_effective_translations($language, $base);

			foreach ($en as $key => $en_value) {
				$tr_value = array_key_exists($key, $tr) ? (string) $tr[$key] : '';
				$en_value = (string) $en_value;
				$is_missing = $this->is_missing_value($tr_value);

				if ($only_missing && ! $is_missing) {
					continue;
				}

				if ($q !== ''
					&& mb_stripos((string) $key, $q) === false
					&& mb_stripos($en_value, $q) === false
					&& mb_stripos($tr_value, $q) === false
				) {
					continue;
				}

				if (count($results) >= $limit) {
					$truncated = true;
					break 2;
				}

				$results[] = array(
					'file' => $base,
					'key' => (string) $key,
					'english' => $en_value,
					'translation' => $tr_value,
					'missing' => $is_missing,
				);
			}
		}

		return array(
			'results' => $results,
			'truncated' => $truncated,
			'limit' => $limit,
		);
	}


	/**
	 * Recursively delete a directory.
	 *
	 * @param  string $dir
	 * @return bool
	 */
	function _delete_directory($dir)
	{
		if (! is_dir($dir)) {
			return false;
		}
		$items = scandir($dir);
		if ($items === false) {
			return false;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$path = $dir . '/' . $item;
			if (is_dir($path)) {
				if (! $this->_delete_directory($path)) {
					return false;
				}
			}
			elseif (! @unlink($path)) {
				return false;
			}
		}
		return @rmdir($dir);
	}


	/**
	 * Return the translation file path.
	 *
	 * Save path ($ignore_exists=true):
	 *   Always returns userdata/language/{lang}/{file} when userdata_path is configured.
	 *   The UI never writes to application/language/.
	 *
	 * Read path ($ignore_exists=false):
	 *   Checks userdata/language first, falls back to application/language.
	 *
	 * @param  string $language
	 * @param  string $translation_file  base name without _lang.php
	 * @param  bool   $ignore_exists     true = return save path (may not exist yet)
	 * @return string|false
	 */
	function translation_file_path($language, $translation_file, $ignore_exists=false)
	{
		$user_data = $this->ci->config->item('userdata_path');

		// --- Save path: always target userdata when configured ---
		if ($ignore_exists === true && !empty($user_data))
		{
			return $user_data . '/language/' . $language . '/' . $translation_file . '_lang.php';
		}

		// --- Read path: userdata first, then application ---
		if (!empty($user_data))
		{
			$user_file = $user_data . '/language/' . $language . '/' . $translation_file . '_lang.php';
			if (file_exists($user_file))
			{
				return $user_file;
			}
		}

		$fullpath = APPPATH . 'language/' . $language . '/' . $translation_file . '_lang.php';
		if (file_exists($fullpath))
		{
			return $fullpath;
		}

		if ($ignore_exists === true)
		{
			return $fullpath; // return path even if it doesn't exist yet
		}

		return false;
	}

	function get_language_folder()
	{
		//custom user language folder
		$user_data=$this->ci->config->item('userdata_path');
		
		if(!empty($user_data) && file_exists($user_data.'/language')){
			return $user_data.'/language';
		}

		//default language folder
		return 'application/language';
	}


	/**
	 * Return source flags for a language.
	 *
	 * @param  string $lang
	 * @return array  ['has_official' => bool, 'has_userdata' => bool]
	 */
	function get_language_info($lang)
	{
		$user_data = $this->ci->config->item('userdata_path');
		return array(
			'has_official' => is_dir(APPPATH . 'language/' . $lang),
			'has_userdata' => !empty($user_data) && is_dir($user_data . '/language/' . $lang),
		);
	}


	/**
	 * Create a new language folder in userdata/language/.
	 *
	 * Only userdata is written; application/language is never touched.
	 *
	 * @param  string $lang  Lowercase name, e.g. 'arabic'
	 * @return array  ['type' => 'success'|'error', 'msg' => string]
	 */
	function create_language($lang)
	{
		if (! $this->is_valid_language_name($lang))
		{
			return array('type' => 'error', 'msg' => 'Invalid language name. Use lowercase letters, digits and underscores only (2–30 characters).');
		}

		$user_data = $this->ci->config->item('userdata_path');
		if (empty($user_data))
		{
			return array('type' => 'error', 'msg' => 'userdata_path is not configured.');
		}

		$lang_folder = $user_data . '/language/' . $lang;

		if (is_dir($lang_folder))
		{
			return array('type' => 'error', 'msg' => 'Language "' . $lang . '" already exists.');
		}

		if (!mkdir($lang_folder, 0755, true))
		{
			return array('type' => 'error', 'msg' => 'Could not create language folder: ' . $lang_folder);
		}

		return array('type' => 'success', 'msg' => 'Language "' . $lang . '" created successfully.');
	}


	/**
	 * Get completeness statistics for a language compared to English.
	 *
	 * Returns an array with:
	 *   total         – total keys in English
	 *   translated    – keys present in the target language
	 *   missing_files – English files that have no counterpart at all
	 *   percent       – integer 0-100
	 *
	 * Returns null for English (it is the base language).
	 *
	 * @param  string $language
	 * @return array|null
	 */
	function get_language_completeness($language)
	{
		if ($language === 'english')
		{
			return null;
		}

		$english_files = $this->get_language_files_array(APPPATH.'language/english');

		if (!is_array($english_files))
		{
			return null;
		}

		$total_keys    = 0;
		$translated    = 0;
		$missing_files = array();

		$user_data = $this->ci->config->item('userdata_path');

		foreach ($english_files as $file)
		{
			$en_data = $this->get_translations_array(APPPATH.'language/english/'.$file);

			if (!is_array($en_data))
			{
				continue;
			}

			$total_keys += count($en_data);

			// Try userdata path first, then application language folder
			$lang_data = null;

			if (!empty($user_data))
			{
				$lang_data = $this->get_translations_array($user_data.'/language/'.$language.'/'.$file);
			}

			if (!is_array($lang_data))
			{
				$lang_data = $this->get_translations_array(APPPATH.'language/'.$language.'/'.$file);
			}

			if (!is_array($lang_data))
			{
				$missing_files[] = str_replace('_lang.php', '', $file);
			}
			else
			{
				foreach ($en_data as $key => $en_val)
				{
					if (array_key_exists($key, $lang_data) && ! $this->is_missing_value((string) $lang_data[$key]))
					{
						$translated++;
					}
				}
			}
		}

		return array(
			'total'         => $total_keys,
			'translated'    => $translated,
			'missing_files' => $missing_files,
			'percent'       => $total_keys > 0 ? (int) round(($translated / $total_keys) * 100) : 0,
		);
	}


	/**
	 * Export an effective language package as a zip of JSON files.
	 *
	 * Each PHP language file is converted to a JSON object {"key":"value"}.
	 * The zip structure is: {lang}/{base}_lang.json
	 * e.g. french/general_lang.json
	 *
	 * The effective translation per file is: userdata first, then application/language.
	 *
	 * @param string $language
	 */
	function export($language)
	{
		if (!$this->language_exists($language))
		{
			show_error('INVALID LANGUAGE: ' . htmlspecialchars($language));
		}

		$user_data = $this->ci->config->item('userdata_path');

		$english_files = $this->get_language_files_array(APPPATH . 'language/english');
		if (!is_array($english_files))
		{
			show_error('Could not read English language folder.');
		}

		$this->ci->load->library('zip');

		foreach ($english_files as $file)
		{
			// Resolve effective file: userdata first, then application/language
			$source = null;

			if (!empty($user_data))
			{
				$candidate = $user_data . '/language/' . $language . '/' . $file;
				if (file_exists($candidate))
				{
					$source = $candidate;
				}
			}

			if ($source === null)
			{
				$candidate = APPPATH . 'language/' . $language . '/' . $file;
				if (file_exists($candidate))
				{
					$source = $candidate;
				}
			}

			if ($source !== null)
			{
				$data = $this->get_translations_array($source);
				if (!is_array($data))
				{
					continue;
				}
				$json         = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				$json_filename = str_replace('.php', '.json', $file);
				$this->ci->zip->add_data($language . '/' . $json_filename, $json);
			}
		}

		$this->ci->zip->download($language . '-' . date('Y-m-d') . '.zip');
	}


	/**
	 * Import a language zip of JSON files into userdata/language/.
	 *
	 * Expected zip structure: {lang}/{file}_lang.json
	 * e.g. french/general_lang.json
	 *
	 * Each JSON file must be a flat object {"key": "value"}.
	 * Validation per file:
	 *   - No path traversal
	 *   - Exactly two path segments: {lang}/{file}
	 *   - Extension must be .json
	 *   - Corresponding PHP file must exist in English language files
	 *   - Valid JSON object with all-string keys and values
	 *   - Values must not contain null bytes and must not exceed 10 KB
	 *   - Only keys that exist in the English reference file are imported
	 *
	 * Imported data is written as PHP to userdata/language/{lang}/{file}_lang.php.
	 * Existing files are backed up to {file}.bak before overwriting.
	 *
	 * @param  string $zip_tmp_path  Path to the uploaded temporary file
	 * @return array  ['type'=>'success'|'error', 'msg'=>string, 'language'=>string, 'imported'=>int, 'skipped'=>array]
	 */
	function import_zip($zip_tmp_path)
	{
		$user_data = $this->ci->config->item('userdata_path');
		if (empty($user_data))
		{
			return array('type' => 'error', 'msg' => 'userdata_path is not configured.', 'language' => '', 'imported' => 0, 'skipped' => array());
		}

		if (!class_exists('ZipArchive'))
		{
			return array('type' => 'error', 'msg' => 'ZipArchive PHP extension is not available.', 'language' => '', 'imported' => 0, 'skipped' => array());
		}

		// Build whitelist: base name (without extension) => PHP filename
		// e.g. 'general_lang' => 'general_lang.php'
		$english_files = $this->get_language_files_array(APPPATH . 'language/english');
		if (!is_array($english_files))
		{
			return array('type' => 'error', 'msg' => 'Could not read English language folder.', 'language' => '', 'imported' => 0, 'skipped' => array());
		}
		$en_basenames = array();
		foreach ($english_files as $f)
		{
			$en_basenames[pathinfo($f, PATHINFO_FILENAME)] = $f;
		}

		$zip = new ZipArchive();
		if ($zip->open($zip_tmp_path) !== true)
		{
			return array('type' => 'error', 'msg' => 'Could not open uploaded zip file.', 'language' => '', 'imported' => 0, 'skipped' => array());
		}

		$file_count = $zip->numFiles;
		$language   = null;

		// First pass: detect language name from the top-level folder
		for ($i = 0; $i < $file_count; $i++)
		{
			$name  = $zip->getNameIndex($i);
			$parts = explode('/', trim($name, '/'));
			if (!empty($parts[0]) && preg_match('/^[a-z][a-z0-9_]{1,29}$/', $parts[0]))
			{
				$language = $parts[0];
				break;
			}
		}

		if (!$language)
		{
			$zip->close();
			return array('type' => 'error', 'msg' => 'Could not detect language from zip structure. Expected a top-level folder named after the language (e.g. french/).', 'language' => '', 'imported' => 0, 'skipped' => array());
		}

		// Ensure destination folder exists
		$lang_folder = $user_data . '/language/' . $language;
		if (!is_dir($lang_folder))
		{
			if (!mkdir($lang_folder, 0755, true))
			{
				$zip->close();
				return array('type' => 'error', 'msg' => 'Could not create language folder: ' . $lang_folder, 'language' => $language, 'imported' => 0, 'skipped' => array());
			}
		}

		$imported = 0;
		$skipped  = array();

		// Second pass: validate and import each file
		for ($i = 0; $i < $file_count; $i++)
		{
			$name = $zip->getNameIndex($i);

			// Skip directory entries
			if (substr($name, -1) === '/')
			{
				continue;
			}

			// Reject path traversal
			if (strpos($name, '..') !== false || strpos($name, './') !== false)
			{
				$skipped[] = $name . ' (path traversal rejected)';
				continue;
			}

			$parts    = explode('/', $name);
			$filename = basename($name);

			// Must be exactly {lang}/{file} — no deeper nesting
			if (count($parts) !== 2 || $parts[0] !== $language)
			{
				$skipped[] = $name . ' (unexpected path, must be ' . $language . '/filename)';
				continue;
			}

			// Extension must be .json
			if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'json')
			{
				$skipped[] = $name . ' (not a JSON file)';
				continue;
			}

			// Base name must correspond to a file in English language files
			$base = pathinfo($filename, PATHINFO_FILENAME); // e.g. 'general_lang'
			if (!isset($en_basenames[$base]))
			{
				$skipped[] = $name . ' (not in English language files)';
				continue;
			}

			$contents = $zip->getFromIndex($i);
			if ($contents === false)
			{
				$skipped[] = $name . ' (could not read from zip)';
				continue;
			}

			// Parse and validate JSON
			$data = json_decode($contents, true);
			if (!is_array($data))
			{
				$skipped[] = $name . ' (invalid JSON — ' . json_last_error_msg() . ')';
				continue;
			}

			// All keys and values must be strings
			$all_strings = true;
			foreach ($data as $k => $v)
			{
				if (!is_string($k) || !is_string($v))
				{
					$all_strings = false;
					break;
				}
			}
			if (!$all_strings)
			{
				$skipped[] = $name . ' (all keys and values must be strings)';
				continue;
			}

			// Load English reference keys for this file
			$en_keys = $this->get_translations_array(APPPATH . 'language/english/' . $en_basenames[$base]);
			if (!is_array($en_keys))
			{
				$skipped[] = $name . ' (could not read English reference file)';
				continue;
			}

			// Only import keys that exist in English; sanitize values
			$filtered   = array();
			$bad_value  = false;
			foreach ($en_keys as $key => $en_val)
			{
				if (!isset($data[$key]))
				{
					continue; // missing key — leave untranslated
				}
				$val = $data[$key];
				// Reject null bytes
				if (strpos($val, "\0") !== false)
				{
					$skipped[]  = $name . ': key "' . $key . '" contains null byte (file rejected)';
					$bad_value  = true;
					break;
				}
				// Value length cap: 10 KB
				if (strlen($val) > 10240)
				{
					$skipped[]  = $name . ': key "' . $key . '" exceeds 10 KB limit (file rejected)';
					$bad_value  = true;
					break;
				}
				$filtered[$key] = $val;
			}

			if ($bad_value)
			{
				continue;
			}

			if (empty($filtered))
			{
				$skipped[] = $name . ' (no matching keys found)';
				continue;
			}

			$dest = $lang_folder . '/' . $en_basenames[$base];
			$file_base = str_replace('_lang.php', '', $en_basenames[$base]);

			// Backup existing file before overwriting
			if (file_exists($dest))
			{
				@copy($dest, $dest . '.bak');
			}

			$php = $this->generate_php_content($filtered, $file_base, $language);
			if (@file_put_contents($dest, $php) === false)
			{
				$skipped[] = $name . ' (write failed)';
				continue;
			}

			$imported++;
		}

		$zip->close();

		if ($imported === 0)
		{
			return array(
				'type'     => 'error',
				'msg'      => 'No files were imported. All entries were skipped.',
				'language' => $language,
				'imported' => 0,
				'skipped'  => $skipped,
			);
		}

		return array(
			'type'     => 'success',
			'msg'      => $imported . ' file(s) imported for language "' . $language . '".',
			'language' => $language,
			'imported' => $imported,
			'skipped'  => $skipped,
		);
	}

}// END Translator Class

/* End of file Translator.php */
/* Location: ./application/libraries/translator.php */