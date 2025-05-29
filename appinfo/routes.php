<?php

return [
    'routes' => [
        [
            'name' => 'settings#index',
            'url' => '/settings',
            'verb' => 'GET',
        ],
        [
            'name' => 'settings#saveTime',
            'url' => '/settings/save-time',
            'verb' => 'POST',
        ],
        [
            'name' => 'settings#saveAlert',
            'url' => '/settings/save-alert',
            'verb' => 'POST',
        ],
        [
            'name' => 'settings#sendTestAlert',
            'url' => '/settings/send-test-alert',
            'verb' => 'POST',
        ],
    ],
];