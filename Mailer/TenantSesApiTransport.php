<?php

declare(strict_types=1);

namespace MauticPlugin\AmazonSESBundle\Mailer;

use AsyncAws\Ses\Input\SendEmailRequest;
use AsyncAws\Ses\SesClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Message;

/**
 * SES-API-Transport mit TenantName. Symfony setzt nur ConfigurationSetName;
 * ohne TenantName läuft der Versand ausserhalb des SES-Tenants.
 * Quelle des Tenants: Header X-SES-TENANT (wird entfernt), sonst Parameter amazon_ses_tenant (local.php); beides leer = kein Tenant.
 */
class TenantSesApiTransport extends SesApiAsyncAwsTransport
{
    public const HEADER = 'X-SES-TENANT';

    public function __construct(
        SesClient $sesClient,
        private readonly string $tenant,
        ?EventDispatcherInterface $dispatcher = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($sesClient, $dispatcher, $logger);
    }

    protected function getRequest(SentMessage $message): SendEmailRequest
    {
        $tenant = $this->tenant;
        $original = $message->getOriginalMessage();
        if ($original instanceof Message && ($header = $original->getHeaders()->get(self::HEADER))) {
            $tenant = $header->getBodyAsString();
            // Header darf nicht als Mailheader an SES gehen
            $original->getHeaders()->remove(self::HEADER);
        }

        $request = parent::getRequest($message);
        if ('' !== $tenant) {
            $request->setTenantName($tenant);
        }

        return $request;
    }
}
