<?php

use App\Console\Commands\DumpDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;

test('db:dump runs mysqldump with the access in a temporary options file', function () {
    config([
        'database.connections.mysql.host' => 'db.example.net',
        'database.connections.mysql.port' => '3307',
        'database.connections.mysql.database' => 'portfolios',
        'database.connections.mysql.username' => 'portfolios',
        'database.connections.mysql.password' => 'p@ss"wo\rd $x',
    ]);
    $options_file = null;
    $options = null;
    Process::fake(function (PendingProcess $process) use (&$options_file, &$options) {
        $options_file = str($process->command[1])->after('--defaults-extra-file=')->toString();
        $options = file_get_contents($options_file);

        return Process::result();
    });

    $this->artisan('db:dump')->assertSuccessful();

    Process::assertRan(fn (PendingProcess $process) => $process->command === [
        'mysqldump',
        "--defaults-extra-file={$options_file}",
        ...DumpDatabase::MYSQLDUMP_OPTIONS,
        'portfolios',
    ]);
    expect($options)->toBe(implode("\n", [
        '[client]',
        'host="db.example.net"',
        'port="3307"',
        'user="portfolios"',
        'password="p@ss\"wo\\\\rd $x"',
    ])."\n")
        ->and($options_file)->not->toBeFile();
});

test('db:dump fails when mysqldump fails', function () {
    Process::fake(['*' => Process::result(exitCode: 2)]);

    $this->artisan('db:dump')->assertFailed();
});

test('db:dump refuses a connection other than MySQL', function () {
    config(['database.connections.sqlite' => ['driver' => 'sqlite', 'database' => ':memory:']]);
    Process::fake();

    $this->artisan('db:dump --connection=sqlite')->assertFailed();

    Process::assertNothingRan();
});
