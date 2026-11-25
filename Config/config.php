<?php

// plugins/AmazonSESBundle/Config/config.php

return array(
    'name'        => 'AmazonSESBundle',
    'description' => 'Provides Amazon SES as an email transport and callback to process bounces',
    'author'      => 'Pablo Veintimilla Vargas, Blitz & Donner',
    'version'     => '1.1.0',
    'routes'   => array(),
    // Name des SES-Tenants; leer = ohne Tenant. In config/local.php überschreibbar.
    'parameters' => array(
        'amazon_ses_tenant' => '',
    ),
);
