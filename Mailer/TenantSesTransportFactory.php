<?php

declare(strict_types=1);

namespace MauticPlugin\AmazonSESBundle\Mailer;

use AsyncAws\Core\Configuration;
use AsyncAws\Ses\SesClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Decorator von mailer.transport_factory.amazon: ses+api liefert den Tenant-Transport,
 * alles andere geht unverändert an die Symfony-Factory (siehe services.php).
 */
class TenantSesTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        private readonly TransportFactoryInterface $inner,
        private readonly string $tenant,
        ?EventDispatcherInterface $dispatcher = null,
        ?HttpClientInterface $client = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($dispatcher, $client, $logger);
    }

    public function supports(Dsn $dsn): bool
    {
        return $this->inner->supports($dsn);
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if ('ses+api' !== $dsn->getScheme()) {
            return $this->inner->create($dsn);
        }

        // gleiche Optionen wie Symfony SesTransportFactory 7.4
        $options = [
            'region' => $dsn->getOption('region') ?: 'eu-west-1',
            'accessKeyId' => $dsn->getUser(),
            'accessKeySecret' => $dsn->getPassword(),
        ] + (
            'default' === $dsn->getHost() ? [] : ['endpoint' => 'https://'.$dsn->getHost().($dsn->getPort() ? ':'.$dsn->getPort() : '')]
        ) + (
            null === $dsn->getOption('session_token') ? [] : ['sessionToken' => $dsn->getOption('session_token')]
        );

        $client = new SesClient(Configuration::create($options), null, $this->client, $this->logger);

        return new TenantSesApiTransport($client, $this->tenant, $this->dispatcher, $this->logger);
    }

    protected function getSupportedSchemes(): array
    {
        return ['ses', 'ses+api', 'ses+https', 'ses+smtp', 'ses+smtps'];
    }
}
