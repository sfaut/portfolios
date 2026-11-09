/**
 * Color theme: "light", "dark" or "system", chosen with the switch of the top bar or on the
 * Preferences page of the profile.
 *
 * For a signed-in user, the choice is saved on the profile: the server renders it on <html>
 * (class "light" or "dark", data-color-scheme) and gets each new choice at data-color-scheme-url.
 * The browser also keeps the last choice in localStorage, under the "theme" key, absent for
 * "system": the guest pages (login…) apply it, through the inline script of
 * layouts/empty.blade.php, before the first paint. The class is read by the dark variant of
 * resources/css/app.css; without either class, the operating system decides.
 */

const KEY = "theme";

/**
 * Turns any value into a choice, "system" by default.
 *
 * @param {string | null | undefined} value
 * @returns {"light" | "dark" | "system"}
 */
function normalize(value) {
    return value === "light" || value === "dark" ? value : "system";
}

/**
 * Reads the choice saved on the profile, or else the one stored in the browser.
 *
 * @returns {"light" | "dark" | "system"}
 */
function read() {
    const saved = document.documentElement.dataset.colorScheme;

    if (saved !== undefined) {
        return normalize(saved);
    }

    try {
        return normalize(localStorage.getItem(KEY));
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
 * Stores the choice in the browser. Without storage, nothing is kept.
 *
 * @param {"light" | "dark" | "system"} mode
 */
function store(mode) {
    try {
        if (mode === "system") {
            localStorage.removeItem(KEY);
        } else {
            localStorage.setItem(KEY, mode);
        }
    } catch {
        //
    }
}

/**
 * Saves the choice on the profile of the signed-in user. A failure is silent: the choice then
 * holds for the browser only.
 *
 * @param {"light" | "dark" | "system"} mode
 */
async function save(mode) {
    const root = document.documentElement;
    const url = root.dataset.colorSchemeUrl;

    if (url === undefined) {
        return;
    }

    root.dataset.colorScheme = mode;

    try {
        await fetch(url, {
            method: "PUT",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify({ color_scheme: mode }),
        });
    } catch {
        //
    }
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
     * Follows the choices made in the other tabs of the browser, which save them themselves.
     */
    init() {
        window.addEventListener("storage", (event) => {
            if (event.key === KEY || event.key === null) {
                let mode = "system";

                try {
                    mode = normalize(localStorage.getItem(KEY));
                } catch {
                    //
                }

                this.mode = mode;
                apply(mode);

                if (document.documentElement.dataset.colorScheme !== undefined) {
                    document.documentElement.dataset.colorScheme = mode;
                }
            }
        });
    },

    /**
     * Applies, stores and saves a choice.
     *
     * @param {"light" | "dark" | "system"} mode
     */
    set(mode) {
        this.mode = normalize(mode);
        apply(this.mode);
        store(this.mode);
        save(this.mode);
    },
};
