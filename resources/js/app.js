import Alpine from "alpinejs";
import analyticLines from "./analytic-lines";
import theme from "./theme";

window.Alpine = Alpine;

Alpine.store("theme", theme);
Alpine.data("analyticLines", analyticLines);

Alpine.start();
