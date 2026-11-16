<?php

/**
 * Predogled HTML mailov (naloga 30) — NI del paketa.
 *
 * Zagon: php orodja/maili/predogled.php
 *
 * Iz paket/com_belauncerunde/administrator/mail/{,en-GB/}<kljuc>.html in izmišljene runde ustvari
 * dist/maili/<kljuc>.html (sl) in dist/maili/en-GB/<kljuc>.html za ogled v brskalniku. Oznake zamenja
 * po enakem algoritmu kot Joomla 6.1 MailTemplate::replaceTags() (bloki {OZNAKA}…{/OZNAKA}, ubežanje
 * htmlspecialchars za "unsafe" oznake). Preveri, da je vsak mail < 60 KB, brez base64 slik in brez
 * neobdelanih oznak. Izhodna koda 0 = vse OK.
 */

$koren = \dirname(__DIR__, 2);
$vir = $koren . '/paket/com_belauncerunde/administrator/mail';
$cilj = $koren . '/dist/maili';
$siteUrl = 'https://runde.belaunce.cc/';

/** Kopija algoritma Joomla 6.1.4 libraries/src/Mail/MailTemplate.php::replaceTags() (vse oznake so "unsafe"). */
function zamenjaj(string $besedilo, array $oznake): string
{
    foreach ($oznake as $kljuc => $vrednost) {
        if (\is_array($vrednost)) {
            $vzorec = preg_quote(strtoupper($kljuc), '/');

            if (preg_match_all('/{' . $vzorec . '}(.*?){\/' . $vzorec . '}/s', $besedilo, $zadetki)) {
                foreach ($zadetki[0] as $i => $zadetek) {
                    $nadomestek = '';

                    foreach ($vrednost as $element) {
                        $nadomestek .= zamenjaj($zadetki[1][$i], $element);
                    }

                    $besedilo = str_ireplace($zadetek, $nadomestek, $besedilo);
                }
            }
        } else {
            $besedilo = str_ireplace('{' . strtoupper($kljuc) . '}', htmlspecialchars((string) $vrednost, ENT_QUOTES, 'UTF-8'), $besedilo);
        }
    }

    return $besedilo;
}

$runda = [
    'sl' => [
        'SITEURL' => $siteUrl, 'NASLOV' => 'Sobotni krog po Poljanski dolini', 'DAN' => 'sobota', 'DATUM' => '21. 11. 2026',
        'URA' => '9:00', 'LOKACIJA' => 'Parkirišče pri OŠ Gorenja vas', 'DOLZINA' => '62 km', 'VODJA' => 'Matija',
        'TIP' => 'Cestno', 'TIP_BARVA' => '#f2b974', 'TIP_BARVA_BESEDILA' => '#000000', 'TEZAVNOST' => 'Turbo',
        'TEZAVNOST_BARVA' => '#e8eefb', 'TEZAVNOST_BARVA_BESEDILA' => '#2560c4',
        'PREDGLAVA' => 'Sobotni krog po Poljanski dolini · sobota, 21. 11. 2026 ob 9:00', 'SITENAME' => 'Runde Belaunce',
    ],
    'en' => [
        'SITEURL' => $siteUrl, 'NASLOV' => 'Saturday loop through Poljane valley', 'DAN' => 'Saturday', 'DATUM' => '21 Nov 2026',
        'URA' => '09:00', 'LOKACIJA' => 'Car park at Gorenja vas school', 'DOLZINA' => '62 km', 'VODJA' => 'Matija',
        'TIP' => 'Road', 'TIP_BARVA' => '#2a76e0', 'TIP_BARVA_BESEDILA' => '#ffffff', 'TEZAVNOST' => 'Turbo',
        'TEZAVNOST_BARVA' => '#e8eefb', 'TEZAVNOST_BARVA_BESEDILA' => '#2560c4',
        'PREDGLAVA' => 'Saturday loop through Poljane valley · Saturday, 21 Nov 2026 at 09:00', 'SITENAME' => 'Belaunce Rides',
    ],
];
$odjava = [['URL' => $siteUrl . 'index.php?option=com_belauncerunde&view=odjavagosta&zeton=abc123']];
$povezavaRunde = $siteUrl . 'runde/12-sobotni-krog';

$primeri = [];

foreach (['sl' => '', 'en' => 'en-GB/'] as $j => $podmapa) {
    $r = $runda[$j];
    $primeri[$podmapa . 'com_belauncerunde.prijava'] = ['SITEURL' => $siteUrl, 'SITENAME' => $r['SITENAME'], 'IME' => 'Ana', 'VELJAVNOST' => 15,
        'POVEZAVA' => $siteUrl . 'index.php?option=com_belauncerunde&view=povezava&zeton=abc123',
        'PREDGLAVA' => $j === 'sl' ? 'Povezava za prijavo velja 15 minut.' : 'The login link is valid for 15 minutes.'];
    // Gost: blok ODJAVA z enim elementom.
    $primeri[$podmapa . 'com_belauncerunde.gost_potrditev'] = $r + ['IME' => 'Ana', 'URL_RUNDE' => $povezavaRunde, 'ODJAVA' => $odjava];
    $primeri[$podmapa . 'com_belauncerunde.nova_prijava'] = $r + ['IME' => 'Ana Novak', 'STEVILO' => 7, 'POVEZAVA' => $povezavaRunde];
    // Sprememba za gosta (ODJAVA) z obema spremembama; ura pri spremembi je nova.
    $primeri[$podmapa . 'com_belauncerunde.runda_sprememba'] = array_merge($r, ['URA' => $j === 'sl' ? '10:00' : '10:00',
        'LOKACIJA' => $j === 'sl' ? 'Trg v Poljanah' : 'Poljane square', 'POVEZAVA' => $povezavaRunde, 'ODJAVA' => $odjava,
        'SPREMEMBE' => [
            ['NAZIV' => $j === 'sl' ? 'Nov začetek' : 'New start', 'VREDNOST' => $j === 'sl' ? 'sobota, 21. 11. 2026 ob 10:00' : 'Saturday, 21 Nov 2026 at 10:00'],
            ['NAZIV' => $j === 'sl' ? 'Nova lokacija' : 'New location', 'VREDNOST' => $j === 'sl' ? 'Trg v Poljanah' : 'Poljane square'],
        ]]);
    // Odpoved za člana: prazen blok ODJAVA mora izginiti.
    $primeri[$podmapa . 'com_belauncerunde.runda_odpoved'] = $r + ['POVEZAVA' => $siteUrl . 'runde', 'ODJAVA' => []];
}

@mkdir($cilj . '/en-GB', 0777, true);
$napak = 0;

foreach ($primeri as $ime => $oznake) {
    $html = file_get_contents($vir . '/' . $ime . '.html');

    if ($html === false) {
        echo "NAPAKA: manjka $ime.html\n";
        $napak++;
        continue;
    }

    $izhod = zamenjaj($html, $oznake);
    file_put_contents($cilj . '/' . $ime . '.html', $izhod);
    $kb = \strlen($izhod) / 1024;
    $ostanki = preg_match_all('/\{\/?[A-Z_]+\}/', $izhod, $m) ? implode(' ', array_unique($m[0])) : '';
    $base64 = str_contains($izhod, 'base64');
    $ok = $kb < 60 && $ostanki === '' && !$base64;
    $napak += $ok ? 0 : 1;
    printf("%-6s %-40s %5.1f KB%s%s\n", $ok ? 'ok' : 'NAPAKA', $ime, $kb,
        $ostanki !== '' ? '  neobdelano: ' . $ostanki : '', $base64 ? '  base64' : '');
}

echo $napak === 0 ? "Vsi predogledi OK ($cilj)\n" : "Napak: $napak\n";
exit($napak === 0 ? 0 : 1);
