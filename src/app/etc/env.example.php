<?php
return [
    'backend' => [
        'frontName' => 'admin'
    ],
    'db' => [
        'connection' => [
            'indexer' => [
                'host' => 'mysql',
                'dbname' => 'mydatabase',
                'username' => 'root',
                'password' => '123456',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'persistent' => null
            ],
            'default' => [
                'host' => 'mysql',
                'dbname' => 'mydatabase',
                'username' => 'root',
                'password' => '123456',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ]
        ],
        'table_prefix' => ''
    ],
    'queue' => [
        'amqp' => [
            'host' => 'fox.rmq.cloudamqp.com',
            'port' => '5672',
            'user' => 'ekqpdkzz',
            'password' => 'UtstYVqXpPxEOJw2zxUbw5EGfv0pzdsX',
            'virtualhost' => 'ekqpdkzz'
        ],
        'consumers_wait_for_messages' => 1
    ],
    'crypt' => [
        'key' => '8b1ea684d9e0a0a2c86ebc4b2e09f244'
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'MAGE_MODE' => 'developer',
    'session' => [
        'save' => 'files'
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'id_prefix' => '69d_'
            ],
            'page_cache' => [
                'id_prefix' => '69d_'
            ]
        ],
        'allow_parallel_generation' => false
    ],
    'lock' => [
        'provider' => 'db',
        'config' => [
            'prefix' => ''
        ]
    ],
    'cache_types' => [
        'config' => 1,
        'layout' => 1,
        'block_html' => 1,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'compiled_config' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'full_page' => 1,
        'target_rule' => 1,
        'config_webservice' => 1,
        'translate' => 1,
        'vertex' => 1
    ],
    'downloadable_domains' => [

    ],
    'install' => [
        'date' => 'Tue, 29 Sep 2020 08:06:54 +0000'
    ],
    'cron_consumers_runner' => [
        'cron_run' => false,
        'max_messages' => 10,
        'consumers' => [
            'vkl'
        ]
    ],
    'directories' => [
        'document_root_is_pub' => false
    ]
];
