<?php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tccinventario_ti',
        'user' => 'mnet_app',
        'pass' => 'troque-esta-senha',
    ],
    'scan' => [
        // Windows: C:\\Program Files (x86)\\Nmap\\nmap.exe
        // Linux:   /usr/bin/nmap
        'nmap_path' => '',
        'minimum_prefix' => 24,
        'timeout_seconds' => 45,
        'default_timeout_seconds' => 30,
        'max_detailed_hosts' => 32,
        'allowed_networks' => [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
        ],
    ],
];
