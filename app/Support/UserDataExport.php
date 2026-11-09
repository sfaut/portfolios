<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/**
 * Archive ZIP des données d'un utilisateur, en fichiers CSV lisibles dans un tableur.
 *
 * L'archive contient son profil, ses portfolios, comptes, opérations, axes analytiques et répartitions
 * analytiques, archivés compris, et un fichier LISEZMOI.txt qui en décrit le format. Les données se
 * lisent sur les tables, filtrées explicitement sur l'utilisateur : l'export ne dépend ni de
 * l'utilisateur connecté ni des portées globales des modèles.
 *
 * Les CSV suivent l'usage des tableurs français : UTF-8 avec BOM, séparateur « ; », fins de ligne CRLF,
 * décimales à virgule, dates au format ISO (« 2026-11-09 », « 2026-11-09 14:30:00 »). Un texte qui
 * commence par « = », « + », « - », « @ », une tabulation ou un retour chariot est précédé d'une
 * apostrophe, pour qu'un tableur ne l'exécute pas comme une formule.
 */
class UserDataExport
{
    /**
     * Débuts de texte qu'un tableur interprète comme une formule.
     *
     * @var list<string>
     */
    public const array FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Description du format, placée dans l'archive sous LISEZMOI.txt.
     */
    public const string README = <<<'TEXT'
        Données exportées de Portfolios.

        Chaque fichier CSV est en UTF-8, avec le point-virgule pour séparateur et la virgule pour séparateur
        décimal, comme l'attendent les tableurs en français. Les dates sont au format AAAA-MM-JJ, les
        horodatages au format AAAA-MM-JJ HH:MM:SS (heure de Paris). Un texte qui commence par =, +, -
        ou @ est précédé d'une apostrophe, pour qu'un tableur ne l'exécute pas comme une formule.

        - utilisateur.csv : votre profil.
        - portfolios.csv : vos portfolios ; « archivé le » est vide pour un portfolio actif.
        - comptes.csv : les comptes de vos portfolios, archivés compris.
        - operations.csv : les opérations de ces comptes.
        - axes-analytiques.csv : les axes analytiques de vos portfolios, archivés compris.
        - repartitions-analytiques.csv : la part (en pourcentage) de chaque opération attribuée à un axe.

        Les colonnes « id », « portfolio_id », « compte_id », « operation_id » et « axe_id » relient les
        fichiers entre eux.
        TEXT;

    /**
     * @param  User  $user  Utilisateur dont les données sont exportées.
     */
    public function __construct(
        private readonly User $user,
    ) {
        //
    }

    /**
     * Nom proposé pour l'archive téléchargée, daté du jour.
     */
    public function fileName(): string
    {
        return 'portfolios-'.now()->format('Y-m-d').'.zip';
    }

    /**
     * Écrit l'archive dans le fichier donné, qu'elle remplace.
     *
     * @param  string  $path  Chemin du fichier ZIP à écrire.
     *
     * @throws RuntimeException
     *                          Quand l'archive ne peut pas être créée ou écrite.
     */
    public function writeTo(string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Archive impossible à créer : {$path}");
        }

        $zip->addFromString('LISEZMOI.txt', str_replace("\n", "\r\n", self::README)."\r\n");

        foreach ($this->csvFiles() as $name => $content) {
            $zip->addFromString($name, $content);
        }

        if (! $zip->close()) {
            throw new RuntimeException("Archive impossible à écrire : {$path}");
        }
    }

    /**
     * Contenu des fichiers CSV de l'archive, par nom de fichier.
     *
     * @return array<string, string>
     */
    public function csvFiles(): array
    {
        $portfolio_ids = DB::table('portfolios')->where('user_id', $this->user->id)->pluck('id');

        return [
            'utilisateur.csv' => $this->csv(
                ['nom', 'prénom', 'e-mail', 'e-mail vérifié le', 'inscrit le'],
                [[
                    $this->text($this->user->last_name),
                    $this->text($this->user->first_name),
                    $this->text($this->user->email),
                    $this->dateTime($this->user->email_verified_at),
                    $this->dateTime($this->user->created_at),
                ]],
            ),
            'portfolios.csv' => $this->csv(
                ['id', 'nom', 'description', 'créé le', 'archivé le'],
                DB::table('portfolios')
                    ->whereIn('id', $portfolio_ids)
                    ->lazyById()
                    ->map(fn (object $portfolio): array => [
                        $portfolio->id,
                        $this->text($portfolio->name),
                        $this->text($portfolio->description),
                        $this->dateTime($portfolio->created_at),
                        $this->dateTime($portfolio->deleted_at),
                    ]),
            ),
            'comptes.csv' => $this->csv(
                ['id', 'portfolio_id', 'portfolio', 'nom', 'description', 'créé le', 'archivé le'],
                DB::table('accounts')
                    ->join('portfolios', 'portfolios.id', '=', 'accounts.portfolio_id')
                    ->whereIn('accounts.portfolio_id', $portfolio_ids)
                    ->select('accounts.*', 'portfolios.name as portfolio_name')
                    ->lazyById(column: 'accounts.id', alias: 'id')
                    ->map(fn (object $account): array => [
                        $account->id,
                        $account->portfolio_id,
                        $this->text($account->portfolio_name),
                        $this->text($account->name),
                        $this->text($account->description),
                        $this->dateTime($account->created_at),
                        $this->dateTime($account->deleted_at),
                    ]),
            ),
            'operations.csv' => $this->csv(
                ['id', 'compte_id', 'compte', 'date', 'libellé', 'montant', 'créée le'],
                DB::table('operations')
                    ->join('accounts', 'accounts.id', '=', 'operations.account_id')
                    ->whereIn('accounts.portfolio_id', $portfolio_ids)
                    ->select('operations.*', 'accounts.name as account_name')
                    ->lazyById(column: 'operations.id', alias: 'id')
                    ->map(fn (object $operation): array => [
                        $operation->id,
                        $operation->account_id,
                        $this->text($operation->account_name),
                        $operation->delivery_at,
                        $this->text($operation->label),
                        $this->decimal($operation->amount),
                        $this->dateTime($operation->created_at),
                    ]),
            ),
            'axes-analytiques.csv' => $this->csv(
                ['id', 'portfolio_id', 'portfolio', 'nom', 'description', 'créé le', 'archivé le'],
                DB::table('analytic_axes')
                    ->join('portfolios', 'portfolios.id', '=', 'analytic_axes.portfolio_id')
                    ->whereIn('analytic_axes.portfolio_id', $portfolio_ids)
                    ->select('analytic_axes.*', 'portfolios.name as portfolio_name')
                    ->lazyById(column: 'analytic_axes.id', alias: 'id')
                    ->map(fn (object $axis): array => [
                        $axis->id,
                        $axis->portfolio_id,
                        $this->text($axis->portfolio_name),
                        $this->text($axis->name),
                        $this->text($axis->description),
                        $this->dateTime($axis->created_at),
                        $this->dateTime($axis->deleted_at),
                    ]),
            ),
            'repartitions-analytiques.csv' => $this->csv(
                ['operation_id', 'axe_id', 'axe', 'pourcentage'],
                DB::table('analytic_lines')
                    ->join('analytic_axes', 'analytic_axes.id', '=', 'analytic_lines.analytic_axis_id')
                    ->whereIn('analytic_axes.portfolio_id', $portfolio_ids)
                    ->select('analytic_lines.*', 'analytic_axes.name as axis_name')
                    ->lazyById(column: 'analytic_lines.id', alias: 'id')
                    ->map(fn (object $line): array => [
                        $line->operation_id,
                        $line->analytic_axis_id,
                        $this->text($line->axis_name),
                        $this->decimal($line->percentage),
                    ]),
            ),
        ];
    }

    /**
     * Écrit un fichier CSV : BOM, en-tête puis lignes.
     *
     * @param  list<string>  $header  Noms des colonnes.
     * @param  iterable<list<int|string>>  $rows  Lignes, déjà formatées.
     */
    private function csv(array $header, iterable $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        fwrite($stream, "\u{FEFF}");
        fputcsv($stream, $header, separator: ';', escape: '', eol: "\r\n");

        foreach ($rows as $row) {
            fputcsv($stream, $row, separator: ';', escape: '', eol: "\r\n");
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    /**
     * Texte saisi, neutralisé s'il commence comme une formule de tableur.
     */
    private function text(string $value): string
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
     */
    private function decimal(string $value): string
    {
        return str_replace('.', ',', $value);
    }

    /**
     * Horodatage à la seconde (« 2026-11-09 14:30:00 »), vide s'il est absent.
     */
    private function dateTime(Carbon|string|null $value): string
    {
        return $value === null ? '' : Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
