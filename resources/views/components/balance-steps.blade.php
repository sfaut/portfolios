{{--
    Illustration des pages invité : la ligne en paliers du logo, agrandie, sur ses lignes de repère.
    Elle se trace, reste, puis s'efface, en boucle de 5 secondes (animations balance-* de
    resources/css/app.css), et reste entière, immobile, quand le système demande de réduire les
    animations. Décorative, elle est masquée aux lecteurs d'écran.

    Dessin calé en bas à gauche (xMinYMax meet) : la taille et la place viennent de la classe passée
    par l'appelant.

    @var \Illuminate\View\ComponentAttributeBag $attributes Attributs du SVG (classe de taille et de place).
--}}
<svg viewBox="0 0 640 520" preserveAspectRatio="xMinYMax meet" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" {{ $attributes }}>
    <g class="stroke-indigo-900 stroke-1">
        <line x1="0" y1="430" x2="640" y2="430"/>
        <line x1="0" y1="330" x2="640" y2="330"/>
        <line x1="0" y1="230" x2="640" y2="230"/>
        <line x1="0" y1="130" x2="640" y2="130"/>
    </g>
    <path
        d="M0,470 H96 V430 H210 V330 H330 V230 H452 V130 H590 V60 H640 V520 H0 Z"
        class="fill-indigo-900/55 animate-balance-area motion-reduce:animate-none"
    />
    <polyline
        points="0,470 96,470 96,430 210,430 210,330 330,330 330,230 452,230 452,130 590,130 590,60"
        pathLength="1"
        stroke-dasharray="1 1"
        stroke-linecap="round"
        stroke-linejoin="round"
        class="fill-none stroke-indigo-400 stroke-4 animate-balance-line motion-reduce:animate-none"
    />
    <circle cx="590" cy="60" r="8" class="fill-indigo-100 animate-balance-dot motion-reduce:animate-none"/>
</svg>
