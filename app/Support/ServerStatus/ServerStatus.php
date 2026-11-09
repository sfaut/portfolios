<?php

namespace App\Support\ServerStatus;

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Throwable;

/**
 * Relevé de l'état du serveur, pour l'administrateur : application, PHP, système, base de données, file
 * et mails, volumes de données, chacun avec ses alertes.
 *
 * Toute mesure indisponible (Windows, /proc illisible, privilège MySQL manquant, autre pilote de base)
 * vaut « Non disponible », sans erreur. Le relevé n'écrit aucun secret : ni mot de passe, ni clef, ni
 * valeur du .env hors des noms de pilotes et des adresses d'expédition et d'administration. Les
 * messages d'exception des tâches en échec n'y figurent pas.
 *
 * Seuils : disque libre sous 10 % (anomalie) ou 20 % (à surveiller), mémoire disponible sous 10 %,
 * charge sur 5 minutes au-delà du nombre de processeurs, connexions MySQL au-delà de 80 % du maximum,
 * tâche de file due depuis plus de LATE_JOB_SECONDS (worker sans doute arrêté), journaux au-delà de
 * LOGS_WARNING_BYTES.
 *
 * Les mesures de la machine passent par des méthodes protégées (procUptime(), loadAverage()…), qu'un
 * test remplace pour fixer leurs valeurs.
 */
class ServerStatus
{
    /**
     * Valeur d'une mesure indisponible.
     */
    public const string UNAVAILABLE = 'Non disponible';

    /**
     * Retard au-delà duquel une tâche de file signale un worker arrêté, en secondes.
     */
    public const int LATE_JOB_SECONDS = 300;

    /**
     * Taille des journaux au-delà de laquelle elle est à surveiller, en octets.
     */
    public const int LOGS_WARNING_BYTES = 100 * 1024 * 1024;

    /**
     * Extensions PHP dont l'application a besoin.
     *
     * @var list<string>
     */
    public const array REQUIRED_EXTENSIONS = ['intl', 'mbstring', 'pdo_mysql', 'zip'];

    /**
     * Tables de données de l'application, avec leur libellé ; vrai si la table archive ses lignes.
     *
     * @var array<string, array{0: string, 1: bool}>
     */
    public const array DATA_TABLES = [
        'users' => ['Utilisateurs', false],
        'portfolios' => ['Portfolios', true],
        'accounts' => ['Comptes', true],
        'operations' => ['Opérations', false],
        'analytic_axes' => ['Axes analytiques', true],
        'analytic_lines' => ['Lignes analytiques', false],
    ];

    /**
     * Sections du relevé, calculées une fois.
     *
     * @var list<StatusSection>|null
     */
    private ?array $sections = null;

    /**
     * @param  CarbonImmutable  $takenAt  Date et heure du relevé, l'instant présent par défaut.
     */
    public function __construct(
        public readonly CarbonImmutable $takenAt = new CarbonImmutable,
    ) {
        //
    }

    /**
     * Sections du relevé, dans l'ordre de la page.
     *
     * @return list<StatusSection>
     */
    public function sections(): array
    {
        return $this->sections ??= [
            new StatusSection('application', 'Application', $this->application()),
            new StatusSection('php', 'PHP', $this->php()),
            new StatusSection('system', 'Système', $this->system()),
            new StatusSection('database', 'Base de données', $this->database()),
            new StatusSection('queue', 'File et mails', $this->queue()),
            new StatusSection('data', 'Données', $this->data()),
        ];
    }

    /**
     * Alertes de toutes les sections, les anomalies avant les points à surveiller.
     *
     * @return list<array{section: StatusSection, item: StatusItem}>
     */
    public function alerts(): array
    {
        $alerts = [];

        foreach ($this->sections() as $section) {
            foreach ($section->alerts() as $item) {
                $alerts[] = ['section' => $section, 'item' => $item];
            }
        }

        usort($alerts, fn (array $a, array $b): int => $a['item']->level === $b['item']->level ? 0 : ($a['item']->level === StatusItem::DANGER ? -1 : 1));

        return $alerts;
    }

    /**
     * Niveau du relevé : le pire de ses sections.
     */
    public function level(): string
    {
        return StatusItem::worst(array_map(fn (StatusSection $section): string => $section->level(), $this->sections()));
    }

    /**
     * Application : version déployée, environnement, caches, maintenance, migrations.
     *
     * @return list<StatusItem>
     */
    private function application(): array
    {
        $production = app()->isProduction();
        $release = basename(base_path());
        $is_release = preg_match('/^v\d+\.\d+\.\d+$/', $release) === 1;
        $deployed_at = $is_release ? @filemtime(base_path()) : false;
        $debug = (bool) config('app.debug');
        $config_cached = app()->configurationIsCached();
        $pending = $this->pendingMigrations();

        return [
            new StatusItem('Version', $is_release
                ? $release.($deployed_at === false ? '' : ', déployée le '.$this->dateTime(CarbonImmutable::createFromTimestamp($deployed_at)))
                : 'Hors release (développement)'),
            new StatusItem('Environnement', (string) app()->environment()),
            $debug && $production
                ? new StatusItem('Débogage', 'Activé', StatusItem::DANGER, 'Le débogage est activé en production : les erreurs montrent le code et la configuration.')
                : new StatusItem('Débogage', $debug ? 'Activé' : 'Désactivé'),
            new StatusItem('Adresse', (string) config('app.url')),
            new StatusItem('Fuseau et langue', config('app.timezone').', '.config('app.locale')),
            new StatusItem('Laravel', app()->version()),
            ! $config_cached && $production
                ? new StatusItem('Cache de configuration', 'Absent', StatusItem::WARNING, 'La configuration n\'est pas en cache en production (php artisan optimize).')
                : new StatusItem('Cache de configuration', $config_cached ? 'En place' : 'Absent'),
            new StatusItem('Caches de routes et d\'événements', ($this->yesNo(app()->routesAreCached())).', '.Str::lower($this->yesNo(app()->eventsAreCached()))),
            app()->isDownForMaintenance()
                ? new StatusItem('Maintenance', 'Activée', StatusItem::WARNING, 'L\'application est en maintenance.')
                : new StatusItem('Maintenance', 'Désactivée'),
            match (true) {
                $pending === null => new StatusItem('Migrations en attente', self::UNAVAILABLE),
                $pending > 0 => new StatusItem('Migrations en attente', (string) $pending, StatusItem::DANGER, trans_choice('{1} Une migration n\'a pas été lancée.|[2,*] :count migrations n\'ont pas été lancées.', $pending)),
                default => new StatusItem('Migrations en attente', 'Aucune'),
            },
        ];
    }

    /**
     * PHP : version, limites, OPcache, extensions requises, mémoire de la requête.
     *
     * @return list<StatusItem>
     */
    private function php(): array
    {
        $missing = array_values(array_filter(self::REQUIRED_EXTENSIONS, fn (string $extension): bool => ! extension_loaded($extension)));

        return [
            new StatusItem('Version', PHP_VERSION),
            new StatusItem('Interface (SAPI)', PHP_SAPI),
            new StatusItem('Limite de mémoire', (string) ini_get('memory_limit')),
            new StatusItem('Durée maximale d\'exécution', ini_get('max_execution_time') === '0' ? 'Illimitée' : ini_get('max_execution_time').' s'),
            new StatusItem('Envoi de fichiers', 'Fichier '.ini_get('upload_max_filesize').', requête '.ini_get('post_max_size')),
            ...$this->opcache(),
            $missing === []
                ? new StatusItem('Extensions requises', implode(', ', self::REQUIRED_EXTENSIONS))
                : new StatusItem('Extensions requises', 'Manquantes : '.implode(', ', $missing), StatusItem::DANGER, 'Extensions PHP manquantes : '.implode(', ', $missing).'.'),
            new StatusItem('Mémoire de pointe de la requête', $this->bytes(memory_get_peak_usage(true))),
        ];
    }

    /**
     * Lignes d'OPcache : état, mémoire utilisée et taux de succès.
     *
     * @return list<StatusItem>
     */
    private function opcache(): array
    {
        $status = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;

        if (! is_array($status) || ! ($status['opcache_enabled'] ?? false)) {
            return [app()->isProduction()
                ? new StatusItem('OPcache', 'Inactif', StatusItem::WARNING, 'OPcache est inactif en production : chaque requête recompile le code.')
                : new StatusItem('OPcache', 'Inactif')];
        }

        $memory = $status['memory_usage'] ?? [];
        $used = (float) ($memory['used_memory'] ?? 0);
        $total = $used + (float) ($memory['free_memory'] ?? 0) + (float) ($memory['wasted_memory'] ?? 0);
        $hit_rate = (float) ($status['opcache_statistics']['opcache_hit_rate'] ?? 0);

        return [
            new StatusItem('OPcache', 'Actif, '.$this->percent($hit_rate / 100).' de succès'),
            new StatusItem('Mémoire d\'OPcache', $total > 0 ? $this->bytes($used).' sur '.$this->bytes($total) : self::UNAVAILABLE, meter: $total > 0 ? $used / $total : null),
        ];
    }

    /**
     * Système : machine, uptime, charge, mémoire, disque, heure.
     *
     * @return list<StatusItem>
     */
    private function system(): array
    {
        $uptime = $this->procUptime();
        $cpus = $this->cpuCount();
        $memory = $this->procMeminfo();

        return [
            new StatusItem('Système', php_uname('s').' '.php_uname('r').' ('.php_uname('m').')'),
            new StatusItem('Nom d\'hôte', (string) gethostname()),
            new StatusItem('Uptime', $uptime === null ? self::UNAVAILABLE : $this->duration($uptime)),
            $this->loadItem($this->loadAverage(), $cpus),
            ...$this->memoryItems($memory),
            $this->diskItem($this->diskSpace()),
            new StatusItem('Heure du serveur', $this->dateTime($this->takenAt).' ('.$this->takenAt->getTimezone()->getName().')'),
        ];
    }

    /**
     * Ligne de la charge : moyennes sur 1, 5 et 15 minutes, et nombre de processeurs.
     *
     * @param  list<float>|null  $load  Moyennes de charge, null si indisponibles.
     * @param  int|null  $cpus  Nombre de processeurs, null s'il est inconnu.
     */
    private function loadItem(?array $load, ?int $cpus): StatusItem
    {
        if ($load === null) {
            return new StatusItem('Charge (1, 5, 15 min)', $cpus === null ? self::UNAVAILABLE : self::UNAVAILABLE.', '.trans_choice('{1} 1 processeur|[2,*] :count processeurs', $cpus));
        }

        $value = implode(' ; ', array_map(fn (float $average): string => Number::format($average, precision: 2, locale: 'fr'), $load))
            .($cpus === null ? '' : ', '.trans_choice('{1} 1 processeur|[2,*] :count processeurs', $cpus));

        return $cpus !== null && $load[1] > $cpus
            ? new StatusItem('Charge (1, 5, 15 min)', $value, StatusItem::WARNING, 'La charge sur 5 minutes dépasse le nombre de processeurs.')
            : new StatusItem('Charge (1, 5, 15 min)', $value);
    }

    /**
     * Lignes de la mémoire vive et du swap.
     *
     * @param  array<string, int>|null  $memory  Valeurs de /proc/meminfo, en octets ; null si indisponibles.
     * @return list<StatusItem>
     */
    private function memoryItems(?array $memory): array
    {
        if ($memory === null || ($memory['MemTotal'] ?? 0) === 0) {
            return [new StatusItem('Mémoire', self::UNAVAILABLE), new StatusItem('Swap', self::UNAVAILABLE)];
        }

        $total = $memory['MemTotal'];
        $available = $memory['MemAvailable'] ?? $memory['MemFree'] ?? 0;
        $value = $this->bytes($available).' disponibles sur '.$this->bytes($total);
        $ratio = 1 - $available / $total;

        $items = [$available / $total < 0.1
            ? new StatusItem('Mémoire', $value, StatusItem::WARNING, 'Moins de 10 % de la mémoire est disponible.', $ratio)
            : new StatusItem('Mémoire', $value, meter: $ratio)];

        $swap_total = $memory['SwapTotal'] ?? 0;
        $items[] = $swap_total === 0
            ? new StatusItem('Swap', 'Aucun')
            : new StatusItem('Swap', $this->bytes($swap_total - ($memory['SwapFree'] ?? 0)).' utilisés sur '.$this->bytes($swap_total), meter: 1 - ($memory['SwapFree'] ?? 0) / $swap_total);

        return $items;
    }

    /**
     * Ligne du disque de l'application.
     *
     * @param  array{free: float, total: float}|null  $space  Espaces libre et total, en octets ; null s'ils sont indisponibles.
     */
    private function diskItem(?array $space): StatusItem
    {
        if ($space === null || $space['total'] <= 0) {
            return new StatusItem('Disque de l\'application', self::UNAVAILABLE);
        }

        ['free' => $free, 'total' => $total] = $space;
        $free_ratio = $free / $total;
        $value = $this->bytes($free).' libres sur '.$this->bytes($total).' ('.$this->percent($free_ratio).')';

        return match (true) {
            $free_ratio < 0.1 => new StatusItem('Disque de l\'application', $value, StatusItem::DANGER, 'Disque : moins de 10 % libres.', 1 - $free_ratio),
            $free_ratio < 0.2 => new StatusItem('Disque de l\'application', $value, StatusItem::WARNING, 'Disque : moins de 20 % libres.', 1 - $free_ratio),
            default => new StatusItem('Disque de l\'application', $value, meter: 1 - $free_ratio),
        };
    }

    /**
     * Base de données : serveur, uptime, base, connexions, taille.
     *
     * @return list<StatusItem>
     */
    private function database(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $items = [
            new StatusItem('Pilote', $driver),
            new StatusItem('Base', $connection->getDatabaseName()),
        ];

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return [...$items, new StatusItem('Serveur', self::UNAVAILABLE)];
        }

        $version = $this->query(fn () => $connection->selectOne('SELECT VERSION() AS version')?->version);
        $status = $this->query(fn () => $this->keyValues($connection->select("SHOW GLOBAL STATUS WHERE Variable_name IN ('Uptime', 'Threads_connected', 'Max_used_connections')"))) ?? [];
        $variables = $this->query(fn () => $this->keyValues($connection->select("SHOW VARIABLES WHERE Variable_name IN ('max_connections', 'character_set_database', 'collation_database')"))) ?? [];
        $size = $this->query(fn () => $connection->selectOne('SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = DATABASE()')?->size);

        $max = (int) ($variables['max_connections'] ?? 0);
        $connected = isset($status['Threads_connected']) ? (int) $status['Threads_connected'] : null;
        $max_used = isset($status['Max_used_connections']) ? (int) $status['Max_used_connections'] : null;

        $connections = match (true) {
            $connected === null || $max === 0 => new StatusItem('Connexions', self::UNAVAILABLE),
            $max_used !== null && $max_used / $max > 0.8 => new StatusItem('Connexions', "{$connected} ouvertes, au plus {$max_used} sur {$max}", StatusItem::WARNING, 'Les connexions MySQL ont dépassé 80 % du maximum.', $max_used / $max),
            default => new StatusItem('Connexions', "{$connected} ouvertes, au plus ".($max_used ?? $connected)." sur {$max}", meter: ($max_used ?? $connected) / $max),
        };

        return [
            ...$items,
            new StatusItem('Serveur', $version === null ? self::UNAVAILABLE : 'MySQL '.$version),
            new StatusItem('Uptime de MySQL', isset($status['Uptime']) ? $this->duration((int) $status['Uptime']) : self::UNAVAILABLE),
            new StatusItem('Jeu de caractères', isset($variables['character_set_database'])
                ? $variables['character_set_database'].', '.($variables['collation_database'] ?? '')
                : self::UNAVAILABLE),
            $connections,
            new StatusItem('Taille de la base', $size === null ? self::UNAVAILABLE : $this->bytes((float) $size)),
        ];
    }

    /**
     * File et mails : tâches en attente, en retard, en échec ; mailer et adresses.
     *
     * @return list<StatusItem>
     */
    private function queue(): array
    {
        $connection = (string) config('queue.default');
        $items = [new StatusItem('Connexion de la file', $connection)];

        if (config("queue.connections.{$connection}.driver") === 'database') {
            $jobs = DB::table((string) config("queue.connections.{$connection}.table", 'jobs'));
            $late_before = $this->takenAt->getTimestamp() - self::LATE_JOB_SECONDS;
            $pending = $this->query(fn () => (clone $jobs)->count());
            $late = $this->query(fn () => (clone $jobs)->where('available_at', '<=', $late_before)->count());
            $oldest = $this->query(fn () => (clone $jobs)->where('available_at', '<=', $late_before)->min('available_at'));

            $items[] = new StatusItem('Tâches en attente', $pending === null ? self::UNAVAILABLE : (string) $pending);
            $items[] = match (true) {
                $late === null => new StatusItem('Tâches en retard', self::UNAVAILABLE),
                $late > 0 => new StatusItem(
                    'Tâches en retard',
                    $late.', la plus ancienne due le '.$this->dateTime(CarbonImmutable::createFromTimestamp((int) $oldest)),
                    StatusItem::DANGER,
                    trans_choice('{1} Une tâche attend depuis plus de 5 minutes : le worker de file semble arrêté.|[2,*] :count tâches attendent depuis plus de 5 minutes : le worker de file semble arrêté.', $late),
                ),
                default => new StatusItem('Tâches en retard', 'Aucune'),
            };
        } else {
            $items[] = new StatusItem('Tâches en attente', self::UNAVAILABLE);
        }

        $items[] = $this->failedJobsItem();

        $from = (string) config('mail.from.address');

        return [
            ...$items,
            new StatusItem('Mailer', (string) config('mail.default')),
            $from === ''
                ? new StatusItem('Expéditeur', 'Absent', StatusItem::DANGER, 'Aucune adresse d\'expédition (MAIL_FROM_ADDRESS) : les mails ne partent pas.')
                : new StatusItem('Expéditeur', $from),
            new StatusItem('Destinataire administrateur', (string) config('app.admin.email') ?: 'Absent'),
        ];
    }

    /**
     * Ligne des tâches en échec : nombre, puis date, tâche et file du dernier échec, sans son message.
     */
    private function failedJobsItem(): StatusItem
    {
        $table = (string) config('queue.failed.table', 'failed_jobs');
        $count = $this->query(fn () => DB::table($table)->count());

        if ($count === null) {
            return new StatusItem('Tâches en échec', self::UNAVAILABLE);
        }

        if ($count === 0) {
            return new StatusItem('Tâches en échec', 'Aucune');
        }

        $last = $this->query(fn () => DB::table($table)->latest('failed_at')->first(['queue', 'payload', 'failed_at']));
        $name = $last === null ? '' : (string) (json_decode((string) $last->payload, true)['displayName'] ?? '');
        $detail = $last === null ? '' : ', la dernière le '.$this->dateTime(CarbonImmutable::parse($last->failed_at))
            .($name === '' ? '' : " ({$name}, file {$last->queue})");

        return new StatusItem(
            'Tâches en échec',
            $count.$detail,
            StatusItem::WARNING,
            trans_choice('{1} Une tâche de file a échoué (php artisan queue:failed).|[2,*] :count tâches de file ont échoué (php artisan queue:failed).', $count),
        );
    }

    /**
     * Données : lignes de chaque table de l'application, archivées comprises, tailles des tables et des
     * journaux.
     *
     * @return list<StatusItem>
     */
    private function data(): array
    {
        $items = [];

        foreach (self::DATA_TABLES as $table => [$label, $archives]) {
            $count = $this->query(fn () => DB::table($table)->count());
            $archived = $archives ? $this->query(fn () => DB::table($table)->whereNotNull('deleted_at')->count()) : null;

            $items[] = new StatusItem($label, match (true) {
                $count === null => self::UNAVAILABLE,
                (int) $archived > 0 => Number::format($count, locale: 'fr').' (dont '.Number::format($archived, locale: 'fr').' '.($archived > 1 ? 'archivés' : 'archivé').')',
                default => Number::format($count, locale: 'fr'),
            });
        }

        $sizes = DB::connection()->getDriverName() === 'sqlite' ? null : $this->query(fn () => DB::select(
            'SELECT table_name AS name, data_length + index_length AS size FROM information_schema.TABLES WHERE table_schema = DATABASE() ORDER BY size DESC, name LIMIT 5',
        ));
        $items[] = new StatusItem('Plus grosses tables', $sizes === null || $sizes === []
            ? self::UNAVAILABLE
            : implode(', ', array_map(fn (object $row): string => $row->name.' '.$this->bytes((float) $row->size), $sizes)));

        $logs = array_sum(array_map(fn (string $file): int => (int) @filesize($file), glob(storage_path('logs/*.log')) ?: []));
        $items[] = $logs > self::LOGS_WARNING_BYTES
            ? new StatusItem('Journaux', $this->bytes($logs), StatusItem::WARNING, 'Les journaux dépassent 100 Mo.')
            : new StatusItem('Journaux', $this->bytes($logs));

        return $items;
    }

    /**
     * Nombre de migrations du projet pas encore lancées, null si le dépôt des migrations est illisible.
     */
    private function pendingMigrations(): ?int
    {
        return $this->query(function (): int {
            /** @var Migrator $migrator */
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                return 0;
            }

            $files = $migrator->getMigrationFiles([database_path('migrations'), ...$migrator->paths()]);

            return count(array_diff(array_keys($files), $migrator->getRepository()->getRan()));
        });
    }

    /**
     * Uptime de la machine en secondes, lu dans /proc/uptime ; null ailleurs que sous Linux.
     */
    protected function procUptime(): ?int
    {
        $content = is_readable('/proc/uptime') ? @file_get_contents('/proc/uptime') : false;

        return $content === false ? null : (int) (float) strtok($content, ' ');
    }

    /**
     * Mémoire de la machine, lue dans /proc/meminfo, en octets par clef (MemTotal, MemAvailable,
     * SwapTotal…) ; null ailleurs que sous Linux.
     *
     * @return array<string, int>|null
     */
    protected function procMeminfo(): ?array
    {
        $content = is_readable('/proc/meminfo') ? @file_get_contents('/proc/meminfo') : false;

        if ($content === false) {
            return null;
        }

        preg_match_all('/^(\w+):\s+(\d+)\s*kB$/m', $content, $matches, PREG_SET_ORDER);

        return collect($matches)->mapWithKeys(fn (array $match): array => [$match[1] => (int) $match[2] * 1024])->all();
    }

    /**
     * Charge moyenne de la machine sur 1, 5 et 15 minutes ; null si elle est indisponible (Windows).
     *
     * @return list<float>|null
     */
    protected function loadAverage(): ?array
    {
        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;

        return $load === false ? null : array_values($load);
    }

    /**
     * Espaces libre et total du disque de l'application, en octets ; null s'ils sont indisponibles.
     *
     * @return array{free: float, total: float}|null
     */
    protected function diskSpace(): ?array
    {
        $free = @disk_free_space(base_path());
        $total = @disk_total_space(base_path());

        return $free === false || $total === false ? null : ['free' => $free, 'total' => $total];
    }

    /**
     * Nombre de processeurs, lu dans /proc/cpuinfo ou, sous Windows, dans NUMBER_OF_PROCESSORS ;
     * null s'il est inconnu.
     */
    protected function cpuCount(): ?int
    {
        $content = is_readable('/proc/cpuinfo') ? @file_get_contents('/proc/cpuinfo') : false;

        if ($content !== false) {
            return max(1, preg_match_all('/^processor\s*:/m', $content));
        }

        $count = (int) getenv('NUMBER_OF_PROCESSORS');

        return $count > 0 ? $count : null;
    }

    /**
     * Résultat d'une lecture, ou null si elle échoue (privilège, table absente…).
     *
     * @template T
     *
     * @param  callable(): T  $read  Lecture.
     * @return T|null
     */
    private function query(callable $read): mixed
    {
        try {
            return $read();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Lignes Variable_name / Value de MySQL, en tableau associatif.
     *
     * @param  list<object>  $rows  Lignes de SHOW STATUS ou SHOW VARIABLES.
     * @return array<string, string>
     */
    private function keyValues(array $rows): array
    {
        return collect($rows)->mapWithKeys(fn (object $row): array => [$row->Variable_name => (string) $row->Value])->all();
    }

    /**
     * Durée en français, à la minute : « 12 j 4 h 31 min », « moins d'une minute ».
     *
     * @param  int  $seconds  Durée en secondes.
     */
    private function duration(int $seconds): string
    {
        $parts = array_filter([
            'j' => intdiv($seconds, 86400),
            'h' => intdiv($seconds % 86400, 3600),
            'min' => intdiv($seconds % 3600, 60),
        ]);

        return $parts === []
            ? 'moins d\'une minute'
            : implode(' ', array_map(fn (string $unit, int $value): string => "{$value} {$unit}", array_keys($parts), $parts));
    }

    /**
     * Taille en français, en unités binaires : « 512 o », « 1,5 Go ».
     *
     * @param  int|float  $bytes  Taille en octets.
     */
    private function bytes(int|float $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $power = $bytes > 0 ? min(count($units) - 1, (int) floor(log($bytes, 1024))) : 0;

        return Number::format($bytes / 1024 ** $power, precision: $power === 0 ? 0 : 1, locale: 'fr')."\u{202F}".$units[$power];
    }

    /**
     * Pourcentage entier en français : « 8 % ».
     *
     * @param  float  $ratio  Taux de 0 à 1.
     */
    private function percent(float $ratio): string
    {
        return Number::format($ratio * 100, precision: 0, locale: 'fr')."\u{202F}%";
    }

    /**
     * Date et heure en français : « 9 novembre 2026 à 14:32 ».
     */
    private function dateTime(CarbonImmutable $moment): string
    {
        return $moment->setTimezone((string) config('app.timezone'))->translatedFormat('j F Y à H:i');
    }

    /**
     * Présence d'après un booléen : « En place », « Absents ».
     */
    private function yesNo(bool $value): string
    {
        return $value ? 'En place' : 'Absents';
    }
}
