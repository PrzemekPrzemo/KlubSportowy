<?php

namespace App\Sports\Curling;

/**
 * Metadata modulu Curling.
 * Druzyna 4-osobowa (Skip / Third / Second / Lead), mecze rozgrywane na endy.
 * Hammer (ostatni kamien w endzie) przechodzi do druzyny ktora w poprzednim
 * endzie nie zdobyla punktu (lub do gosci w endzie 1, jezeli losowanie).
 */
class CurlingModule
{
    public const KEY              = 'curling';
    public const FEDERATION_CODE  = 'PZCurl';
    public const POSITIONS        = ['skip', 'third', 'second', 'lead', 'alternate'];
    public const MATCH_FORMAT     = ['ends' => 8, 'ends_max' => 10];
    public const TEAM_SIZE        = 4;
    public const ROSTER_MAX       = 5;

    public function metadata(): array
    {
        return [
            'key'             => self::KEY,
            'name'            => 'Curling',
            'federation_code' => self::FEDERATION_CODE,
            'positions'       => self::POSITIONS,
            'match_format'    => self::MATCH_FORMAT,
            'team_size'       => self::TEAM_SIZE,
            'roster_max'      => self::ROSTER_MAX,
            'team_sport'      => true,
        ];
    }

    /** Po endzie X zwroc nastepna strone z hammerem (alternacja). */
    public function nextHammer(string $previousHammer, int $homeScoredInEnd, int $awayScoredInEnd): string
    {
        // Hammer trafia do druzyny ktora NIE zdobyla punktow w tym endzie
        // (jezeli blank end — pozostaje przy aktualnym posiadaczu).
        if ($homeScoredInEnd > 0 && $awayScoredInEnd === 0) return 'away';
        if ($awayScoredInEnd > 0 && $homeScoredInEnd === 0) return 'home';
        return $previousHammer; // blank end lub stealed — hammer zostaje
    }

    public function defaultFormations(): array
    {
        return [
            'standard 4-osobowy' => self::POSITIONS,
            'mixed doubles (2)'  => ['skip', 'lead'],
        ];
    }
}
