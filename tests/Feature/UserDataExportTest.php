<?php

use App\Models\User;
use App\Support\UserDataExport;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

/**
 * Lignes d'un fichier CSV de l'export, BOM retiré, champs séparés.
 *
 * @return list<list<string>>
 */
function csvRows(string $content): array
{
    $lines = explode("\r\n", rtrim(substr($content, 3), "\r\n"));

    return array_map(fn (string $line): array => str_getcsv($line, separator: ';', escape: ''), $lines);
}

test('the profile asks to confirm the password before exporting', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('profile.export'));

    $response->assertRedirect(route('password.confirm'));
});

test('the profile offers to download the data', function () {
    $response = $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.edit'));

    $response->assertSee('href="'.route('profile.export').'"', escape: false);
});

test('the export downloads a dated zip archive of csv files', function () {
    $this->travelTo('2026-11-09 10:00:00');

    $response = $this->actingAs(User::factory()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.export'));

    $response->assertOk()->assertDownload('portfolios-2026-11-09.zip');
    $path = $response->baseResponse->getFile()->getPathname();
    $zip = new ZipArchive;
    $zip->open($path);
    $names = array_map(fn (int $index): string => $zip->getNameIndex($index), range(0, $zip->numFiles - 1));
    $zip->close();
    unlink($path);
    expect($names)->toEqualCanonicalizing([
        'LISEZMOI.txt',
        'utilisateur.csv',
        'portfolios.csv',
        'comptes.csv',
        'operations.csv',
        'axes-analytiques.csv',
        'repartitions-analytiques.csv',
    ]);
});

test('the export holds the data of the user, archived included, and nothing of another user', function () {
    $user = User::factory()->create();
    $portfolio = $user->portfolios()->create(['name' => 'Famille']);
    $account = $portfolio->accounts()->create(['name' => 'Compte courant']);
    $operation = $account->operations()->create(['label' => 'Loyer', 'amount' => '-750.50', 'delivery_at' => '2026-11-05']);
    $axis = $portfolio->analyticAxes()->create(['name' => 'Logement']);
    $operation->analyticLines()->create(['analytic_axis_id' => $axis->id, 'percentage' => '100.00']);
    $archived_portfolio = $user->portfolios()->create(['name' => 'Ancien']);
    $archived_portfolio->accounts()->create(['name' => 'Livret clos'])->delete();
    $archived_portfolio->analyticAxes()->create(['name' => 'Vacances'])->delete();
    $archived_portfolio->delete();
    $stranger_portfolio = User::factory()->create()->portfolios()->create(['name' => 'Voisin']);
    $stranger_portfolio->accounts()->create(['name' => 'Compte du voisin'])
        ->operations()->create(['label' => 'Achat du voisin', 'amount' => '10.00', 'delivery_at' => '2026-11-05']);
    $stranger_portfolio->analyticAxes()->create(['name' => 'Axe du voisin']);

    $files = (new UserDataExport($user))->csvFiles();

    expect(array_column(csvRows($files['portfolios.csv']), 1))->toBe(['nom', 'Famille', 'Ancien'])
        ->and(array_column(csvRows($files['comptes.csv']), 3))->toBe(['nom', 'Compte courant', 'Livret clos'])
        ->and(csvRows($files['operations.csv'])[1])->toBe([
            (string) $operation->id, (string) $account->id, 'Compte courant', '2026-11-05', 'Loyer', '-750,50', $operation->created_at->format('Y-m-d H:i:s'),
        ])
        ->and(count(csvRows($files['operations.csv'])))->toBe(2)
        ->and(array_column(csvRows($files['axes-analytiques.csv']), 3))->toBe(['nom', 'Logement', 'Vacances'])
        ->and(csvRows($files['repartitions-analytiques.csv'])[1])->toBe([(string) $operation->id, (string) $axis->id, 'Logement', '100,00'])
        ->and(csvRows($files['utilisateur.csv'])[1][2])->toBe($user->email);
});

test('the export marks the archived records with their archiving date', function () {
    $this->travelTo('2026-11-09 10:00:00');
    $user = User::factory()->create();
    $user->portfolios()->create(['name' => 'Ancien'])->delete();

    $rows = csvRows((new UserDataExport($user))->csvFiles()['portfolios.csv']);

    expect($rows[0][4])->toBe('archivé le')
        ->and($rows[1][4])->toBe('2026-11-09 10:00:00');
});

test('the export writes csv files for a French spreadsheet', function () {
    $user = User::factory()->create();

    $content = (new UserDataExport($user))->csvFiles()['portfolios.csv'];

    expect($content)->toStartWith("\u{FEFF}id;nom;description;\"créé le\";\"archivé le\"\r\n");
});

test('the export neutralizes texts that a spreadsheet would run as a formula', function (string $label) {
    $user = User::factory()->create();
    $user->portfolios()->create(['name' => 'Famille'])
        ->accounts()->create(['name' => 'Compte'])
        ->operations()->create(['label' => $label, 'amount' => '-1.00', 'delivery_at' => '2026-11-05']);

    $row = csvRows((new UserDataExport($user))->csvFiles()['operations.csv'])[1];

    expect($row[4])->toBe("'{$label}")
        ->and($row[5])->toBe('-1,00');
})->with([
    'equals' => '=HYPERLINK("http://example.com")',
    'plus' => '+33 1 23 45 67 89',
    'minus' => '-2+3',
    'at' => '@SUM(A1:A2)',
]);
