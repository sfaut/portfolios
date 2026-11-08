<?php

namespace App\Http\Controllers;

use App\Mail\PortfolioCreatedMail;
use Illuminate\Support\Facades\Mail;

class MailingController extends Controller
{
    public function sendPortfolioCreatedMail()
    {
        Mail::to(config('app.admin_email'))->send(new PortfolioCreatedMail);
    }
}
