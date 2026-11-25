<?php

// Ende-zu-Ende-Test mit dem SES-Simulator (kein echter Empfänger):
// success@ und bounce@simulator.amazonses.com über den Mautic-Transport (Tenant), dann prüfen,
// ob der Rückläufer per SNS-Callback in Mautic als "Nicht kontaktieren" landet.
// Aufruf auf dem Server: php sendetest-simulator.php <mautic-wurzel> <absender>
// Legt den Testkontakt an und löscht ihn am Ende. Gibt weder DSN noch Schlüssel aus.
declare(strict_types=1);

[$self, $root, $from] = $argv + [null, null, null];
chdir($root);
require $root.'/autoload.php';

$kernel = new AppKernel('prod', false);
$kernel->boot();
$c = $kernel->getContainer();
$db = $c->get('doctrine')->getConnection();
$p = $c->get('mautic.helper.core_parameters')->get('db_table_prefix') ?? '';

$transport = $c->get('Mautic\EmailBundle\Mailer\Transport\TransportFactory')
    ->fromString($c->get('mautic.helper.core_parameters')->get('mailer_dsn'));
echo 'Transport: '.get_class($transport)."\n";

$bounce = 'bounce@simulator.amazonses.com';
$db->executeStatement("DELETE FROM {$p}leads WHERE email IN (?, ?)", [$bounce, 'success@simulator.amazonses.com']);
$db->executeStatement("INSERT INTO {$p}leads (date_added, email, points, date_identified) VALUES (NOW(), ?, 0, NOW())", [$bounce]);
$id = (int) $db->fetchOne("SELECT id FROM {$p}leads WHERE email = ?", [$bounce]);

foreach (['success@simulator.amazonses.com', $bounce] as $to) {
    $mail = (new Symfony\Component\Mime\Email())
        ->from(new Symfony\Component\Mime\Address($from, 'The Lab Hotel'))
        ->to($to)->subject('Simulator '.date('H:i:s'))->text('Simulator-Test, kein echter Empfänger.');
    $mail->getHeaders()->addTextHeader('X-SES-CONFIGURATION-SET', 'thelabhotel');
    $sent = $transport->send($mail);
    echo "GESENDET an $to, MessageId ".$sent->getMessageId()."\n";
}

// Rückläufer abwarten (SNS, bis 90 s)
$row = null;
for ($i = 0; $i < 18; ++$i) {
    sleep(5);
    $row = $db->fetchAssociative("SELECT reason, comments FROM {$p}lead_donotcontact WHERE lead_id = ? AND channel = 'email'", [$id]);
    if ($row) {
        echo 'DNC gesetzt: reason='.$row['reason'].' comments='.substr((string) $row['comments'], 0, 80)."\n";
        break;
    }
}
if (!$row) {
    echo "KEIN DNC nach 90 s\n";
}

$db->executeStatement("DELETE FROM {$p}leads WHERE id = ?", [$id]);
exit($row ? 0 : 1);
