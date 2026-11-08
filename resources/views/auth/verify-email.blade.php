<x-guest-layout>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        Avant de continuer, vérifiez votre adresse e-mail en cliquant sur le lien que nous vous avons envoyé.
        Si vous ne l'avez pas reçu, demandez-en un nouveau.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="font-medium text-sm text-success-600 dark:text-success-400">
            Un nouveau lien de vérification vient d'être envoyé à votre adresse e-mail.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="post" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Renvoyer le lien de vérification
                </x-primary-button>
            </div>
        </form>

        <form method="post" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                Se déconnecter
            </button>
        </form>
    </div>
</x-guest-layout>
