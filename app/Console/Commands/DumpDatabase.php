<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Output\OutputInterface;

#[Signature('db:dump {--connection= : Connexion à copier, celle de l\'application par défaut}')]
#[Description('Écrit sur la sortie standard une copie SQL complète de la base MySQL, par mysqldump')]
class DumpDatabase extends Command
{
    /**
     * Options de mysqldump, pour une copie complète et restaurable telle quelle.
     *
     * - `--single-transaction` : copie cohérente, sans bloquer l'application (InnoDB) ;
     * - `--routines`, `--triggers`, `--events` : procédures et fonctions, déclencheurs, événements
     *   planifiés, avec les tables et les vues, copiées d'office ;
     * - `--hex-blob` : colonnes binaires en hexadécimal, sans risque d'altération ;
     * - `--no-tablespaces` : sans les instructions d'espaces de stockage, qui demandent le privilège
     *   PROCESS, et dont une base InnoDB ordinaire n'a pas besoin ;
     * - `--add-drop-database`, `--databases` : la copie supprime puis recrée la base, avec son jeu de
     *   caractères et sa collation ; une restauration rend donc exactement l'état copié, sans les
     *   objets créés depuis.
     *
     * @var list<string>
     */
    public const array MYSQLDUMP_OPTIONS = [
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        '--hex-blob',
        '--no-tablespaces',
        '--add-drop-database',
        '--databases',
    ];

    /**
     * Copie la base de la connexion par mysqldump, dont la sortie passe telle quelle sur la sortie
     * standard, et les erreurs sur la sortie d'erreur.
     *
     * Les accès viennent de la configuration de Laravel. Ils passent à mysqldump par un fichier
     * d'options temporaire, lisible par le seul compte qui lance la commande, et supprimé ensuite : le
     * mot de passe n'apparaît pas dans la ligne de commande, que tout compte du serveur peut lire.
     */
    public function handle(): int
    {
        $connection_name = $this->option('connection') ?? config('database.default');
        $connection = config("database.connections.{$connection_name}");

        if (($connection['driver'] ?? null) !== 'mysql') {
            $this->output->getErrorStyle()->error("La connexion « {$connection_name} » n'est pas une connexion MySQL.");

            return self::FAILURE;
        }

        // tempnam() crée le fichier en 0600 : lisible par le seul compte courant.
        $options_file = tempnam(sys_get_temp_dir(), 'mysqldump-');

        try {
            file_put_contents($options_file, $this->optionsFileContents($connection));

            $result = Process::forever()->run(
                [
                    'mysqldump',
                    // Doit précéder toute autre option.
                    "--defaults-extra-file={$options_file}",
                    ...self::MYSQLDUMP_OPTIONS,
                    $connection['database'],
                ],
                function (string $type, string $output): void {
                    $output_style = $type === 'err' ? $this->output->getErrorStyle() : $this->output;
                    $output_style->write($output, false, OutputInterface::OUTPUT_RAW);
                },
            );
        } finally {
            unlink($options_file);
        }

        return $result->successful() ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Fichier d'options de MySQL donnant à mysqldump les accès de la connexion.
     *
     * Les valeurs sont entre guillemets, leurs « \ » et « " » échappés : un mot de passe peut contenir
     * n'importe quel caractère. Un socket Unix, s'il est configuré, remplace l'hôte et le port.
     *
     * @param  array<string, mixed>  $connection  Configuration de la connexion MySQL.
     */
    private function optionsFileContents(array $connection): string
    {
        $options = filled($connection['unix_socket'] ?? null)
            ? ['socket' => $connection['unix_socket']]
            : ['host' => $connection['host'], 'port' => $connection['port']];

        $options['user'] = $connection['username'];
        $options['password'] = $connection['password'];

        $lines = array_map(
            fn (string $key, mixed $value): string => "{$key}=\"".addcslashes((string) $value, '\\"').'"',
            array_keys($options),
            $options,
        );

        return "[client]\n".implode("\n", $lines)."\n";
    }
}
