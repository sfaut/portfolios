import Alpine from "alpinejs";
import accountSearch from "./account-search";
import analyticLines from "./analytic-lines";
import avatarEditor from "./avatar-editor";
import datePicker from "./date-picker";
import ledger from "./ledger";
import monthPicker from "./month-picker";
import multiSelect from "./multi-select";
import sectionSpy from "./section-spy";
import theme from "./theme";
import toasts from "./toasts";

window.Alpine = Alpine;

Alpine.store("theme", theme);
Alpine.store("toasts", toasts);
Alpine.data("accountSearch", accountSearch);
Alpine.data("analyticLines", analyticLines);
Alpine.data("avatarEditor", avatarEditor);
Alpine.data("datePicker", datePicker);
Alpine.data("ledger", ledger);
Alpine.data("monthPicker", monthPicker);
Alpine.data("multiSelect", multiSelect);
Alpine.data("sectionSpy", sectionSpy);

Alpine.start();
