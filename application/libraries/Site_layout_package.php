<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Site layout package: export the header, footer and styles as one .zip, and read one back.
 *
 * Used by api/admin/site_layout. The package carries settings and images only:
 *   manifest.json   {format, format_version, nada_version, theme, exported, parts:{part:{state}}, images:[names]}
 *   header.json     header settings (as stored in site_layout)
 *   footer.json     footer settings
 *   styles.json     site CSS settings
 *   images/<name>   images the header uses (logo, header and row backgrounds)
 *
 * Theme, Bootstrap, menu items, site title and languages come from the site that imports it.
 * Reading runs every part through the builder's normalize() (the same cleaning as a save) and every image
 * through Site_layout_images (the same checks as an upload). Only known file names are read from the zip;
 * nothing is extracted to a path taken from it.
 */
class Site_layout_package {

	const FORMAT = 'nada-site-layout';
	const FORMAT_VERSION = 1;

	const MAX_PACKAGE = 20971520;   // 20 MB
	const MAX_ENTRIES = 50;
	const MAX_JSON = 1048576;       // per settings file

	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->library('site_layout_parts');
	}

	/** "header,footer" => known parts in a fixed order; empty => all */
	public function parts_param($value)
	{
		return $this->ci->site_layout_parts->parts_param($value);
	}

	private function builder($part)
	{
		return $this->ci->site_layout_parts->builder($part);
	}

	// =============================================================================================
	// Export
	// =============================================================================================

	/**
	 * Write the package to a temporary file.
	 *
	 * @param string $state published | draft (draft falls back to published for a part without a draft)
	 * @return string path of the .zip; the caller deletes it
	 * @throws Site_layout_error ZIP_NOT_AVAILABLE, NOTHING_TO_EXPORT, EXPORT_FAILED
	 */
	public function build(array $parts, $state)
	{
		if (!class_exists('ZipArchive')) {
			throw new Site_layout_error('ZIP_NOT_AVAILABLE', 500);
		}
		$state = $state === 'draft' ? 'draft' : 'published';
		$model = $this->ci->Site_layout_model;

		$manifest = array(
			'format'         => self::FORMAT,
			'format_version' => self::FORMAT_VERSION,
			'nada_version'   => defined('APP_VERSION') ? APP_VERSION : '',
			'theme'          => $this->ci->site_header_builder->theme_name(),
			'exported'       => gmdate('Y-m-d\TH:i:s\Z'),
			'parts'          => array(),
			'images'         => array(),
		);
		$files = array();
		$images = array();

		foreach (array_intersect(Site_layout_parts::$parts, $parts) as $part) {
			$raw = $model->get($part, $state);
			$used = $state;
			if ($raw === '' && $state === 'draft') {
				$raw = $model->get($part, 'published');
				$used = 'published';
			}
			if ($raw === '') {
				continue;
			}
			$builder = $this->builder($part);
			$settings = $builder->normalize($raw);
			$files[$part . '.json'] = $builder->to_json($settings);
			$manifest['parts'][$part] = array('state' => $used);

			if ($part === 'header') {
				foreach ($builder->image_paths($settings) as $path) {
					$abs = rtrim(FCPATH, '/\\') . '/' . $path;
					if (is_file($abs)) {
						$images[basename($path)] = $abs;
					}
				}
			}
		}

		if (!$manifest['parts']) {
			throw new Site_layout_error('NOTHING_TO_EXPORT', 422);
		}
		$manifest['images'] = array_keys($images);

		$tmp = tempnam(sys_get_temp_dir(), 'nada-sl-');
		$zip = new ZipArchive();
		if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
			if ($tmp !== false) {
				@unlink($tmp);
			}
			throw new Site_layout_error('EXPORT_FAILED', 500);
		}
		$zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		foreach ($files as $name => $json) {
			$zip->addFromString($name, $json);
		}
		foreach ($images as $name => $abs) {
			$zip->addFile($abs, 'images/' . $name);
		}
		if (!$zip->close()) {
			@unlink($tmp);
			throw new Site_layout_error('EXPORT_FAILED', 500);
		}
		return $tmp;
	}

	// =============================================================================================
	// Import
	// =============================================================================================

	/**
	 * Open and check a package file. Close $package['zip'] when done.
	 *
	 * @return array{zip:ZipArchive, manifest:array, parts:array, images:array} images: file name => zip index
	 * @throws Site_layout_error ZIP_NOT_AVAILABLE, FILE_TOO_LARGE, NOT_A_PACKAGE, PACKAGE_NEWER
	 */
	public function open($path)
	{
		if (!class_exists('ZipArchive')) {
			throw new Site_layout_error('ZIP_NOT_AVAILABLE', 500);
		}
		if (!is_file($path) || filesize($path) > self::MAX_PACKAGE) {
			throw new Site_layout_error('FILE_TOO_LARGE', 413);
		}

		$zip = new ZipArchive();
		if ($zip->open($path) !== true) {
			throw new Site_layout_error('NOT_A_PACKAGE', 422);
		}
		$fail = function ($code) use ($zip) {
			$zip->close();
			return new Site_layout_error($code, 422);
		};
		if ($zip->numFiles > self::MAX_ENTRIES) {
			throw $fail('NOT_A_PACKAGE');
		}

		$max_image = 0;
		foreach (Site_layout_images::$limits as $limit) {
			$max_image = max($max_image, $limit['max']);
		}

		// Only these names are read; anything else in the zip is ignored.
		$json_index = array();
		$images = array();
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$stat = $zip->statIndex($i);
			$name = $stat ? (string) $stat['name'] : '';
			if (preg_match('#^(manifest|header|footer|styles)\.json$#', $name, $m)) {
				if ($stat['size'] > self::MAX_JSON) {
					throw $fail('NOT_A_PACKAGE');
				}
				$json_index[$m[1]] = $i;
			}
			elseif (preg_match('#^images/([A-Za-z0-9][A-Za-z0-9._-]*\.(png|jpe?g|gif|webp|svg))$#i', $name, $m)) {
				if ($stat['size'] <= $max_image && strpos($m[1], '..') === false) {
					$images[$m[1]] = $i;
				}
			}
		}

		$manifest = isset($json_index['manifest']) ? json_decode($this->read_entry($zip, $json_index['manifest'], self::MAX_JSON), true) : null;
		if (!is_array($manifest) || !isset($manifest['format']) || $manifest['format'] !== self::FORMAT) {
			throw $fail('NOT_A_PACKAGE');
		}
		if ((int) (isset($manifest['format_version']) ? $manifest['format_version'] : 0) > self::FORMAT_VERSION) {
			throw $fail('PACKAGE_NEWER');
		}

		$parts = array();
		$listed = isset($manifest['parts']) && is_array($manifest['parts']) ? $manifest['parts'] : array();
		$manifest['parts'] = array();
		foreach (Site_layout_parts::$parts as $part) {
			if (!isset($json_index[$part])) {
				continue;
			}
			$settings = json_decode($this->read_entry($zip, $json_index[$part], self::MAX_JSON), true);
			if (!is_array($settings)) {
				continue;
			}
			$parts[$part] = $settings;
			$state = isset($listed[$part]['state']) && $listed[$part]['state'] === 'draft' ? 'draft' : 'published';
			$manifest['parts'][$part] = array('state' => $state);
		}
		if (!$parts) {
			throw $fail('NOT_A_PACKAGE');
		}

		return array('zip' => $zip, 'manifest' => $manifest, 'parts' => $parts, 'images' => $images, 'max_image' => $max_image);
	}

	/**
	 * Read one zip entry, at most $max bytes. The size in the zip directory is only a claim, so the read itself is
	 * capped; a longer entry returns '' (treated as invalid).
	 */
	private function read_entry(ZipArchive $zip, $index, $max)
	{
		$data = $zip->getFromIndex($index, $max + 1);
		return is_string($data) && strlen($data) <= $max ? $data : '';
	}

	/** What an opened package holds and what importing it would replace */
	public function summary(array $package)
	{
		$parts = array();
		foreach ($package['parts'] as $part => $settings) {
			$info = array(
				'state'          => $package['manifest']['parts'][$part]['state'],
				'replaces_draft' => $this->ci->Site_layout_model->get($part, 'draft') !== '',
			);
			if ($part !== 'styles') {
				$info['source'] = isset($settings['source']) && is_scalar($settings['source']) ? (string) $settings['source'] : '';
			}
			if ($part === 'header') {
				$info['images'] = count($this->header_image_refs($settings));
			}
			$parts[$part] = $info;
		}
		return array(
			'package'  => $this->package_info($package['manifest']),
			'parts'    => $parts,
			'warnings' => $this->site_warnings($package['manifest']),
		);
	}

	/**
	 * Clean the chosen parts of an opened package for storing as drafts. Header images are copied into
	 * files/site/ now; settings are not saved here.
	 *
	 * @return array part => settings JSON
	 */
	public function prepare(array $package, array $selected, array &$warnings)
	{
		$out = array();
		foreach (array_intersect(Site_layout_parts::$parts, $selected) as $part) {
			if (!isset($package['parts'][$part])) {
				continue;
			}
			$input = $package['parts'][$part];
			$builder = $this->builder($part);

			// HTML or CSS over the size limit would be imported empty: leave the whole part out instead.
			// Checked before any image is copied.
			$errors = array();
			$builder->normalize($input, $errors);
			$too_long = array_keys(array_intersect($errors, array('HTML_TOO_LONG', 'CSS_TOO_LONG')));
			if ($too_long) {
				$warnings[] = array('part' => $part, 'code' => 'PART_TOO_LONG', 'detail' => implode(', ', $too_long));
				continue;
			}

			if ($part === 'header') {
				$input = $this->import_header_images($input, $package, $warnings);
			}
			$errors = array();
			$settings = $builder->normalize($input, $errors);

			// Settings written by a newer NADA may have options this site does not know; those are dropped
			$version = isset($input['version']) && is_numeric($input['version']) ? (int) $input['version'] : 0;
			if ($version > $this->ci->site_layout_parts->version($part)) {
				$warnings[] = array('part' => $part, 'code' => 'NEWER_VERSION', 'detail' => '');
			}

			if ($errors) {
				$warnings[] = array('part' => $part, 'code' => 'SETTINGS_ADJUSTED', 'detail' => implode(', ', array_keys($errors)));
			}
			foreach (array('html' => 'HTML_CLEANED', 'css' => 'CSS_CLEANED') as $key => $code) {
				if (isset($input[$key]) && $this->squash($input[$key]) !== $this->squash(isset($settings[$key]) ? $settings[$key] : '')) {
					$warnings[] = array('part' => $part, 'code' => $code, 'detail' => '');
				}
			}
			$out[$part] = $builder->to_json($settings);
		}
		return $out;
	}

	// =============================================================================================
	// Helpers
	// =============================================================================================

	private function package_info(array $manifest)
	{
		$get = function ($key) use ($manifest) {
			return isset($manifest[$key]) && is_scalar($manifest[$key]) ? (string) $manifest[$key] : '';
		};
		return array(
			'nada_version' => $get('nada_version'),
			'theme'        => $get('theme'),
			'exported'     => $get('exported'),
			'site'         => array(
				'nada_version' => defined('APP_VERSION') ? APP_VERSION : '',
				'theme'        => $this->ci->site_header_builder->theme_name(),
			),
		);
	}

	/** The package came from another theme or NADA version: it may look different here */
	private function site_warnings(array $manifest)
	{
		$info = $this->package_info($manifest);
		$warnings = array();
		if ($info['theme'] !== '' && $info['theme'] !== $info['site']['theme']) {
			$warnings[] = array('part' => '', 'code' => 'THEME_DIFFERS', 'detail' => $info['theme']);
		}
		if ($info['nada_version'] !== '' && $info['nada_version'] !== $info['site']['nada_version']) {
			$warnings[] = array('part' => '', 'code' => 'VERSION_DIFFERS', 'detail' => $info['nada_version']);
		}
		return $warnings;
	}

	/** Image settings in a header: list of [keys to the setting, kind, value] */
	private function header_image_refs(array $s)
	{
		$refs = array();
		if (!empty($s['logo']) && is_string($s['logo'])) {
			$refs[] = array(array('logo'), 'logo', $s['logo']);
		}
		if (!empty($s['header_style']['bg_image']) && is_string($s['header_style']['bg_image'])) {
			$refs[] = array(array('header_style', 'bg_image'), 'background', $s['header_style']['bg_image']);
		}
		if (isset($s['rows']) && is_array($s['rows'])) {
			foreach ($s['rows'] as $id => $row) {
				if (is_array($row) && !empty($row['style']['bg_image']) && is_string($row['style']['bg_image'])) {
					$refs[] = array(array('rows', $id, 'style', 'bg_image'), 'background', $row['style']['bg_image']);
				}
			}
		}
		return $refs;
	}

	/**
	 * Copy the header's images from the package into files/site/ and point the settings at the copies.
	 * An image that is missing or fails a check is left out, with a warning.
	 */
	private function import_header_images(array $s, array $package, array &$warnings)
	{
		$images = $this->ci->site_layout_images;
		$done = array();
		foreach ($this->header_image_refs($s) as $ref) {
			list($keys, $kind, $value) = $ref;
			$name = basename(str_replace('\\', '/', $value));
			$key = $kind . '/' . $name;

			if (!isset($done[$key])) {
				$done[$key] = '';
				if (!isset($package['images'][$name])) {
					$warnings[] = array('part' => 'header', 'code' => 'IMAGE_MISSING', 'detail' => $name);
				}
				else {
					$tmp = tempnam(sys_get_temp_dir(), 'nada-sl-img-');
					$ext = $images->extension($name);
					try {
						file_put_contents($tmp, $this->read_entry($package['zip'], $package['images'][$name], $package['max_image']));
						$code = $images->check($tmp, $ext, $kind);
						if ($code === '') {
							$done[$key] = $images->store($tmp, $kind, $ext, false);
						}
						else {
							$warnings[] = array('part' => 'header', 'code' => 'IMAGE_SKIPPED', 'detail' => $name . ' (' . $code . ')');
						}
					}
					catch (Exception $e) {
						$warnings[] = array('part' => 'header', 'code' => 'IMAGE_SKIPPED', 'detail' => $name . ' (' . $e->getMessage() . ')');
					}
					@unlink($tmp);
				}
			}
			$s = $this->set_in($s, $keys, $done[$key]);
		}
		return $s;
	}

	private function set_in(array $s, array $keys, $value)
	{
		$ref = &$s;
		foreach ($keys as $k) {
			$ref = &$ref[$k];
		}
		$ref = $value;
		unset($ref);
		return $s;
	}

	private function squash($s)
	{
		return preg_replace('/\s+/', '', is_scalar($s) ? (string) $s : '');
	}
}
