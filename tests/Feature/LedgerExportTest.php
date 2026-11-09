<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->travelTo('2026-11-09 10:00:00');

    $this->owner = User::factory()->create();
    $this->portfolio = $this->owner->portfolios()->create(['name' => 'Cabinet Martin']);
    $this->bank = $this->portfolio->accounts()->create(['name' => 'Banque']);
    $this->former = $this->portfolio->accounts()->create(['name' => '+Ancien']);
    $consulting = $this->portfolio->analyticAxes()->create(['name' => 'Conseil']);
    $training = $this->portfolio->analyticAxes()->create(['name' => 'Formation']);

    $invoice = $this->bank->operations()->create(['label' => 'Facture ; Alpha', 'amount' => '-1250.50', 'delivery_at' => '2026-01-10']);
    $invoice->forceFill(['created_at' => '2026-01-11 09:00:00'])->save();
    $invoice->analyticLines()->create(['analytic_axis_id' => $training->id, 'percentage' => '62.50']);
    $invoice->analyticLines()->create(['analytic_axis_id' => $consulting->id, 'percentage' => '37.50']);

    $this->former->operations()->create(['label' => '=SUM(A1:A9)', 'amount' => '40.00', 'delivery_at' => '2025-07-01'])
        ->forceFill(['created_at' => '2025-07-02 12:00:00'])->save();

    $this->former->delete();

    $this->actingAs($this->owner);
});

test('the ledger export downloads every operation as a CSV file for French spreadsheets', function () {
    $response = $this->get(route('ledger.export', $this->portfolio));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertDownload('grand-livre-cabinet-martin-2026-11-09.csv');

    expect($response->streamedContent())->toBe(implode("\r\n", [
        "\u{FEFF}compte;\"horodatage écriture\";\"date effective\";libellé;montant;axes",
        "Banque;\"2026-01-11 09:00:00\";2026-01-10;\"Facture ; Alpha\";-1250,50;\"Conseil 37,5\u{A0}% | Formation 62,5\u{A0}%\"",
        "'+Ancien;\"2025-07-02 12:00:00\";2025-07-01;'=SUM(A1:A9);40,00;",
        '',
    ]));
});

test('the ledger export applies the filters and the sort of the page', function () {
    $filtered = $this->get(route('ledger.export', [$this->portfolio, 'flow' => 'payments']))->streamedContent();
    $sorted = $this->get(route('ledger.export', [$this->portfolio, 'sort' => 'effective', 'dir' => 'asc']))->streamedContent();

    expect($filtered)->toContain('=SUM(A1:A9)')->not->toContain('Alpha')
        ->and(mb_strpos($sorted, '=SUM(A1:A9)'))->toBeLessThan(mb_strpos($sorted, 'Alpha'));
});

test('the ledger export of a portfolio without operation has its header alone', function () {
    $empty = $this->owner->portfolios()->create(['name' => 'Vide']);

    expect($this->get(route('ledger.export', $empty))->streamedContent())
        ->toBe("\u{FEFF}compte;\"horodatage écriture\";\"date effective\";libellé;montant;axes\r\n");
});
