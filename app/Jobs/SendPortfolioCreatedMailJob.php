<?php

namespace App\Jobs;

use App\Mail\PortfolioCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPortfolioCreatedMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        Mail::to('sebastien.faut@gmail.com')->send(new PortfolioCreatedMail);
    }
}
