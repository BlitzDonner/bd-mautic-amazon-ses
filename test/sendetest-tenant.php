<?php

// Testversand über den echten Mautic-Container (Transport aus dem DSN der Instanz) an EINE Adresse.
// Aufruf auf dem Server: php sendetest-tenant.php <mautic-wurzel> <empfaenger> [tenant-header]
// Gibt weder DSN noch Schlüssel aus.
declare(strict_types=1);

[$self, $root, $to] = $argv + [null, null, null];
$headerTenant = $argv[3] ?? null;
chdir($root);
require $root.'/autoload.php';

$kernel = new AppKernel('prod', false);
$kernel->boot();
$c = $kernel->getContainer();

$dsn = $c->get('mautic.helper.core_parameters')->get('mailer_dsn');
$transport = $c->get('Mautic\EmailBundle\Mailer\Transport\TransportFactory')->fromString($dsn);
if (getenv('OHNE_TENANT')) { $transport = (new Symfony\Component\Mailer\Bridge\Amazon\Transport\SesTransportFactory())->create(Symfony\Component\Mailer\Transport\Dsn::fromString($dsn)); } // Negativkontrolle: Symfony-Transport ohne Tenant
echo 'Transport: '.get_class($transport)."\n";

$mail = (new Symfony\Component\Mime\Email())
    ->from(new Symfony\Component\Mime\Address('info@thelabhotel.ch', 'The Lab Hotel'))
    ->to($to)
    ->subject('Tenant-Test '.date('H:i:s'))
    ->text('Testmail Tenant thelabhotel, bitte ignorieren.');
$mail->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', 'thelabhotel');
if ($headerTenant) {
    $mail->getHeaders()->addTextHeader('X-SES-TENANT', $headerTenant);
}

try {
    $sent = $transport->send($mail);
    echo 'GESENDET, MessageId '.$sent->getMessageId()."\n";
} catch (Throwable $e) {
    echo 'FEHLER '.get_class($e).': '.substr($e->getMessage(), 0, 400)."\n";
    exit(1);
}
