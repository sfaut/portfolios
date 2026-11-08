/**
 * Color theme chosen in the user menu: "light", "dark" or "system".
 *
 * The choice is kept per browser in localStorage, under the "theme" key, which
 * is absent for "system". It shows as the "light" or "dark" class of <html>,
 * read by the dark variant of resources/css/app.css; without either class, the
 * operating system decides. The inline script of layouts/empty.blade.php sets
 * the class before the first paint, to avoid a flash of the other theme.
 */

const KEY = "theme";

/**
 * Reads the stored choice, "system" when there is none or storage is blocked.
 *
 * @returns {"light" | "dark" | "system"}
 */
function read() {
    try {
        const mode = localStorage.getItem(KEY);

        return mode === "light" || mode === "dark" ? mode : "system";
    } catch {
        return "system";
    }
}

/**
 * Sets the class of <html> that matches the choice.
 *
 * @param {"light" | "dark" | "system"} mode
 */
function apply(mode) {
    const root = document.documentElement;

    root.classList.toggle("light", mode === "light");
    root.classList.toggle("dark", mode === "dark");
}

/**
 * Alpine store, registered as "theme".
 */
export default {
    /**
     * Current choice.
     *
     * @type {"light" | "dark" | "system"}
     */
    mode: read(),

    /**
     * Follows the choices made in the other tabs of the browser.
     */
    init() {
        window.addEventListener("storage", (event) => {
            if (event.key === KEY || event.key === null) {
                this.mode = read();
                apply(this.mode);
            }
        });
    },

    /**
     * Applies and stores a choice. Without storage, it holds for the page only.
     *
     * @param {"light" | "dark" | "system"} mode
     */
    set(mode) {
        this.mode = mode;
        apply(mode);

        try {
            if (mode === "system") {
                localStorage.removeItem(KEY);
            } else {
                localStorage.setItem(KEY, mode);
            }
        } catch {
            //
        }
    },
};
