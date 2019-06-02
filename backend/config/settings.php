<?php declare(strict_types=1);

use Neucore\Application;
use GuzzleHttp\Client;

return [

    // Slim framework settings that can be customized by users
    'settings.httpVersion' => '1.1',
    'settings.responseChunkSize' => 4096,
    'settings.outputBuffering' => 'append',
    'settings.determineRouteBeforeAppMiddleware' => true,
    'settings.displayErrorDetails' => false,
    'settings.addContentLengthHeader' => false,
    'settings.routerCacheFile' => false,

    'config' => [

        'env_var_defaults' => [
            'BRAVECORE_EVE_DATASOURCE' => 'tranquility',
            'BRAVECORE_LOG_PATH'       => Application::ROOT_DIR . '/var/logs',
            'BRAVECORE_LOG_ROTATION'   => 'weekly',
            'BRAVECORE_CACHE_DIR'      => Application::ROOT_DIR . '/var/cache',
        ],

        'monolog' => [
            'path'     => '${BRAVECORE_LOG_PATH}',
            'rotation' => '${BRAVECORE_LOG_ROTATION}',
        ],

        'doctrine' => [
            'meta' => [
                'entity_paths' => [
                    Application::ROOT_DIR . '/src/classes/Entity'
                ],
                'dev_mode' => false,
                'proxy_dir' =>  '${BRAVECORE_CACHE_DIR}/proxies'
            ],
            'connection' => [
                'url' => '${BRAVECORE_DATABASE_URL}'
            ],
            'driver_options' => [
                'mysql_ssl_ca'             => '${BRAVECORE_MYSQL_SSL_CA}',
                'mysql_verify_server_cert' => '${BRAVECORE_MYSQL_VERIFY_SERVER_CERT}',
            ],
            'data_fixtures' => Application::ROOT_DIR . '/src/classes/DataFixtures'
        ],

        'CORS' => [
            'allow_origin' => '${BRAVECORE_ALLOW_ORIGIN}',
        ],

        'eve' => [
            'client_id'       => '${BRAVECORE_EVE_CLIENT_ID}',
            'secret_key'      => '${BRAVECORE_EVE_SECRET_KEY}',
            'callback_url'    => '${BRAVECORE_EVE_CALLBACK_URL}',
            'scopes'          => '${BRAVECORE_EVE_SCOPES}',
            'datasource'      => '${BRAVECORE_EVE_DATASOURCE}',
            'esi_host'        => 'https://esi.evetech.net',
            'oauth_urls_tq'   => [
                'authorize' => 'https://login.eveonline.com/oauth/authorize',
                'token'     => 'https://login.eveonline.com/oauth/token',
                'verify'    => 'https://login.eveonline.com/oauth/verify',
            ],
            'oauth_urls_sisi' => [
                'authorize' => 'https://sisilogin.testeveonline.com/oauth/authorize',
                'token'     => 'https://sisilogin.testeveonline.com/oauth/token',
                'verify'    => 'https://sisilogin.testeveonline.com/oauth/verify',
            ],
        ],

        'guzzle' => [
            'cache' => [
                'dir' => '${BRAVECORE_CACHE_DIR}/http'
            ],
            'user_agent' => 'Neucore/' . NEUCORE_VERSION . ' (https://github.com/tkhamez/neucore) ' .
                            'GuzzleHttp/' . Client::VERSION,
        ],

        'di' => [
            'cache_dir' => '${BRAVECORE_CACHE_DIR}/di'
        ]
    ],
];
