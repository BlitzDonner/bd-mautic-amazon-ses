<?php

// Test: SES-API-Transport setzt TenantName (ohne Korrektur wäre der Test rot).
// Aufruf: php test/tenant-test.php <mautic-wurzel>  (braucht vendor/ von Mautic)
declare(strict_types=1);

require rtrim($argv[1] ?? '', '/').'/vendor/autoload.php';
require __DIR__.'/../Mailer/TenantSesApiTransport.php';
require __DIR__.'/../Mailer/TenantSesTransportFactory.php';

use MauticPlugin\AmazonSESBundle\Mailer\TenantSesApiTransport;
use MauticPlugin\AmazonSESBundle\Mailer\TenantSesTransportFactory;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesTransportFactory;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mime\Email;

function pruefe(bool $ok, string $was): void
{
    echo ($ok ? 'OK   ' : 'FAIL ').$was."\n";
    if (!$ok) {
        $GLOBALS['fehler'] = true;
    }
}

$factory = new TenantSesTransportFactory(new SesTransportFactory(), 'thelabhotel');
$transport = $factory->create(Dsn::fromString('ses+api://KEY:SECRET@default?region=eu-west-1'));
pruefe($transport instanceof TenantSesApiTransport, 'ses+api liefert Tenant-Transport');
pruefe(!($factory->create(Dsn::fromString('ses+smtp://KEY:SECRET@default')) instanceof TenantSesApiTransport), 'ses+smtp unverändert');

$request = function (Email $mail) use ($transport): AsyncAws\Ses\Input\SendEmailRequest {
    $m = new ReflectionMethod($transport, 'getRequest');

    return $m->invoke($transport, new SentMessage($mail, Symfony\Component\Mailer\DelayedEnvelope::class ? new Symfony\Component\Mailer\Envelope(new Symfony\Component\Mime\Address('a@x.ch'), [new Symfony\Component\Mime\Address('b@x.ch')]) : null));
};

$mail = (new Email())->from('a@x.ch')->to('b@x.ch')->subject('t')->text('t');
pruefe($request($mail)->getTenantName() === 'thelabhotel', 'Standard-Tenant gesetzt');

$mail = (new Email())->from('a@x.ch')->to('b@x.ch')->subject('t')->text('t');
$mail->getHeaders()->addTextHeader('X-SES-TENANT', 'anderer');
$r = $request($mail);
pruefe($r->getTenantName() === 'anderer', 'Header überschreibt Tenant');
$namen = array_map(fn ($h) => $h->getName(), $r->getContent()?->getSimple()?->getHeaders() ?? []);
pruefe(!in_array('X-SES-TENANT', $namen, true) && !$mail->getHeaders()->has('X-SES-TENANT'), 'Header wird entfernt');

exit(empty($GLOBALS['fehler']) ? 0 : 1);
