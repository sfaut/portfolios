{{--
    Logo de Portfolios : le solde cumulé qui monte par paliers, dans un carré arrondi.

    Tracé en currentColor : la couleur vient de la classe text-* passée par l'appelant, la taille de
    h-* / w-* / size-*. Les fill="none" des tracés gardent le dessin évidé même sous fill-current.
--}}
<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Portfolios" {{ $attributes }}>
    <g fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="4.25" y="4.25" width="23.5" height="23.5" rx="6" fill="none"/>
        <polyline points="9,21 13.5,21 13.5,16 18,16 18,11.5 23,11.5" fill="none"/>
    </g>
</svg>
