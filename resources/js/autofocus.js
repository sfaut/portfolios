/**
 * Places the caret at the end of the field that gets the focus on page load (autofocus attribute),
 * so that its current value can be completed or erased right away, instead of being typed before.
 *
 * Only the first focus is concerned: a later click places the caret where the pointer is. The
 * browser may give the autofocus focus before or after this script runs, hence both cases. Some
 * fields (email, number…) have no caret to move: setSelectionRange() throws for them.
 */
export default function placeCaretAtEndOfAutofocus() {
    const field = document.querySelector("input[autofocus], textarea[autofocus]");

    if (field === null) {
        return;
    }

    const placeCaretAtEnd = () => {
        try {
            field.setSelectionRange(field.value.length, field.value.length);
        } catch {
            // No caret for this type of field.
        }
    };

    if (document.activeElement === field) {
        placeCaretAtEnd();
    } else {
        field.addEventListener("focus", placeCaretAtEnd, { once: true });
    }
}
