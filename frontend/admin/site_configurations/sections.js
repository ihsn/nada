/** Section IDs and payload keys — shared by router guards and Site configurations UI */
export const SECTION_DEFS = [
  {
    id: 'general',
    titleKey: 'general_site_settings',
    keys: [
      'website_title',
      'website_footer',
      'default_home_page',
      'website_webmaster_name',
      'website_webmaster_email',
      'max_resource_upload_size',
      'admin_header_background',
    ],
  },
  { id: 'languages', titleKey: 'language', keys: ['language', 'supported_languages'] },
  { id: 'html_editor', titleKey: 'use_html_editor_for_html', keys: ['use_html_editor'] },
  {
    id: 'catalog',
    titleKey: 'survey_catalog_settings',
    keys: [
      'catalog_root',
      'ddi_import_folder',
      'catalog_records_per_page',
      'catalog_variable_view',
      'catalog_show_abstract',
      'data_types_nav_bar',
      'guests_hide_microdata_tab',
      'catalog_default_sort_by',
      'catalog_default_sort_order',
    ],
  },
  { id: 'search', titleKey: 'fulltext_search', keys: ['search_provider'] },
  {
    id: 'login',
    titleKey: 'site_login',
    keys: ['site_password_protect', 'login_timeout', 'min_password_length'],
  },
  { id: 'analytics', titleKey: 'Google Analytics', keys: ['google_ua_code'] },
  {
    id: 'mail',
    titleKey: 'mail_settings',
    keys: [
      'email_driver',
      'mail_protocol',
      'smtp_host',
      'smtp_port',
      'smtp_user',
      'smtp_pass',
      'smtp_auth',
      'smtp_crypto',
      'sendgrid_api_key',
      'microsoft_graph_client_id',
      'microsoft_graph_client_secret',
      'microsoft_graph_tenant_id',
    ],
  },
];

export const SITE_CONFIG_SECTION_IDS = new Set(SECTION_DEFS.map((s) => s.id));
