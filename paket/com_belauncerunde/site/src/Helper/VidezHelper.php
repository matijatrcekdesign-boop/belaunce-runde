<?php

namespace Belaunce\Component\Belauncerunde\Site\Helper;

\defined('_JEXEC') or die;

/** Skupno oblikovanje javnih vrednosti za predloge rund. */
final class VidezHelper
{
    /** Vrne razred barve izključno za znane aliase; novi tipi so nevtralni. */
    public static function razredTipa(?string $alias): string
    {
        return match (strtolower((string) $alias)) {
            'gravel' => 'br-type--gravel',
            'mtb' => 'br-type--mtb',
            'cestno' => 'br-type--cestno',
            default => 'br-type--privzeto',
        };
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
