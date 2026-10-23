<?php

namespace Belaunce\Component\Belauncerunde\Site\Helper;

\defined('_JEXEC') or die;

/** Skupno oblikovanje javnih vrednosti za predloge rund. */
final class VidezHelper
{
    /** Vrne preverjeno in razširjeno barvo; pokvarjen zapis nikoli ne pride v CSS. */
    public static function barvaTipa(?string $hex): string
    {
        $hex = trim((string) $hex);

        if (!preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/D', $hex)) {
            return '#6c757d';
        }

        if (strlen($hex) === 4) {
            $hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        }

        return strtolower($hex);
    }

    /** Izbere svetlo ali temno besedilo z višjim kontrastom po WCAG svetlosti. */
    public static function kontrastBesedila(string $hex): string
    {
        $svetlost = self::svetlost(self::barvaTipa($hex));
        $temna = self::svetlost('#1d1a16');
        $kontrastSvetla = 1.05 / ($svetlost + 0.05);
        $kontrastTemna = (max($svetlost, $temna) + 0.05) / (min($svetlost, $temna) + 0.05);

        return $kontrastTemna > $kontrastSvetla ? '#1d1a16' : '#ffffff';
    }

    /** Izračuna relativno svetlost treh lineariziranih kanalov sRGB. */
    private static function svetlost(string $hex): float
    {
        $kanali = [];

        foreach ([1, 3, 5] as $odmik) {
            $kanal = hexdec(substr($hex, $odmik, 2)) / 255;
            $kanali[] = $kanal <= 0.04045 ? $kanal / 12.92 : (($kanal + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $kanali[0] + 0.7152 * $kanali[1] + 0.0722 * $kanali[2];
    }

    /** Vrne prvo črko javnega imena vodje za okrogel podpis kartice. */
    public static function zacetnicaVodje(?string $ime): string
    {
        $ime = trim((string) $ime);

        return $ime === '' ? '?' : mb_strtoupper(mb_substr($ime, 0, 1, 'UTF-8'), 'UTF-8');
    }

    /** Oblikuje shranjeno decimalno dolžino brez odvečne ničle. */
    public static function dolzina($kilometri): string
    {
        $vrednost = number_format((float) $kilometri, 1, ',', '');

        return str_ends_with($vrednost, ',0') ? substr($vrednost, 0, -2) : $vrednost;
    }

    /** Vrne položaj težavnosti med objavljenimi šifranti po ordering in ID. */
    public static function rangTezavnosti(int $id, array $tezavnosti): int
    {
        foreach ($tezavnosti as $indeks => $tezavnost) {
            if ((int) $tezavnost->id === $id) {
                return $indeks + 1;
            }
        }

        return 0;
    }
}
