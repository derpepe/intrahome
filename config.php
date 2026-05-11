<?php
return [
    'ecowitt' => [
        'api_key' => '0156d847-92aa-4070-b57a-eaa4ff864e01',
        'application_key' => 'E0020E479808206AF7C7885AA401FC5E',
        'mac_address' => 'F8:B3:B7:8E:8D:74',
        'room_names' => [
            'ch1' => 'Schlafzimmer',
            'ch2' => 'Badezimmer',
            'ch3' => 'Flur',
            'ch4' => 'Küche'
        ]
    ],
    'apc' => [
        // Lokaler Abruf der APC USV per SNMP (PowerNet-MIB)
        'host'            => '192.168.2.20',
        'community'       => 'public',
        'snmp_timeout_us' => 1500000, // 1.5s
        'snmp_retries'    => 1,
        'cache_seconds'   => 10,
    ]
];
