import Alpine from "alpinejs";
import accountSearch from "./account-search";
import analyticLines from "./analytic-lines";
import monthPicker from "./month-picker";
import sectionSpy from "./section-spy";
import theme from "./theme";

window.Alpine = Alpine;

Alpine.store("theme", theme);
Alpine.data("accountSearch", accountSearch);
Alpine.data("analyticLines", analyticLines);
Alpine.data("monthPicker", monthPicker);
Alpine.data("sectionSpy", sectionSpy);

Alpine.start();
