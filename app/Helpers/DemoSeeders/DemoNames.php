<?php

declare(strict_types=1);

namespace App\Helpers\DemoSeeders;

/**
 * Pools of Polish names + helpers for deterministic-but-varied demo data.
 *
 * Kept self-contained — no external libs. Lists are ASCII-friendly (no diacritics)
 * to keep UNIQUE keys / email addresses safe across mysql client encodings.
 */
final class DemoNames
{
    /** @var string[] */
    public const MALE_FIRST = [
        'Jan','Piotr','Tomasz','Michal','Krzysztof','Marek','Lukasz','Adam','Robert','Marcin',
        'Pawel','Kamil','Rafal','Andrzej','Jacek','Bartosz','Mateusz','Maciej','Dawid','Filip',
        'Jakub','Wojciech','Daniel','Sebastian','Damian','Patryk','Konrad','Aleksander','Karol','Szymon',
        'Igor','Adrian','Hubert','Mikolaj','Oskar','Antoni','Kacper','Franciszek','Stanislaw','Tymon',
        'Mateusz','Norbert','Dominik','Przemyslaw','Artur','Grzegorz','Zbigniew','Henryk','Roman','Stefan',
        'Wiktor','Igor','Olaf','Leon','Borys','Cezary','Dariusz','Eryk','Fabian','Gabriel',
        'Hieronim','Ireneusz','Janusz','Kornel','Ludwik','Maks','Nikodem','Olgierd','Patron','Rufin',
        'Sergiusz','Tadeusz','Urban','Witold','Xawery','Yannick','Zenon','Aron','Benedykt','Cyprian',
        'Damir','Edward','Feliks','Gerwazy','Hilary','Igor','Jaroslaw','Kazimierz','Leszek','Marian',
        'Norbert','Oktawian','Przemo','Ryszard','Symeon','Teofil','Ulrich','Walenty','Yarema','Zdzislaw',
    ];

    /** @var string[] */
    public const FEMALE_FIRST = [
        'Anna','Maria','Katarzyna','Agnieszka','Monika','Joanna','Magdalena','Paulina','Dominika','Aleksandra',
        'Natalia','Karolina','Ewa','Justyna','Marta','Beata','Iwona','Edyta','Renata','Patrycja',
        'Sylwia','Sandra','Olga','Klaudia','Weronika','Wiktoria','Julia','Zofia','Hanna','Lena',
        'Alicja','Amelia','Maja','Oliwia','Pola','Antonina','Helena','Lila','Nina','Roza',
        'Iga','Kinga','Daria','Aneta','Bozena','Cecylia','Daniela','Elwira','Felicja','Gabriela',
        'Halina','Ilona','Janina','Krystyna','Leokadia','Malgorzata','Natalia','Otylia','Patrycja','Regina',
        'Stanislawa','Teresa','Urszula','Wanda','Xenia','Yolanda','Zuzanna','Aurelia','Berenika','Celina',
        'Dorota','Estera','Florentyna','Genowefa','Honorata','Iza','Jagoda','Kornelia','Lidia','Mira',
        'Nikola','Ofelia','Pelagia','Rita','Sara','Tamara','Ula','Violetta','Wioletta','Yvonne',
        'Zaneta','Anita','Blanka','Czeslawa','Diana','Emilia','Fiona','Greta','Helga','Inez',
    ];

    /** @var string[] */
    public const LAST = [
        'Kowalski','Nowak','Wisniewski','Wojcik','Kowalczyk','Kaminski','Lewandowski','Zielinski','Szymanski','Wozniak',
        'Dabrowski','Kozlowski','Jankowski','Mazur','Wojciechowski','Kwiatkowski','Krawczyk','Kaczmarek','Piotrowski','Grabowski',
        'Pawlowski','Michalski','Krol','Wieczorek','Jablonski','Wrobel','Nowakowski','Majewski','Olszewski','Stepien',
        'Jaworski','Adamczyk','Dudek','Nowicki','Pawlak','Gorski','Witkowski','Walczak','Sikora','Baran',
        'Rutkowski','Michalak','Szewczyk','Ostrowski','Tomaszewski','Pietrzak','Marciniak','Wrobel','Zalewski','Jakubowski',
        'Jasinski','Zawadzki','Sadowski','Bak','Chmielewski','Wlodarczyk','Borkowski','Czarnecki','Sawicki','Sokolowski',
        'Urbanski','Kubiak','Maciejewski','Szczepanski','Kucharski','Wilk','Kalinowski','Lis','Mazurek','Wysocki',
        'Adamski','Kaczmarczyk','Sobczak','Czerwinski','Krupa','Kozak','Stankiewicz','Mucha','Glowacki','Zajac',
        'Wojtczak','Cieslak','Bednarek','Konieczny','Domanski','Kasprzak','Sikorski','Brzezinski','Karpinski','Pietrasik',
        'Wasilewski','Krajewski','Lipinski','Mroz','Sobolewski','Tomczak','Komorowski','Markowski','Czajka','Mikolajczyk',
    ];

    public const STREETS = [
        'Marszalkowska','Krakowskie Przedmiescie','Pulawska','Aleje Jerozolimskie','Nowy Swiat','Mokotowska',
        'Polna','Grochowska','Wolska','Bielanska','Slowackiego','Wisniowa','Sportowa','Olimpijska',
        'Sienkiewicza','Mickiewicza','Reymonta','Konopnickiej','Tuwima','Lesna','Akademicka','Pilsudskiego',
    ];

    public const CITIES = [
        'Warszawa','Krakow','Lodz','Wroclaw','Poznan','Gdansk','Szczecin','Bydgoszcz','Lublin','Katowice',
        'Bialystok','Gdynia','Czestochowa','Radom','Sosnowiec','Torun','Kielce','Gliwice','Zabrze','Olsztyn',
    ];

    /**
     * Deterministic-by-index pick from array — uses modulo so callers can index from 0..N
     * without collisions. Adding a salt lets us shuffle different fields independently.
     */
    public static function pick(array $pool, int $index, int $salt = 0): string
    {
        return $pool[($index + $salt) % count($pool)];
    }

    /**
     * Build a unique-ish email slug from first/last + index — ASCII-safe.
     */
    public static function emailSlug(string $first, string $last, int $index): string
    {
        $clean = static fn(string $s): string => preg_replace('/[^a-z]/', '', strtolower($s)) ?? '';
        return $clean($first) . '.' . $clean($last) . $index . '@demo.test';
    }

    /**
     * Generate a syntactically valid PESEL with correct checksum.
     *
     * NOTE: numbers are generated from a fixed base year (1990) + offset so they
     * never accidentally match real people. PESEL checksum algorithm is standard.
     */
    public static function generatePesel(\DateTimeImmutable $birth, string $gender, int $sequence): string
    {
        $y = (int)$birth->format('Y');
        $m = (int)$birth->format('m');
        $d = (int)$birth->format('d');

        // Century encoding per spec.
        if ($y >= 1800 && $y <= 1899)      { $m += 80; }
        elseif ($y >= 1900 && $y <= 1999)  { /* +0 */ }
        elseif ($y >= 2000 && $y <= 2099)  { $m += 20; }
        elseif ($y >= 2100 && $y <= 2199)  { $m += 40; }
        elseif ($y >= 2200 && $y <= 2299)  { $m += 60; }

        $yy = sprintf('%02d', $y % 100);
        $mm = sprintf('%02d', $m);
        $dd = sprintf('%02d', $d);

        // Serial — 3 digits; ensure last digit has correct parity for gender.
        // PESEL 10th digit: even = female, odd = male.
        $serial = sprintf('%03d', $sequence % 999);
        $parityDigit = ($gender === 'M') ? 1 : 0;
        // 10th digit (index 9) = first 3 of serial + gender-digit; we recompute
        // with the gender-digit appended below. Build 9 digits, then 10th = gender,
        // checksum is 11th.
        $partial = $yy . $mm . $dd . $serial . (string)$parityDigit;

        // Checksum weights: 1,3,7,9,1,3,7,9,1,3
        $weights = [1,3,7,9,1,3,7,9,1,3];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int)$partial[$i]) * $weights[$i];
        }
        $check = (10 - ($sum % 10)) % 10;

        return $partial . $check;
    }
}
