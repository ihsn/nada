<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once(APPPATH . 'core/MY_Migration.php');

/**
 * Platform core (light steps): user/API-key columns, sitelogs cutover, analytics tables,
 * codelists and data structures, repository ACL, site configuration, data deposit,
 * display templates, activation codes and the removal of the legacy 'user' role.
 *
 * Every step skips work that is already applied, so this is safe on any database from
 * v5.5 onward regardless of which earlier migrations it has run. Steps that rebuild large
 * tables or indexes are in the later migrations (core_table_alterations, search indexes,
 * variables unicode, pageview tracking, timeseries cutover).
 */
class Migration_Platform_core extends MY_Migration {

	public function up()
	{
		$this->run_archived_steps(array(
			'20251027000001_add_authtype_to_users.php',
			'20251128000001_api_keys_security_enhancement.php',
			'20251214000001_rename_sitelogs_to_legacy.php',
			'20251214000002_create_sitelogs_table.php',
			'20260618120001_ensure_sitelogs_table.php',
			'20260310000001_dctypes_code_and_translations.php',
			'20260312000001_create_codelists_schema.php',
			'20260701000004_codelists_dsd_versioning.php',
			'20260326000001_create_analytics_schema.php',
			'20260326000002_migrate_analytics_legacy_totals.php',
			'20260508120001_create_repositories_acl_table.php',
			'20260508130501_migrate_legacy_role_permissions_to_repositories_acl.php',
			'20260508150001_remove_collection_scoped_role_permissions.php',
			'20260510120001_add_data_classifications_enabled_configuration.php',
			'20260608120001_create_study_admin_metadata_schema.php',
			'20260611120001_add_catalog_public_search_ui_configuration.php',
			'20260614120001_add_facets_timeseries_configuration.php',
			'20260909120003_datadeposit_platform.php',
			'20261015120001_datadeposit_field_limits.php',
			'20260909120001_display_templates_platform.php',
			'20260909120004_add_site_user_register_configuration.php',
			'20261022120001_activation_code_hash.php',
		));
	}

	public function down()
	{
		throw new Exception('Rollback not supported. Restore from database backup if needed.');
	}
}
