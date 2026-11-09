<?php

use App\Models\User;
use App\Support\ServerStatus\ServerStatus;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->withSession(['auth.password_confirmed_at' => time()]);
    fakeMachine();
});

/**
 * Fixe les mesures de la machine lues par le relevé : par défaut une machine saine, à 2 processeurs,
 * 4 Gio de mémoire dont la moitié disponible, un disque à moitié libre. Une mesure à null est
 * indisponible.
 *
 * @param  array{uptime?: int|null, load?: list<float>|null, cpus?: int|null, memory?: array<string, int>|null, disk?: array{free: float, total: float}|null}  $measures
 */
function fakeMachine(array $measures = []): void
{
    $measures = [
        'uptime' => 3 * 86400 + 4 * 3600 + 5 * 60,
        'load' => [0.5, 0.4, 0.3],
        'cpus' => 2,
        'memory' => ['MemTotal' => 4 * 1024 ** 3, 'MemAvailable' => 2 * 1024 ** 3, 'SwapTotal' => 0, 'SwapFree' => 0],
        'disk' => ['free' => 50.0 * 1024 ** 3, 'total' => 100.0 * 1024 ** 3],
        ...$measures,
    ];

    app()->bind(ServerStatus::class, fn () => new class($measures) extends ServerStatus
    {
        public function __construct(
            private readonly array $measures,
        ) {
            parent::__construct();
        }

        protected function procUptime(): ?int
        {
            return $this->measures['uptime'];
        }

        protected function loadAverage(): ?array
        {
            return $this->measures['load'];
        }

        protected function cpuCount(): ?int
        {
            return $this->measures['cpus'];
        }

        protected function procMeminfo(): ?array
        {
            return $this->measures['memory'];
        }

        protected function diskSpace(): ?array
        {
            return $this->measures['disk'];
        }
    });
}

/**
 * Ajoute une tâche à la file database, disponible depuis le nombre de secondes donné.
 */
function queueJob(int $available_since): void
{
    DB::table('jobs')->insert([
        'queue' => 'default',
        'payload' => '{}',
        'attempts' => 0,
        'available_at' => now()->getTimestamp() - $available_since,
        'created_at' => now()->getTimestamp() - $available_since,
    ]);
}

/**
 * Ajoute une tâche en échec, au nom et à la file donnés.
 */
function failJob(string $name = 'App\\Mail\\PortfolioCreatedMail', string $queue = 'default', string $exception = 'RuntimeException: boom'): void
{
    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'database',
        'queue' => $queue,
        'payload' => json_encode(['displayName' => $name]),
        'exception' => $exception,
        'failed_at' => now()->subHour(),
    ]);
}

test('the administrator sees the six sections of the status', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertOk()
        ->assertSeeInOrder(['État du serveur', 'Relevé du'])
        ->assertSeeInOrder(['Application', 'PHP', 'Système', 'Base de données', 'File et mails', 'Données'])
        ->assertSee(PHP_VERSION)
        ->assertSee('id="alerts"', escape: false)
        ->assertSee('href="#database"', escape: false)
        ->assertSee('id="database"', escape: false);
});

test('a healthy server has nothing to report', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertSee('Rien à signaler')
        ->assertSeeInOrder(['Uptime', '3 j 4 h 5 min'])
        ->assertSeeInOrder(['Charge (1, 5, 15 min)', '0,50 ; 0,40 ; 0,30, 2 processeurs'])
        ->assertSeeInOrder(['Mémoire', "2,0\u{202F}Go disponibles sur 4,0\u{202F}Go"])
        ->assertSeeInOrder(['Disque de l\'application', "50,0\u{202F}Go libres sur 100,0\u{202F}Go (50\u{202F}%)"])
        ->assertSee('style="width: 50%"', escape: false)
        ->assertDontSee('(à surveiller)')
        ->assertDontSee('(anomalie)');
});

test('the measures missing on the machine are not available', function () {
    fakeMachine(['uptime' => null, 'load' => null, 'cpus' => null, 'memory' => null, 'disk' => null]);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    foreach (['Uptime', 'Charge (1, 5, 15 min)', 'Mémoire', 'Swap', 'Disque de l\'application'] as $label) {
        $response->assertSeeInOrder([$label, ServerStatus::UNAVAILABLE]);
    }
    $response->assertSee('Rien à signaler');
});

test('the disk is watched under 20 % free, and an anomaly under 10 %', function (float $free, string $title, string $alert) {
    fakeMachine(['disk' => ['free' => $free, 'total' => 100.0]]);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSee($title)->assertSee($alert);
})->with([
    '15 % free' => [15.0, 'Un point à surveiller', 'Disque : moins de 20 % libres.'],
    '5 % free' => [5.0, 'Une anomalie', 'Disque : moins de 10 % libres.'],
]);

test('the memory is watched under 10 % available', function () {
    fakeMachine(['memory' => ['MemTotal' => 1000 * 1024, 'MemAvailable' => 50 * 1024]]);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSee('Moins de 10 % de la mémoire est disponible.');
});

test('the load is watched beyond the number of processors', function () {
    fakeMachine(['load' => [1.0, 2.5, 1.0], 'cpus' => 2]);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSee('La charge sur 5 minutes dépasse le nombre de processeurs.');
});

test('the alerts list the anomalies first', function () {
    fakeMachine(['disk' => ['free' => 5.0, 'total' => 100.0], 'load' => [9.0, 9.0, 9.0]]);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSeeInOrder([
        'Une anomalie et un point à surveiller',
        'Disque : moins de 10 % libres.',
        'La charge sur 5 minutes dépasse le nombre de processeurs.',
    ]);
});

test('the database section reads the MySQL server', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertSee('MySQL '.DB::selectOne('SELECT VERSION() AS version')->version)
        ->assertSee(DB::connection()->getDatabaseName());
});

test('an empty queue has nothing to report', function () {
    config(['queue.default' => 'database']);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSeeInOrder(['Tâches en retard', 'Aucune', 'Tâches en échec', 'Aucune']);
    $response->assertDontSee('le worker de file semble arrêté');
});

test('a recent job is not late', function () {
    config(['queue.default' => 'database']);
    queueJob(available_since: 60);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSeeInOrder(['Tâches en attente', '1', 'Tâches en retard', 'Aucune']);
});

test('a job due for ten minutes reports a stopped worker', function () {
    config(['queue.default' => 'database']);
    queueJob(available_since: 600);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertSee('Une anomalie')
        ->assertSee('Une tâche attend depuis plus de 5 minutes : le worker de file semble arrêté.');
});

test('a failed job is a point to watch', function () {
    failJob();

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertSee('Une tâche de file a échoué (php artisan queue:failed).')
        ->assertSee('App\\Mail\\PortfolioCreatedMail, file default');
});

test('pending migrations are an anomaly', function () {
    DB::table('migrations')->where('id', DB::table('migrations')->max('id'))->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSee('Une migration n\'a pas été lancée.');
});

test('an empty sender address is an anomaly', function () {
    config(['mail.from.address' => '']);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSee('Aucune adresse d\'expédition (MAIL_FROM_ADDRESS) : les mails ne partent pas.');
});

test('the data counts include the archived rows', function () {
    $portfolio = $this->admin->portfolios()->create(['name' => 'Portfolio']);
    $portfolio->accounts()->create(['name' => 'Actif']);
    $portfolio->accounts()->create(['name' => 'Archivé'])->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertSeeInOrder(['Comptes', '2 (dont 1 archivé)']);
});

test('the debug mode in production is an anomaly', function () {
    config(['app.debug' => true]);
    app()->detectEnvironment(fn () => 'production');

    $alerts = (new ServerStatus(now()->toImmutable()))->alerts();

    expect(collect($alerts)->pluck('item.alert'))
        ->toContain('Le débogage est activé en production : les erreurs montrent le code et la configuration.');
});

test('the user menu offers the server status to the administrator only', function () {
    $this->actingAs($this->admin)->get(route('portfolio.index'))
        ->assertSee(route('admin.server'));

    $this->actingAs(User::factory()->create())->get(route('portfolio.index'))
        ->assertDontSee(route('admin.server'))
        ->assertDontSee('État du serveur');
});

test('a guest is sent to the login page', function () {
    auth()->logout();

    $this->get(route('admin.server'))->assertRedirect(route('login'));
});

test('the administrator confirms the password first', function () {
    $this->flushSession();

    $this->actingAs($this->admin)->get(route('admin.server'))
        ->assertRedirect(route('password.confirm'));
});

test('the page has no portfolio navigation', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response->assertDontSee('Pages du portfolio');
});
