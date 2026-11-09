<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Écriture des fichiers CSV de l'application, lisibles dans un tableur français.
 *
 * Un fichier est en UTF-8 avec BOM, séparateur « ; », fins de ligne CRLF. Les valeurs se formatent
 * par les méthodes de la classe : décimales à virgule (decimal()), horodatages au format ISO
 * (dateTime()), et textes saisis neutralisés (text()). Un texte qui commence par « = », « + »,
 * « - », « @ », une tabulation ou un retour chariot est précédé d'une apostrophe, pour qu'un tableur
 * ne l'exécute pas comme une formule.
 */
final class Csv
{
    /**
     * Débuts de texte qu'un tableur interprète comme une formule.
     *
     * @var list<string>
     */
    public const array FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Écrit un fichier CSV dans un flux : BOM, en-tête puis lignes.
     *
     * @param  resource  $stream  Flux ouvert en écriture.
     * @param  list<string>  $header  Noms des colonnes.
     * @param  iterable<list<int|string>>  $rows  Lignes, déjà formatées.
     */
    public static function write($stream, array $header, iterable $rows): void
    {
        fwrite($stream, "\u{FEFF}");
        fputcsv($stream, $header, separator: ';', escape: '', eol: "\r\n");

        foreach ($rows as $row) {
            fputcsv($stream, $row, separator: ';', escape: '', eol: "\r\n");
        }
    }

    /**
     * Contenu d'un fichier CSV : BOM, en-tête puis lignes.
     *
     * @param  list<string>  $header  Noms des colonnes.
     * @param  iterable<list<int|string>>  $rows  Lignes, déjà formatées.
     */
    public static function content(array $header, iterable $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        self::write($stream, $header, $rows);

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    /**
     * Texte saisi, neutralisé s'il commence comme une formule de tableur.
     *
     * @param  string  $value  Texte à écrire.
     */
    public static function text(string $value): string
    {
        foreach (self::FORMULA_PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return "'{$value}";
            }
        }

        return $value;
    }

    /**
     * Nombre décimal de la base (« -10.50 »), à virgule (« -10,50 »).
     *
     * @param  string  $value  Nombre à point décimal.
     */
    public static function decimal(string $value): string
    {
        return str_replace('.', ',', $value);
    }

    /**
     * Horodatage à la seconde (« 2026-11-09 14:30:00 »), vide s'il est absent.
     *
     * @param  Carbon|string|null  $value  Horodatage, ou null.
     */
    public static function dateTime(Carbon|string|null $value): string
    {
        return $value === null ? '' : Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
