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
    "catalog"=>[ 
        "title" => "Studies listing page",
        "description" => "Allows access to the studies listing page to view, browse and search studies",
        "permissions"=>[
            [
                "permission" => "view",
                "description" => "Browse and search studies"
            ]            
        ]
            ], 
    "study"=>[ 
        "title" => "Manage studies",
        "description"=> "Global access to studies in all collections. For restricting access by collection, see <i>'Permissions by collection'</i> section below",
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
    ]    
];

//permissions by collections
$config['acl_permissions_collections'] = ['study','licensed_request'];

