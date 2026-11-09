<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Prévient le titulaire d'une adresse qu'une inscription a été tentée avec elle, alors qu'elle a déjà un
 * compte.
 *
 * L'inscription répond de la même façon que l'adresse ait un compte ou non : ce mail remplace, pour une
 * adresse déjà inscrite, le lien de vérification envoyé à une adresse nouvelle. Il part pendant la
 * requête, comme ce lien, pour que le temps de réponse ne distingue pas les deux cas.
 */
class RegistrationAttempted extends Notification
{
    /**
     * Canaux d'envoi.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Mail envoyé au titulaire de l'adresse.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tentative d\'inscription avec votre adresse')
            ->line('Une inscription à Portfolios vient d\'être demandée avec votre adresse e-mail, qui a déjà un compte.')
            ->action('Se connecter', route('login'))
            ->line('Si vous avez oublié votre mot de passe, la page de connexion permet de le réinitialiser.')
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet e-mail : votre compte n\'a pas changé.');
    }
}
