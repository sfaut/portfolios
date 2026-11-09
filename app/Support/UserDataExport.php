<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/**
 * Archive ZIP des données d'un utilisateur, en fichiers CSV lisibles dans un tableur.
 *
 * L'archive contient son profil, ses portfolios, comptes, opérations, axes analytiques et répartitions
 * analytiques, archivés compris, sa photo de profil s'il en a une, et un fichier LISEZMOI.txt qui en
 * décrit le format. Les données se
 * lisent sur les tables, filtrées explicitement sur l'utilisateur : l'export ne dépend ni de
 * l'utilisateur connecté ni des portées globales des modèles.
 *
 * Les CSV sont écrits par App\Support\Csv, selon l'usage des tableurs français : UTF-8 avec BOM,
 * séparateur « ; », fins de ligne CRLF, décimales à virgule, dates au format ISO (« 2026-11-09 »,
 * « 2026-11-09 14:30:00 »), textes qui commencent comme une formule neutralisés par une apostrophe.
 */
class UserDataExport
{
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
        - photo.webp : votre photo de profil, si vous en avez une.

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

        $avatar = $this->user->avatar_path === '' ? null : Storage::disk('local')->get($this->user->avatar_path);

        if ($avatar !== null) {
            $zip->addFromString('photo.webp', $avatar);
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
            'utilisateur.csv' => Csv::content(
                ['nom', 'prénom', 'e-mail', 'thème', 'e-mail vérifié le', 'inscrit le'],
                [[
                    Csv::text($this->user->last_name),
                    Csv::text($this->user->first_name),
                    Csv::text($this->user->email),
                    $this->user->color_scheme->value,
                    Csv::dateTime($this->user->email_verified_at),
                    Csv::dateTime($this->user->created_at),
                ]],
            ),
            'portfolios.csv' => Csv::content(
                ['id', 'nom', 'description', 'créé le', 'archivé le'],
                DB::table('portfolios')
                    ->whereIn('id', $portfolio_ids)
                    ->lazyById()
                    ->map(fn (object $portfolio): array => [
                        $portfolio->id,
                        Csv::text($portfolio->name),
                        Csv::text($portfolio->description),
                        Csv::dateTime($portfolio->created_at),
                        Csv::dateTime($portfolio->deleted_at),
                    ]),
            ),
            'comptes.csv' => Csv::content(
                ['id', 'portfolio_id', 'portfolio', 'nom', 'description', 'créé le', 'archivé le'],
                DB::table('accounts')
                    ->join('portfolios', 'portfolios.id', '=', 'accounts.portfolio_id')
                    ->whereIn('accounts.portfolio_id', $portfolio_ids)
                    ->select('accounts.*', 'portfolios.name as portfolio_name')
                    ->lazyById(column: 'accounts.id', alias: 'id')
                    ->map(fn (object $account): array => [
                        $account->id,
                        $account->portfolio_id,
                        Csv::text($account->portfolio_name),
                        Csv::text($account->name),
                        Csv::text($account->description),
                        Csv::dateTime($account->created_at),
                        Csv::dateTime($account->deleted_at),
                    ]),
            ),
            'operations.csv' => Csv::content(
                ['id', 'compte_id', 'compte', 'date effective', 'libellé', 'montant', 'horodatage écriture'],
                DB::table('operations')
                    ->join('accounts', 'accounts.id', '=', 'operations.account_id')
                    ->whereIn('accounts.portfolio_id', $portfolio_ids)
                    ->select('operations.*', 'accounts.name as account_name')
                    ->lazyById(column: 'operations.id', alias: 'id')
                    ->map(fn (object $operation): array => [
                        $operation->id,
                        $operation->account_id,
                        Csv::text($operation->account_name),
                        $operation->delivery_at,
                        Csv::text($operation->label),
                        Csv::decimal($operation->amount),
                        Csv::dateTime($operation->created_at),
                    ]),
            ),
            'axes-analytiques.csv' => Csv::content(
                ['id', 'portfolio_id', 'portfolio', 'nom', 'description', 'créé le', 'archivé le'],
                DB::table('analytic_axes')
                    ->join('portfolios', 'portfolios.id', '=', 'analytic_axes.portfolio_id')
                    ->whereIn('analytic_axes.portfolio_id', $portfolio_ids)
                    ->select('analytic_axes.*', 'portfolios.name as portfolio_name')
                    ->lazyById(column: 'analytic_axes.id', alias: 'id')
                    ->map(fn (object $axis): array => [
                        $axis->id,
                        $axis->portfolio_id,
                        Csv::text($axis->portfolio_name),
                        Csv::text($axis->name),
                        Csv::text($axis->description),
                        Csv::dateTime($axis->created_at),
                        Csv::dateTime($axis->deleted_at),
                    ]),
            ),
            'repartitions-analytiques.csv' => Csv::content(
                ['operation_id', 'axe_id', 'axe', 'pourcentage'],
                DB::table('analytic_lines')
                    ->join('analytic_axes', 'analytic_axes.id', '=', 'analytic_lines.analytic_axis_id')
                    ->whereIn('analytic_axes.portfolio_id', $portfolio_ids)
                    ->select('analytic_lines.*', 'analytic_axes.name as axis_name')
                    ->lazyById(column: 'analytic_lines.id', alias: 'id')
                    ->map(fn (object $line): array => [
                        $line->operation_id,
                        $line->analytic_axis_id,
                        Csv::text($line->axis_name),
                        Csv::decimal($line->percentage),
                    ]),
            ),
        ];
    }
}
