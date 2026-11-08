<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Messages de réinitialisation du mot de passe
    |--------------------------------------------------------------------------
    |
    | Messages associés aux réponses du gestionnaire de mots de passe (envoi du
    | lien, réinitialisation, lien invalide…).
    |
    | Aucun message ne dit si un compte existe : « sent » répond à toute demande
    | de lien (PasswordResetLinkController), et « user » (adresse inconnue)
    | reprend le texte de « token » (lien invalide).
    |
    */

    'reset' => 'Votre mot de passe a été réinitialisé.',
    'sent' => 'Si un compte correspond à cette adresse, il recevra un e-mail avec un lien de réinitialisation.',
    'throttled' => 'Veuillez patienter avant de réessayer.',
    'token' => "Ce lien de réinitialisation n'est pas valide pour cette adresse e-mail.",
    'user' => "Ce lien de réinitialisation n'est pas valide pour cette adresse e-mail.",

];
