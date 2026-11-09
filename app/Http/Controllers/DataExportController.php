<?php

namespace App\Http\Controllers;

use App\Support\UserDataExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Téléchargement des données de l'utilisateur connecté, depuis son profil.
 */
class DataExportController extends Controller
{
    /**
     * Envoie l'archive ZIP des données de l'utilisateur (UserDataExport), écrite dans un fichier
     * temporaire supprimé après l'envoi.
     */
    public function __invoke(Request $request): BinaryFileResponse
    {
        $export = new UserDataExport($request->user());

        $path = tempnam(sys_get_temp_dir(), 'portfolios-export-');

        $export->writeTo($path);

        return response()
            ->download($path, $export->fileName(), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }
}
