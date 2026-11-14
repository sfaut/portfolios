import Alpine from "alpinejs";
import sort from "@alpinejs/sort";
import accountSearch from "./account-search";
import analyticLines from "./analytic-lines";
import avatarEditor from "./avatar-editor";
import axisAppearance from "./axis-appearance";
import datePicker from "./date-picker";
import ledger from "./ledger";
import monthPicker from "./month-picker";
import multiSelect from "./multi-select";
import sectionSpy from "./section-spy";
import tickBox from "./tick-box";
import theme from "./theme";
import toasts from "./toasts";

window.Alpine = Alpine;

Alpine.plugin(sort);

Alpine.store("theme", theme);
Alpine.store("toasts", toasts);
Alpine.data("accountSearch", accountSearch);
Alpine.data("analyticLines", analyticLines);
Alpine.data("avatarEditor", avatarEditor);
Alpine.data("axisAppearance", axisAppearance);
Alpine.data("datePicker", datePicker);
Alpine.data("ledger", ledger);
Alpine.data("monthPicker", monthPicker);
Alpine.data("multiSelect", multiSelect);
Alpine.data("sectionSpy", sectionSpy);
Alpine.data("tickBox", tickBox);

Alpine.start();
