<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo('2026-11-09 10:00:00.123456');

    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->portfolio = $this->user->portfolios()->create(['name' => 'Famille']);
    $this->account = $this->portfolio->accounts()->create(['name' => 'Compte courant']);
    $this->operation = $this->account->operations()->create(['label' => 'Loyer', 'amount' => '-750.50', 'delivery_at' => '2026-11-05']);
    $this->axis = $this->portfolio->analyticAxes()->create(['name' => 'Logement']);
    $this->line = $this->operation->analyticLines()->create(['analytic_axis_id' => $this->axis->id, 'percentage' => '100.00']);
});

test('every model stores its timestamps to the microsecond', function (string $table, string $model) {
    $row = DB::table($table)->where('id', $this->{$model}->id)->first();

    expect($row->created_at)->toBe('2026-11-09 10:00:00.123456')
        ->and($row->updated_at)->toBe('2026-11-09 10:00:00.123456');
})->with([
    ['users', 'user'],
    ['portfolios', 'portfolio'],
    ['accounts', 'account'],
    ['operations', 'operation'],
    ['analytic_axes', 'axis'],
    ['analytic_lines', 'line'],
]);

test('every model reads its timestamps back to the microsecond, and an update moves them', function (string $model) {
    $record = $this->{$model}->fresh();
    expect($record->created_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-09 10:00:00.123456');

    $this->travelTo('2026-11-09 11:00:00.654321');
    $record->touch();

    expect($record->fresh()->updated_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-09 11:00:00.654321')
        ->and($record->fresh()->created_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-09 10:00:00.123456');
})->with(['user', 'portfolio', 'account', 'operation', 'axis', 'line']);

test('the archiving date of the archivable models keeps its microseconds', function (string $model) {
    $this->travelTo('2026-11-09 12:00:00.000789');
    $this->{$model}->delete();

    expect($this->{$model}->fresh()->deleted_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-09 12:00:00.000789');
})->with(['portfolio', 'account', 'axis']);

test('the operation report reads the timestamps of its operation to the microsecond', function () {
    $report = $this->account->operationsReport()->first();

    expect($report->created_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-09 10:00:00.123456')
        ->and($report->updated_at->format('Y-m-d H:i:s.u'))->toBe('2026-11-09 10:00:00.123456');
});
