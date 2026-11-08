import Alpine from "alpinejs";
import theme from "./theme";

window.Alpine = Alpine;

Alpine.store("theme", theme);

Alpine.start();
