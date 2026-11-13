<?php

/**
 * Razvojni test kriptografije Web Push (NI del paketa).
 *
 * Zagon: php orodja/test-webpush.php
 *
 * 1. RFC 8291, Dodatek A: šifriranje z injiciranim efemernim ključem in soljo mora dati natanko
 *    testni vektor (izpiše UJEMA).
 * 2. VAPID (RFC 8292, ES256): podpis JWT z novim parom ključev, pretvorba DER → surovih 64 B → DER
 *    in preverjanje z openssl_verify (izpiše OK).
 * 3. Dodatno: povratno dešifriranje z zasebnim ključem prejemnika (izpiše OK).
 *
 * Izhodna koda 0 = vse uspelo, 1 = napaka.
 */

// PushService je Joomlin razred; za test zadošča konstanta, saj kriptografske metode ne kličejo jedra.
\define('_JEXEC', 1);

require __DIR__ . '/../paket/com_belauncerunde/administrator/src/Service/PushService.php';

use Belaunce\Component\Belauncerunde\Administrator\Service\PushService as P;

$napak = 0;

/** Izpiše rezultat preverjanja in šteje napake. */
function preveri(string $ime, bool $uspeh, string $ok, int &$napak): void
{
    echo $ime . ' ' . str_repeat('.', max(3, 52 - mb_strlen($ime))) . ' ' . ($uspeh ? $ok : 'NAPAKA') . PHP_EOL;

    if (!$uspeh) {
        $napak++;
    }
}

$d = static fn (string $niz): string => (string) P::base64urlDekodiraj($niz);

// --- 1) RFC 8291, Dodatek A ---------------------------------------------------------------
$vsebina = 'When I grow up, I want to be a watermelon';
$uaJavni = $d('BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4');
$uaZasebni = $d('q1dXpw3UpT5VOmu_cf_v6ih07Aems3njxI-JWgLcM94');
$asJavni = $d('BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8');
$asZasebni = $d('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw');
$auth = $d('BTBZMqHH6r4Tts7J_aSIgg');
$sol = $d('DGv6ra1nlYgDCS1FRnbzlw');
$pricakovano = 'DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzA'
    . 'C8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN';

try {
    $telo = P::sifriraj($vsebina, $uaJavni, $auth, $asZasebni, $asJavni, $sol);
    $dobljeno = P::base64urlKodiraj($telo);
    preveri('RFC 8291 Dodatek A (aes128gcm)', hash_equals($pricakovano, $dobljeno), 'UJEMA', $napak);

    if ($dobljeno !== $pricakovano) {
        echo '  pričakovano: ' . $pricakovano . PHP_EOL . '  dobljeno:    ' . $dobljeno . PHP_EOL;
    }
} catch (\Throwable $e) {
    preveri('RFC 8291 Dodatek A (aes128gcm)', false, 'UJEMA', $napak);
    echo '  ' . $e->getMessage() . PHP_EOL;
}

// --- 2) VAPID ES256 -----------------------------------------------------------------------
try {
    [$javni, $zasebni] = P::ustvariPar();
    preveri('Nov par P-256 (65 B / 32 B)', strlen($javni) === 65 && $javni[0] === "\x04" && strlen($zasebni) === 32, 'OK', $napak);

    $jwt = P::vapidJwt('https://fcm.googleapis.com', 'mailto:test@example.invalid', time() + 43200, $zasebni, $javni);
    $deli = explode('.', $jwt);
    $glava = json_decode((string) P::base64urlDekodiraj($deli[0] ?? ''), true);
    $trditve = json_decode((string) P::base64urlDekodiraj($deli[1] ?? ''), true);
    $surovi = (string) P::base64urlDekodiraj($deli[2] ?? '');
    $oblika = count($deli) === 3 && ($glava['alg'] ?? '') === 'ES256' && ($glava['typ'] ?? '') === 'JWT'
        && ($trditve['aud'] ?? '') === 'https://fcm.googleapis.com' && strlen($surovi) === 64;
    $preverjeno = openssl_verify($deli[0] . '.' . $deli[1], P::surovVDer($surovi),
        openssl_pkey_get_public(P::pemJavni($javni)), OPENSSL_ALGO_SHA256) === 1;
    preveri('VAPID JWT (ES256, DER→surovi→DER, openssl_verify)', $oblika && $preverjeno, 'OK', $napak);

    // Povratna pretvorba na več podpisih (tudi r/s z vodilnimi ničlami ali visokim bitom).
    $povratno = true;
    $kljuc = openssl_pkey_get_private(P::pemZasebni($zasebni, $javni));

    for ($i = 0; $i < 200; $i++) {
        openssl_sign('x' . $i, $der, $kljuc, OPENSSL_ALGO_SHA256);
        $povratno = $povratno && openssl_verify('x' . $i, P::surovVDer(P::derVSurovi($der)),
            openssl_pkey_get_public(P::pemJavni($javni)), OPENSSL_ALGO_SHA256) === 1;
    }

    preveri('DER ↔ surovi (200 podpisov)', $povratno, 'OK', $napak);
} catch (\Throwable $e) {
    preveri('VAPID JWT (ES256)', false, 'OK', $napak);
    echo '  ' . $e->getMessage() . PHP_EOL;
}

// --- 3) Povratno dešifriranje (naključni efemerni ključ in sol) --------------------------
try {
    $telo = P::sifriraj('{"title":"Test"}', $uaJavni, $auth);
    $sol2 = substr($telo, 0, 16);
    $as2 = substr($telo, 21, 65);
    $ecdh = openssl_pkey_derive(openssl_pkey_get_public(P::pemJavni($as2)),
        openssl_pkey_get_private(P::pemZasebni($uaZasebni, $uaJavni)), 0);
    $ikm = hash_hkdf('sha256', $ecdh, 32, "WebPush: info\0" . $uaJavni . $as2, $auth);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sol2);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sol2);
    $sifra = substr($telo, 86);
    $odprto = openssl_decrypt(substr($sifra, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($sifra, -16));
    preveri('Povratno dešifriranje (naključna sol)', $odprto === '{"title":"Test"}' . "\x02", 'OK', $napak);
} catch (\Throwable $e) {
    preveri('Povratno dešifriranje (naključna sol)', false, 'OK', $napak);
}

echo PHP_EOL . ($napak === 0 ? 'Vsi testi uspešni.' : 'Neuspešnih testov: ' . $napak) . PHP_EOL;
exit($napak === 0 ? 0 : 1);
