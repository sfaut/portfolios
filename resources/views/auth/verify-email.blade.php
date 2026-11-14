{{--
    Demande de vérification de l'adresse, où mène le middleware verified : nouvel envoi du lien, puis,
    en pied, la déconnexion.
--}}
<x-guest-layout>
    <x-slot:title>Vérifiez votre adresse</x-slot:title>
    <x-slot:description>
        Avant de continuer, suivez le lien que nous vous avons envoyé par e-mail.
        Si vous ne l'avez pas reçu, demandez-en un nouveau.
    </x-slot:description>

    @if (session('status') === 'verification-link-sent')
        <div class="font-medium text-sm text-success-600 dark:text-success-400">
            Un nouveau lien de vérification vient d'être envoyé à votre adresse e-mail.
        </div>
    @endif

    <form method="post" action="{{ route('verification.send') }}">
        @csrf

        <x-primary-button class="w-full justify-center">Renvoyer le lien de vérification</x-primary-button>
    </form>

    <x-slot:footer>
        <form method="post" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="font-medium text-indigo-700 dark:text-indigo-400 underline hover:no-underline rounded-sm focus:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900"
            >Se déconnecter</button>
        </form>
    </x-slot:footer>
</x-guest-layout>
