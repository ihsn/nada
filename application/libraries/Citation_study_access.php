<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Citation_study_access
 *
 * Linking a citation to a study needs `study/view` on the study's owner collection. Used by the admin citations API.
 */
class Citation_study_access
{
	private $ci;

	public function __construct()
	{
		$this->ci =& get_instance();
		$this->ci->load->library('acl_manager');
	}

	/**
	 * Study ids (surveys.id) from request input: numeric, positive, unique.
	 */
	public function clean_ids($ids)
	{
		$out = array();
		foreach ((array) $ids as $id) {
			if (is_numeric($id) && (int) $id > 0) {
				$out[(int) $id] = (int) $id;
			}
		}
		return array_values($out);
	}

	/**
	 * Study ids that exist.
	 */
	public function existing_ids(array $ids)
	{
		if (empty($ids)) {
			return array();
		}
		$this->ci->db->select('id');
		$this->ci->db->where_in('id', $ids);
		$rows = $this->ci->db->get('surveys')->result_array();
		return array_map('intval', array_column($rows, 'id'));
	}

	/**
	 * Can the user view the study?
	 *
	 * The study is judged by its owner collection (the survey_repos link with isadmin = 1), exactly as the
	 * catalog API does: the study id is passed without a collection and Acl_manager::check_access() looks the
	 * owner up. (A study can be linked to several collections; taking "the first link" instead could allow or
	 * refuse the wrong way round, and would disagree with what the study picker lists.)
	 *
	 * @param int      $sid
	 * @param int|null $user_id the acting user (session user or API key owner); NULL for the current user
	 */
	public function can_view($sid, $user_id = NULL)
	{
		try {
			$this->ci->acl_manager->check_access('study', 'view', $user_id, (int) $sid, NULL);
			return true;
		}
		catch (AclAccessDeniedException $e) {
			unset($e);
			return false;
		}
	}

	/**
	 * Ids the user may not view.
	 *
	 * @return int[]
	 */
	public function denied_ids(array $sids, $user_id = NULL)
	{
		$denied = array();
		foreach ($sids as $sid) {
			if (!$this->can_view($sid, $user_id)) {
				$denied[] = (int) $sid;
			}
		}
		return $denied;
	}
}
