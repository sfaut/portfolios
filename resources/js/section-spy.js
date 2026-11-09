/**
 * Marque, dans une navigation entre les sections d'une page, le lien de la section en lecture :
 * aria-current="true" sur ce lien, retiré des autres.
 *
 * Posé sur la racine de la navigation (x-data="sectionSpy"), il suit les cibles de ses liens d'ancre
 * (« #php ») : la section en lecture est la dernière, dans l'ordre de la page, dont le haut a passé
 * le tiers supérieur de la fenêtre ; en haut de la page, la première ; au bas, la dernière.
 */
export default () => ({
    links: [],
    onScroll: null,

    init() {
        this.links = [...this.$el.querySelectorAll('a[href^="#"]')]
            .map((link) => ({ link, target: document.getElementById(decodeURIComponent(link.hash.slice(1))) }))
            .filter(({ target }) => target !== null);

        this.onScroll = () => this.update();
        window.addEventListener("scroll", this.onScroll, { passive: true });
        window.addEventListener("resize", this.onScroll, { passive: true });
        this.update();
    },

    destroy() {
        window.removeEventListener("scroll", this.onScroll);
        window.removeEventListener("resize", this.onScroll);
    },

    update() {
        if (this.links.length === 0) {
            return;
        }

        const line = window.innerHeight / 3;
        const atBottom = window.scrollY > 0
            && window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
        const passed = this.links.filter(({ target }) => target.getBoundingClientRect().top <= line);
        const entry = atBottom ? this.links.at(-1) : (passed.at(-1) ?? this.links[0]);

        this.mark(entry.link);
    },

    mark(current) {
        if (current.getAttribute("aria-current") !== "true") {
            this.reveal(current);
        }

        this.links.forEach(({ link }) => {
            if (link === current) {
                link.setAttribute("aria-current", "true");
            } else {
                link.removeAttribute("aria-current");
            }
        });
    },

    /**
     * Centre le lien dans la liste qui défile en largeur (bande d'un écran étroit), sans faire
     * défiler la page.
     */
    reveal(link) {
        const list = link.closest("ul");

        if (list === null || list.scrollWidth <= list.clientWidth) {
            return;
        }

        const offset = link.getBoundingClientRect().left - list.getBoundingClientRect().left;
        list.scrollTo({ left: list.scrollLeft + offset - (list.clientWidth - link.offsetWidth) / 2, behavior: "smooth" });
    },
});
