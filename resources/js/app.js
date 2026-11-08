import Alpine from "alpinejs";
import accountSearch from "./account-search";
import analyticLines from "./analytic-lines";
import theme from "./theme";

window.Alpine = Alpine;

Alpine.store("theme", theme);
Alpine.data("accountSearch", accountSearch);
Alpine.data("analyticLines", analyticLines);

Alpine.start();
