<?php

/**
 * Razvojni test validacije endpointa push naročnine (NI del paketa).
 *
 * Zagon: php orodja/test-endpoint.php
 * Preveri PushModel::veljavenEndpoint() (SSRF: samo https znanih push storitev). Izhodna koda 0 = vse OK.
 */

// Stub Joomlinega razreda: veljavenEndpoint() je statičen in ne potrebuje baze.
namespace Joomla\CMS\MVC\Model { class BaseDatabaseModel {} }
namespace { define('_JEXEC', 1); require __DIR__ . '/../paket/com_belauncerunde/site/src/Model/PushModel.php';
use Belaunce\Component\Belauncerunde\Site\Model\PushModel as M;
$t = [
 // dovoljeno
 'https://fcm.googleapis.com/fcm/send/abc' => true,
 'https://web.push.apple.com/QGabc' => true,
 'https://updates.push.services.mozilla.com/wpush/v2/x' => true,
 'https://wns2-db5p.notify.windows.com/w/?token=x' => true,
 'https://FCM.googleapis.com./fcm/send/abc' => true,
 // zavrnjeno: neznane storitve in posnemanja
 'https://example.com/push' => false,
 'https://fcm.googleapis.com.evil.com/x' => false,
 'https://evil-fcm.googleapis.com/x' => false,
 'https://push.apple.com/x' => false,
 'https://evilpush.apple.com/x' => false,
 'https://push.services.mozilla.com.evil.com/x' => false,
 'https://notify.windows.com/x' => false,
 // zavrnjeno: prej obstoječi primeri
 'http://fcm.googleapis.com/x' => false,
 'https://localhost/x' => false,
 'https://127.0.0.1/x' => false,
 'https://[::1]/x' => false,
 'https://a.localhost/x' => false,
 'https://intranet/x' => false,
 'https://user:pw@fcm.googleapis.com/x' => false,
 'ftp://fcm.googleapis.com/x' => false,
 'https://10.0.0.1:8443/x' => false,
 'https://fcm.googleapis.com/' . str_repeat('a', 2100) => false,
 "https://fcm.googleapis.com/a b" => false,
];
$napak = 0;
foreach ($t as $u => $p) { $r = M::veljavenEndpoint($u); $ok = $r === $p; $napak += $ok ? 0 : 1;
 echo ($ok ? 'ok     ' : 'NAPAKA ') . ($p ? 'sprejmi ' : 'zavrni  ') . substr($u, 0, 58) . "\n"; }
echo ($napak === 0 ? "Vsi primeri (" . count($t) . ") OK" : "Napak: $napak") . "\n"; exit($napak ? 1 : 0); }
