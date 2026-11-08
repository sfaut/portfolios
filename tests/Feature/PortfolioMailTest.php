<?php

use App\Mail\PortfolioCreatedMail;
use App\Mail\PortfolioDeletedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

test('creating and archiving a portfolio mail the administrator', function () {
    config(['app.admin_email' => 'admin@example.net']);
    Mail::fake();
    $portfolio = User::factory()->create()->portfolios()->create(['name' => 'Portfolio']);

    $portfolio->delete();

    Mail::assertQueued(PortfolioCreatedMail::class, fn ($mail) => $mail->hasTo('admin@example.net'));
    Mail::assertQueued(PortfolioDeletedMail::class, fn ($mail) => $mail->hasTo('admin@example.net'));
});

test('no mail is sent without an administrator address', function () {
    config(['app.admin_email' => '']);
    Mail::fake();
    $portfolio = User::factory()->create()->portfolios()->create(['name' => 'Portfolio']);

    $portfolio->delete();

    Mail::assertNothingOutgoing();
});
