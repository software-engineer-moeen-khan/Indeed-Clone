<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | Hostinger shared hosting does not run a local Typesense daemon by default.
    | Use Scout's database engine unless SCOUT_DRIVER is explicitly configured.
    |
    */

    'driver' => env('SCOUT_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            // 'users' => [
            //     'searchableAttributes' => ['id', 'name', 'email'],
            //     'attributesForFaceting'=> ['filterOnly(email)'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // 'users' => [
            //     'filterableAttributes'=> ['id', 'name', 'email'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Typesense remains available when SCOUT_DRIVER=typesense is explicitly set.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'search_only_api_key' => env('TYPESENSE_SEARCH_ONLY_API_KEY'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],
        'model-settings' => [
            \App\Models\JobListing::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'job_title', 'type' => 'string', 'facet' => true],
                        ['name' => 'employer_name', 'type' => 'string', 'facet' => true],
                        ['name' => 'description', 'type' => 'string'],
                        ['name' => 'city', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'state', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'country', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'location_geopoint', 'type' => 'geopoint', 'optional' => true],
                        ['name' => 'employment_type', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'is_remote', 'type' => 'bool', 'optional' => true, 'facet' => true],
                        ['name' => 'job_category', 'type' => 'string', 'facet' => true, 'optional' => true],
                        ['name' => 'required_experience', 'type' => 'int32', 'facet' => true, 'optional' => true],
                        ['name' => 'salary_min', 'type' => 'int32', 'optional' => true, 'facet' => true],
                        ['name' => 'salary_max', 'type' => 'int32', 'optional' => true, 'facet' => true],
                        ['name' => 'salary_currency', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'salary_period', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'publisher', 'type' => 'string', 'optional' => true, 'facet' => true],
                        ['name' => 'posted_at', 'type' => 'int64', 'sort' => true],
                        ['name' => 'created_at', 'type' => 'int64', 'sort' => true],
                        ['name' => 'expired_at', 'type' => 'int64', 'optional' => true],
                        ['name' => 'skills', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                        ['name' => 'benefits', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                        ['name' => 'qualifications', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                        ['name' => 'responsibilities', 'type' => 'string[]', 'optional' => true, 'facet' => true],
                    ],
                    'default_sorting_field' => 'posted_at',
                    'enable_nested_fields' => true,
                ],
                'search-parameters' => [
                    'query_by' => 'job_title,employer_name,description,qualifications,responsibilities',
                    'query_by_weights' => '4,3,2,1,1',
                    'typo_tokens_threshold' => 1,
                    'num_typos' => 2,
                    'min_len_1typo' => 4,
                    'min_len_2typo' => 7,
                    'drop_tokens_threshold' => 1,
                    'enable_overrides' => true,
                    'prioritize_exact_match' => true,
                    'prioritize_token_position' => true,
                ],
            ],
        ],
    ],
];
