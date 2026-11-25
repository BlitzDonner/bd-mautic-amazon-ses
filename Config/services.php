<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\AmazonSESBundle\Mailer\TenantSesTransportFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Services',
        'Mailer',
    ];

    $services->load('MauticPlugin\\AmazonSESBundle\\', '../')
        ->exclude('../{' . implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)) . '}');

    // B&D: ses+api sendet mit SES-Tenant (Parameter amazon_ses_tenant in local.php, oder Header X-SES-TENANT).
    // Kein autoconfigure, sonst würde die Factory doppelt als mailer.transport_factory getaggt.
    $services->set(TenantSesTransportFactory::class)
        ->autoconfigure(false)
        ->decorate('mailer.transport_factory.amazon')
        ->args([
            service('.inner'),
            '%mautic.amazon_ses_tenant%',
            service('event_dispatcher')->nullOnInvalid(),
            service('http_client')->nullOnInvalid(),
            service('logger')->nullOnInvalid(),
        ]);
};
