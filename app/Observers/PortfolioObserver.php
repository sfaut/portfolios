<?php

namespace App\Observers;

use App\Mail\PortfolioCreatedMail;
use App\Mail\PortfolioDeletedMail;
use App\Models\Portfolio;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class PortfolioObserver
{
    /**
     * Informe l'administrateur de la création du portfolio.
     */
    public function created(Portfolio $portfolio): void
    {
        $this->mailAdmin(new PortfolioCreatedMail($portfolio));
    }

    public function updated(Portfolio $portfolio): void
    {
        //
    }

    /**
     * Informe l'administrateur de l'archivage du portfolio.
     */
    public function deleted(Portfolio $portfolio): void
    {
        $this->mailAdmin(new PortfolioDeletedMail($portfolio));
    }

    public function restored(Portfolio $portfolio): void
    {
        //
    }

    public function forceDeleted(Portfolio $portfolio): void
    {
        //
    }

    /**
     * Envoie le mail à l'administrateur (`app.admin.email`) avec deux minutes de délai, et ne fait rien
     * si aucune adresse n'est configurée.
     */
    private function mailAdmin(Mailable $mail): void
    {
        $admin_email = config('app.admin.email');

        if ($admin_email === '') {
            return;
        }

        Mail::to($admin_email)->later(now()->addMinutes(2), $mail);
    }
}
