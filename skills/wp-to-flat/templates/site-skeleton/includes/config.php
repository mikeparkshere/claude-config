<?php
// Production values. includes/config.local.php (gitignored, written on the server) overrides per environment.
$site = [
    'name'    => 'Example Business',
    'url'     => 'https://example.com',          // production origin, no trailing slash
    'phone'   => '(555) 555-0100',
    'email'   => 'hello@example.com',
    'address' => ['street' => '123 Main St', 'city' => 'Springfield', 'state' => 'OH', 'zip' => '45501'],
    'social'  => [],                              // ['facebook' => 'https://...']
    'ga_id'   => '',                              // GA4 measurement ID; loads on the production host only
    // Page registry: drives the nav and tools/build-sitemap.php. Order = nav order.
    'pages'   => [
        'home'    => ['path' => '/',         'label' => 'Home',    'nav' => true],
        'contact' => ['path' => '/contact/', 'label' => 'Contact', 'nav' => true],
    ],
];
$secrets = [];
