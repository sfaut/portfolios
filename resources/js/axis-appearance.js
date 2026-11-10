import { matches } from "./axis-appearance-rules";

/**
 * Appearance of an analytic axis in its form (components/axis-appearance.blade.php): preview of
 * the badge, color and icon.
 *
 * The color and the icon are native radios, bound by x-model; this component adds the preview,
 * which follows the name typed in the field of the form, the tint of the icon grid in the color
 * chosen, the search, which hides the icons and the themes that do not match, and the theme tabs,
 * which scroll the grid to their theme.
 *
 * @param {object} config Data of App\View\Components\AxisAppearance.
 * @param {string} config.nameField Identifier of the field of the axis name.
 * @param {string} config.icon Icon chosen on load (Lucide name).
 * @param {string} config.color Color chosen on load.
 * @param {Record<string, string>} config.badges Classes of the badge of each color.
 * @param {Record<string, string>} config.texts Classes of the text of each color.
 */
export default function axisAppearance(config) {
    return {
        icon: config.icon,
        color: config.color,
        badges: config.badges,
        texts: config.texts,
        name: document.getElementById(config.nameField)?.value ?? "",
        query: "",
        hint: "",
        empty: false,

        /**
         * Follows the name field, filters the grid on each search, and shows the icon chosen.
         */
        init() {
            document.getElementById(config.nameField)?.addEventListener("input", (event) => {
                this.name = event.target.value;
            });

            this.$watch("query", () => this.filter());

            this.$nextTick(() => {
                const chosen = this.item(this.icon);

                if (chosen) {
                    this.$refs.grid.scrollTop = chosen.offsetTop - this.$refs.grid.clientHeight / 2;
                }
            });
        },

        /**
         * Label of an icon of the grid.
         *
         * @param {string} icon Lucide name.
         * @returns {?HTMLElement}
         */
        item(icon) {
            return this.$refs.grid.querySelector(`[data-icon="${CSS.escape(icon)}"]`);
        },

        /**
         * SVG of the icon chosen, copied from the grid for the preview.
         */
        get iconSvg() {
            return this.item(this.icon)?.querySelector("svg")?.outerHTML ?? "";
        },

        /**
         * French name of the icon chosen.
         */
        get label() {
            return this.item(this.icon)?.dataset.label ?? "";
        },

        /**
         * Hides the icons that do not match the search, and the themes left empty.
         */
        filter() {
            let shown = 0;

            for (const section of this.$refs.grid.querySelectorAll("[data-theme]")) {
                let found = 0;

                for (const item of section.querySelectorAll("[data-icon]")) {
                    const match = matches(item.dataset.search, this.query);

                    item.hidden = !match;
                    found += match ? 1 : 0;
                }

                section.hidden = found === 0;
                shown += found;
            }

            this.empty = shown === 0;
            this.$refs.grid.scrollTop = 0;
        },

        /**
         * Scrolls the grid to a theme.
         *
         * @param {string} id Identifier of the theme section.
         */
        jump(id) {
            const section = document.getElementById(id);

            if (section) {
                this.$refs.grid.scrollTo({ top: section.offsetTop, behavior: "smooth" });
            }
        },
    };
}
