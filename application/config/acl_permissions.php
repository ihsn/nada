<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


$config['acl_system_roles'] = ['user','admin'];



//$acl->allow('lsms_collection_reviewer', 'lsms', array('unpublish','publish','view'));
//$acl->allow('admin');

//give full access to admin to everything
$config['acl_system_role_permissions'] = [
    'user'=>[
        'role'=>'user',
        'resource'=>'', //no access to any resource
        'permissions'=>'' //no permissions
    ],
    'admin'=>[
        'role'=>'admin',
        'resource'=>null, //full access to all resources
        'permissions'=>null //allowed all permissions
    ],
    
];



$config['acl_permissions'] = [
    'dashboard' => [
        'title' => 'Site dashboard',
        "permissions"=>[
            [
            'permission'=>'view',                
            'description'=>'View site administration dashboard'
            ]
        ]
    ],
    "menu"=>[ 
        "title" => "Site menu pages",
        "permissions"=>[
            [
                "permission" => "view"
            ],
            [
                "permission" => "create"
            ],
            [
                "permission" => "edit"
            ],
            [
                "permission" => "delete"
            ],
            [
                "permission" => "publish",
                "description" => "Publish or unpublish menu items"
            ]
        ]
    ],
    "citation"=>[ 
        "title" => "Citations",
        "permissions"=>[
            [
                "permission" => "view"
            ],
            [
                "permission" => "create"
            ],
            [
                "permission" => "edit"
            ],
            [
                "permission" => "delete"
            ]
        ]
    ],
    "user"=>[ 
        "title" => "Users",
        "permissions"=>[
            [
                "permission" => "view"
            ],
            [
                "permission" => "create"
            ],
            [
                "permission" => "edit"
            ],
            [
                "permission" => "delete"
            ]
        ]
    ],
    "licensed_request"=>[ 
        "title" => "Licensed requests",
        "description" => "Site-wide access to licensed survey requests across all collections.",
        "permissions"=>[
            [
                "permission" => "view"
            ],
            [
                "permission" => "create"
            ],
            [
                "permission" => "edit"
            ],
            [
                "permission" => "delete"
            ]
        ]
    ],
    "collection"=>[ 
        "title" => "Manage collections",
        "description" => "Site-wide access to collection records (view, create, edit, delete, publish).",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse and search collections"
            ],
            [
                "permission" => "create",
                "description" => "Create a new collection"
            ],
            [
                "permission" => "edit",
                "description" => "Edit collection information"
            ],
            [
                "permission" => "delete",
                "description" => "Delete a collection"
            ],
            [
                "permission" => "publish",
                "description" => "Publish or unpublish a collection"
            ],
            [
                "permission" => "manage_access",
                "description" => "Assign per-user access on any collection (studies, licensed requests, and collection administration)."
            ]
        ]
    ], 
    "study"=>[ 
        "title" => "Manage studies",
        "description"=> "Site-wide access to studies across all collections.",
        "permissions"=>[
            [
                "permission" => "view"
            ],
            [
                "permission" => "create",
                "description" => "Allows creating new studies including importing from DDI"
            ],
            [
                "permission" => "edit",
                "description" => "Edit study options, file uploads and external resources"
            ],
            [
                "permission" => "delete",
                "description" => "Delete a study"
            ],
            [
                "permission" => "publish",
                "description" => "Publish or unpublish a study"
            ]
        ]
    ],    
    "reports"=>[ 
        "title" => "Reports",
        "description"=> "Reports",
        "permissions"=>[
            [
                "permission" => "view"
            ]
        ]
    ],
    "configurations"=>[ 
        "title" => "Site configurations",
        "description"=> "Manage site configurations",
        "permissions"=>[
            [
                "permission" => "edit"
            ]
        ]
    ],
    "vocabularies"=>[ 
        "title" => "Vocabularies",
        "description"=> "Manage vocabularies and terms",
        "permissions"=>[
            [
                "permission" => "edit"
            ]
        ]
    ],
    "countries"=>[ 
        "title" => "Countries configurations",
        "description"=> "Manage countries list",
        "permissions"=>[
            [
                "permission" => "edit"
            ]
        ]
    ],
    "regions"=>[ 
        "title" => "Regions",
        "description"=> "Manage regions",
        "permissions"=>[
            [
                "permission" => "edit"
            ]
        ]
    ],
    "facets"=>[
        "title" => "Search facets",
        "description" => "Manage search facets, ordering, and indexing",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse facets admin"
            ],
            [
                "permission" => "edit",
                "description" => "Create, update, reorder, and reindex facets"
            ],
            [
                "permission" => "delete",
                "description" => "Delete user facets"
            ]
        ]
    ],
    "collection_type"=>[
        "title" => "Collection sections",
        "description" => "Manage collection type sections",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse collection sections"
            ],
            [
                "permission" => "edit",
                "description" => "Create and edit collection sections"
            ],
            [
                "permission" => "delete",
                "description" => "Delete collection sections"
            ]
        ]
    ],
    "translate"=>[ 
        "title" => "Site translations",
        "description"=> "Manage translations",
        "permissions"=>[
            [
                "permission" => "edit"
            ]
        ]
    ],
    "codelist"=>[
        "title" => "Codelists",
        "description" => "Manage catalogue codelists, items, groups, and translations",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse and read codelists"
            ],
            [
                "permission" => "create",
                "description" => "Create codelists and versions"
            ],
            [
                "permission" => "edit",
                "description" => "Edit codelists, items, groups, and import"
            ],
            [
                "permission" => "delete",
                "description" => "Delete codelists and related rows"
            ]
        ]
    ],
    "data_structure"=>[
        "title" => "Data structures (DSD)",
        "description" => "Manage global data structure definitions and components",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse and read data structures (including study picker lists)"
            ],
            [
                "permission" => "create",
                "description" => "Create data structure versions"
            ],
            [
                "permission" => "edit",
                "description" => "Update, import, export, and manage components"
            ],
            [
                "permission" => "delete",
                "description" => "Delete data structures and components"
            ]
        ]
    ],
    "table"=>[
        "title" => "Tables API",
        "description" => "Administer data tables via the Tables API (schema, indexes, uploads)",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Open the tables admin UI"
            ],
            [
                "permission" => "edit",
                "description" => "Upload data, manage indexes, fields, and table definitions"
            ],
            [
                "permission" => "delete",
                "description" => "Delete tables and table definitions"
            ]
        ]
    ],
    "datadeposit"=>[
        "title" => "Data deposit",
        "description" => "Administer submitted data-deposit projects (review, process, assign, delete).",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse and open data-deposit projects"
            ],
            [
                "permission" => "edit",
                "description" => "Process projects, assign tasks, send notifications, and download files"
            ],
            [
                "permission" => "delete",
                "description" => "Delete data-deposit projects and task assignments"
            ]
        ]
    ],
    "bulk_data_access"=>[
        "title" => "Bulk data access",
        "description" => "Manage bulk data access collections (group licensed studies for shared access).",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse bulk data access collections"
            ],
            [
                "permission" => "edit",
                "description" => "Create collections and attach or detach studies"
            ],
            [
                "permission" => "delete",
                "description" => "Delete bulk data access collections"
            ]
        ]
    ],
    "filestore"=>[ 
        "title" => "Filestore",
        "description"=> "Manage filestore files",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "View and browse filestore files"
            ],
            [
                "permission" => "upload",
                "description" => "Upload files to filestore"
            ],
            [
                "permission" => "delete",
                "description" => "Delete files from filestore"
            ],
            [
                "permission" => "download",
                "description" => "Download files from filestore"
            ]
        ]
    ],

];

/**
 * Per-collection ACL (repositories_acl): tier definitions for the collections admin UI and satisfiers for checks.
 * Multiple grants may coexist; study_admin / licensed_request_admin imply full access for that domain.
 */
$config['collections_acl'] = array(
	'study_tiers' => array(
		array(
			'key'         => 'study_view',
			'label'       => 'View',
			'description' => 'Browse and open studies in this collection.',
		),
		array(
			'key'         => 'study_edit',
			'label'       => 'Edit',
			'description' => 'Create and edit studies (metadata, files, import), refresh/replace DDI, batch refresh/import, and transfer ownership. Does not include publish or delete.',
		),
		array(
			'key'         => 'study_delete',
			'label'       => 'Delete',
			'description' => 'Delete studies in this collection.',
		),
		array(
			'key'         => 'study_publish',
			'label'       => 'Publish',
			'description' => 'Publish or unpublish studies.',
		),
		array(
			'key'         => 'study_admin',
			'label'       => 'Admin',
			'description' => 'Full study access for this collection (implies view, edit, delete, publish).',
		),
	),
	'collection_tiers' => array(
		array(
			'key'         => 'collection_view',
			'label'       => 'View',
			'description' => 'Browse this collection in admin (metadata, history, linked studies).',
		),
		array(
			'key'         => 'collection_edit',
			'label'       => 'Edit',
			'description' => 'Edit collection metadata (title, text, thumbnail, weight). Does not include publish or delete.',
		),
		array(
			'key'         => 'collection_publish',
			'label'       => 'Publish',
			'description' => 'Publish or unpublish this collection.',
		),
		array(
			'key'         => 'collection_delete',
			'label'       => 'Delete',
			'description' => 'Delete this collection.',
		),
		array(
			'key'         => 'collection_manage_access',
			'label'       => 'Manage access',
			'description' => 'Assign per-user study, licensed-request, and collection-admin grants on this collection.',
		),
		array(
			'key'         => 'collection_admin',
			'label'       => 'Admin',
			'description' => 'Full collection administration for this collection (implies view, edit, publish, delete, manage access).',
		),
	),
	'licensed_request_tiers' => array(
		array(
			'key'         => 'licensed_request_view',
			'label'       => 'View',
			'description' => 'View licensed survey requests for this collection.',
		),
		array(
			'key'         => 'licensed_request_edit',
			'label'       => 'Edit',
			'description' => 'Update request status, files, and settings.',
		),
		array(
			'key'         => 'licensed_request_delete',
			'label'       => 'Delete',
			'description' => 'Delete or cancel requests where applicable.',
		),
		array(
			'key'         => 'licensed_request_admin',
			'label'       => 'Admin',
			'description' => 'Full licensed-request administration for this collection.',
		),
	),
	'satisfiers' => array(
		'study_view'    => array('study_view', 'study_edit', 'study_delete', 'study_publish', 'study_admin'),
		'study_edit'    => array('study_edit', 'study_admin'),
		'study_create'  => array('study_edit', 'study_admin'),
		'study_delete'  => array('study_delete', 'study_admin'),
		'study_publish' => array('study_publish', 'study_admin'),
		'licensed_request_view'   => array(
			'licensed_request_view',
			'licensed_request_edit',
			'licensed_request_delete',
			'licensed_request_admin',
		),
		'licensed_request_edit'   => array('licensed_request_edit', 'licensed_request_admin'),
		'licensed_request_create' => array('licensed_request_edit', 'licensed_request_admin'),
		'licensed_request_delete' => array('licensed_request_delete', 'licensed_request_admin'),
		'collection_view'          => array(
			'collection_view',
			'collection_edit',
			'collection_publish',
			'collection_delete',
			'collection_manage_access',
			'collection_admin',
		),
		'collection_edit'          => array('collection_edit', 'collection_admin'),
		'collection_publish'       => array('collection_publish', 'collection_admin'),
		'collection_delete'        => array('collection_delete', 'collection_admin'),
		'collection_manage_access' => array('collection_manage_access', 'collection_admin'),
	),
);

