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
        "description" => "Allows access to create, view, edit and delete collections",
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
            ]
        ]
    ], 
    "study"=>[ 
        "title" => "Manage studies",
        "description"=> "Site-wide access to studies across collections. Per-collection access is managed under Admin → Collections → Permissions (user grants).",
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
    "translate"=>[ 
        "title" => "Site translations",
        "description"=> "Manage translations",
        "permissions"=>[
            [
                "permission" => "edit"
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
	),
);

