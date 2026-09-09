<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Country filter value resolution for public catalog search.
 *
 * The `country` search parameter is accepted as any mix of:
 *   - numeric countries.countryid values (what the facet sidebar emits)
 *   - country names          e.g. "Brazil"
 *   - ISO3 codes             e.g. "BRA"
 *   - ISO2 codes             e.g. "BR"   (mapped to ISO3 before lookup)
 *   - country_aliases.alias  e.g. a locally configured alternate spelling
 *
 * Matching is case-insensitive.
 *
 * Fail-closed contract: when the caller supplied at least one country value and NONE of them
 * resolve to a real country, this returns the NO_MATCH sentinel rather than an empty array.
 * Callers must translate that into a filter matching zero rows. Dropping the filter instead
 * (the historical behaviour) silently returned the entire catalog for an unrecognised country,
 * which reads as a successful broad search rather than a failed filter.
 */
class Catalog_country_resolver {

	/**
	 * Sentinel country id used to force an empty result set. countries.countryid is a positive
	 * AUTO_INCREMENT/IDENTITY value, so -1 can never match a real row.
	 */
	const NO_MATCH = -1;

	/**
	 * Resolve mixed country values to country IDs.
	 *
	 * @param mixed $values Array or scalar; names, ISO2/ISO3 codes, aliases, or numeric IDs
	 * @return int[] Country IDs; array(self::NO_MATCH) when values were given but none resolved;
	 *               empty array when no country filter was requested at all
	 */
	public static function resolve($values)
	{
		$values = self::normalize_values($values);

		if (empty($values)) {
			//no country filter requested — caller should not add a clause
			return array();
		}

		$ids    = array();
		$lookup = array();

		foreach ($values as $value) {
			if (is_numeric($value)) {
				$id = (int) $value;
				if ($id > 0) {
					$ids[] = $id;
				}
				continue;
			}

			$lookup[] = $value;
		}

		if (! empty($lookup)) {
			$ids = array_merge($ids, self::lookup_ids($lookup));
		}

		$ids = array_values(array_unique(array_filter($ids, function ($id) {
			return $id > 0;
		})));

		if (empty($ids)) {
			//values were supplied but nothing matched — fail closed
			return array(self::NO_MATCH);
		}

		return $ids;
	}

	/**
	 * True when resolve() found nothing for the values it was given.
	 *
	 * @param int[] $ids
	 * @return bool
	 */
	public static function is_no_match(array $ids)
	{
		return count($ids) === 1 && (int) reset($ids) === self::NO_MATCH;
	}

	/**
	 * Flatten to a trimmed, non-empty list of scalars.
	 *
	 * @param mixed $values
	 * @return string[]
	 */
	private static function normalize_values($values)
	{
		if ($values === null || $values === false || $values === '') {
			return array();
		}

		if (! is_array($values)) {
			$values = explode(',', (string) $values);
		}

		$out = array();

		foreach ($values as $value) {
			if (is_array($value)) {
				continue;
			}
			$value = trim((string) $value);
			if ($value !== '') {
				$out[] = $value;
			}
		}

		return $out;
	}

	/**
	 * Look up country IDs by name, ISO3 or alias (case-insensitive). ISO2 inputs are mapped
	 * to ISO3 first, matching the mapping the public API already applies.
	 *
	 * @param string[] $names
	 * @return int[]
	 */
	private static function lookup_ids(array $names)
	{
		$ci =& get_instance();

		$terms = array();

		foreach ($names as $name) {
			$lower   = strtolower($name);
			$terms[] = $lower;

			if (strlen($lower) === 2) {
				$iso3 = self::iso2_to_iso3($lower);
				if ($iso3 !== null) {
					$terms[] = $iso3;
				}
			}
		}

		$terms = array_values(array_unique($terms));

		if (empty($terms)) {
			return array();
		}

		$escaped = array();
		foreach ($terms as $term) {
			$escaped[] = $ci->db->escape($term);
		}
		$in_list = implode(',', $escaped);

		$sql = 'SELECT countries.countryid FROM countries
			WHERE LOWER(countries.name) IN (' . $in_list . ')
			   OR LOWER(countries.iso) IN (' . $in_list . ')
			UNION
			SELECT country_aliases.countryid FROM country_aliases
			WHERE LOWER(country_aliases.alias) IN (' . $in_list . ')';

		$rows = $ci->db->query($sql)->result_array();

		return array_map('intval', array_column($rows, 'countryid'));
	}

	/**
	 * @param string $iso2 lowercase 2-letter code
	 * @return string|null lowercase 3-letter code
	 */
	private static function iso2_to_iso3($iso2)
	{
		static $map = array(
			'af'=>'afg','al'=>'alb','dz'=>'dza','as'=>'asm','ad'=>'and','ao'=>'ago','ai'=>'aia',
			'aq'=>'ata','ag'=>'atg','ar'=>'arg','am'=>'arm','aw'=>'abw','au'=>'aus','at'=>'aut',
			'az'=>'aze','bs'=>'bhs','bh'=>'bhr','bd'=>'bgd','bb'=>'brb','by'=>'blr','be'=>'bel',
			'bz'=>'blz','bj'=>'ben','bm'=>'bmu','bt'=>'btn','bo'=>'bol','bq'=>'bes','ba'=>'bih',
			'bw'=>'bwa','bv'=>'bvt','br'=>'bra','io'=>'iot','bn'=>'brn','bg'=>'bgr','bf'=>'bfa',
			'bi'=>'bdi','cv'=>'cpv','kh'=>'khm','cm'=>'cmr','ca'=>'can','ky'=>'cym','cf'=>'caf',
			'td'=>'tcd','cl'=>'chl','cn'=>'chn','cx'=>'cxr','cc'=>'cck','co'=>'col','km'=>'com',
			'cd'=>'cod','cg'=>'cog','ck'=>'cok','cr'=>'cri','hr'=>'hrv','cu'=>'cub','cw'=>'cuw',
			'cy'=>'cyp','cz'=>'cze','ci'=>'civ','dk'=>'dnk','dj'=>'dji','dm'=>'dma','do'=>'dom',
			'ec'=>'ecu','eg'=>'egy','sv'=>'slv','gq'=>'gnq','er'=>'eri','ee'=>'est','sz'=>'swz',
			'et'=>'eth','fk'=>'flk','fo'=>'fro','fj'=>'fji','fi'=>'fin','fr'=>'fra','gf'=>'guf',
			'pf'=>'pyf','tf'=>'atf','ga'=>'gab','gm'=>'gmb','ge'=>'geo','de'=>'deu','gh'=>'gha',
			'gi'=>'gib','gr'=>'grc','gl'=>'grl','gd'=>'grd','gp'=>'glp','gu'=>'gum','gt'=>'gtm',
			'gg'=>'ggy','gn'=>'gin','gw'=>'gnb','gy'=>'guy','ht'=>'hti','hm'=>'hmd','va'=>'vat',
			'hn'=>'hnd','hk'=>'hkg','hu'=>'hun','is'=>'isl','in'=>'ind','id'=>'idn','ir'=>'irn',
			'iq'=>'irq','ie'=>'irl','im'=>'imn','il'=>'isr','it'=>'ita','jm'=>'jam','jp'=>'jpn',
			'je'=>'jey','jo'=>'jor','kz'=>'kaz','ke'=>'ken','ki'=>'kir','kp'=>'prk','kr'=>'kor',
			'kw'=>'kwt','kg'=>'kgz','la'=>'lao','lv'=>'lva','lb'=>'lbn','ls'=>'lso','lr'=>'lbr',
			'ly'=>'lby','li'=>'lie','lt'=>'ltu','lu'=>'lux','mo'=>'mac','mg'=>'mdg','mw'=>'mwi',
			'my'=>'mys','mv'=>'mdv','ml'=>'mli','mt'=>'mlt','mh'=>'mhl','mq'=>'mtq','mr'=>'mrt',
			'mu'=>'mus','yt'=>'myt','mx'=>'mex','fm'=>'fsm','md'=>'mda','mc'=>'mco','mn'=>'mng',
			'me'=>'mne','ms'=>'msr','ma'=>'mar','mz'=>'moz','mm'=>'mmr','na'=>'nam','nr'=>'nru',
			'np'=>'npl','nl'=>'nld','nc'=>'ncl','nz'=>'nzl','ni'=>'nic','ne'=>'ner','ng'=>'nga',
			'nu'=>'niu','nf'=>'nfk','mp'=>'mnp','no'=>'nor','om'=>'omn','pk'=>'pak','pw'=>'plw',
			'ps'=>'pse','pa'=>'pan','pg'=>'png','py'=>'pry','pe'=>'per','ph'=>'phl','pn'=>'pcn',
			'pl'=>'pol','pt'=>'prt','pr'=>'pri','qa'=>'qat','mk'=>'mkd','ro'=>'rou','ru'=>'rus',
			'rw'=>'rwa','re'=>'reu','bl'=>'blm','sh'=>'shn','kn'=>'kna','lc'=>'lca','mf'=>'maf',
			'pm'=>'spm','vc'=>'vct','ws'=>'wsm','sm'=>'smr','st'=>'stp','sa'=>'sau','sn'=>'sen',
			'rs'=>'srb','sc'=>'syc','sl'=>'sle','sg'=>'sgp','sx'=>'sxm','sk'=>'svk','si'=>'svn',
			'sb'=>'slb','so'=>'som','za'=>'zaf','gs'=>'sgs','ss'=>'ssd','es'=>'esp','lk'=>'lka',
			'sd'=>'sdn','sr'=>'sur','sj'=>'sjm','se'=>'swe','ch'=>'che','sy'=>'syr','tw'=>'twn',
			'tj'=>'tjk','tz'=>'tza','th'=>'tha','tl'=>'tls','tg'=>'tgo','tk'=>'tkl','to'=>'ton',
			'tt'=>'tto','tn'=>'tun','tr'=>'tur','tm'=>'tkm','tc'=>'tca','tv'=>'tuv','ug'=>'uga',
			'ua'=>'ukr','ae'=>'are','gb'=>'gbr','um'=>'umi','us'=>'usa','uy'=>'ury','uz'=>'uzb',
			'vu'=>'vut','ve'=>'ven','vn'=>'vnm','vg'=>'vgb','vi'=>'vir','wf'=>'wlf','eh'=>'esh',
			'ye'=>'yem','zm'=>'zmb','zw'=>'zwe','ax'=>'ala',
		);

		return isset($map[$iso2]) ? $map[$iso2] : null;
	}
}
