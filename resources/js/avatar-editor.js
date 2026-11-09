import { clampOffset, clampZoom, MAX_ZOOM, minScale, sourceRect } from "./avatar-crop-rules";

/**
 * Côté de l'image envoyée, en pixels : celui de la photo normalisée par le serveur.
 */
const OUTPUT_SIZE = 512;

/**
 * Marge entre le cercle et le bord de la scène, en pixels (classe inset-5 du cercle).
 */
const MARGIN = 20;

/**
 * Recadrage de la photo de profil, posé sur son formulaire (x-data="avatarCropper").
 *
 * Le fichier choisi s'ouvre dans la boîte de recadrage : l'image se glisse sous un cercle (pointeur,
 * tactile, flèches), se zoome (molette, curseur, boutons, + et −) et couvre toujours le cercle.
 * « Enregistrer » dessine le carré du cercle dans un canvas de 512 pixels, en WebP (en PNG si le
 * navigateur n'encode pas le WebP), le met à la place du fichier dans le champ, puis envoie le
 * formulaire : le serveur le valide et le normalise comme tout autre envoi. Une image que le
 * navigateur ne sait pas lire part telle quelle, et le serveur dit pourquoi il la refuse. Fermer la
 * boîte vide le champ.
 */
export default () => ({
    image: null,
    source: "",
    width: 0,
    height: 0,
    stage: 0,
    diameter: 0,
    zoom: 1,
    offset: { x: 0, y: 0 },
    drag: null,
    saving: false,
    maxZoom: MAX_ZOOM,

    get scale() {
        return minScale(this.width, this.height, this.diameter) * this.zoom;
    },

    destroy() {
        URL.revokeObjectURL(this.source);
    },

    choose(event) {
        const file = event.target.files[0];

        if (!file) {
            return;
        }

        URL.revokeObjectURL(this.source);
        this.source = URL.createObjectURL(file);

        const image = new Image();
        image.onload = () => {
            this.image = image;
            this.width = image.naturalWidth;
            this.height = image.naturalHeight;
            this.open();
        };
        image.onerror = () => this.$root.requestSubmit();
        image.src = this.source;
    },

    open() {
        this.$refs.dialog.showModal();
        this.stage = this.$refs.stage.clientWidth;
        this.diameter = this.stage - 2 * MARGIN;
        this.recenter();
        this.$refs.stage.focus();
    },

    cancel() {
        if (!this.saving) {
            this.$refs.input.value = "";
        }
    },

    recenter() {
        this.zoom = 1;
        this.offset = { x: 0, y: 0 };
    },

    moveTo(x, y) {
        this.offset = clampOffset({ x, y }, this.width, this.height, this.scale, this.diameter);
    },

    setZoom(zoom) {
        const next = clampZoom(zoom);
        const ratio = next / this.zoom;

        this.zoom = next;
        this.moveTo(this.offset.x * ratio, this.offset.y * ratio);
    },

    grab(event) {
        this.drag = { id: event.pointerId, x: event.clientX, y: event.clientY, offset: { ...this.offset } };
        event.currentTarget.setPointerCapture(event.pointerId);
    },

    pull(event) {
        if (this.drag?.id === event.pointerId) {
            this.moveTo(this.drag.offset.x + event.clientX - this.drag.x, this.drag.offset.y + event.clientY - this.drag.y);
        }
    },

    release(event) {
        if (this.drag?.id === event.pointerId) {
            this.drag = null;
        }
    },

    key(event) {
        const step = event.shiftKey ? 1 : 10;
        const moves = { ArrowLeft: [-step, 0], ArrowRight: [step, 0], ArrowUp: [0, -step], ArrowDown: [0, step] };

        if (moves[event.key]) {
            this.moveTo(this.offset.x + moves[event.key][0], this.offset.y + moves[event.key][1]);
        } else if (event.key === "+" || event.key === "=") {
            this.setZoom(this.zoom + 0.25);
        } else if (event.key === "-") {
            this.setZoom(this.zoom - 0.25);
        } else {
            return;
        }

        event.preventDefault();
    },

    /**
     * Place l'image dans un cadre carré de côté `frame`, où le cercle mesure `circle` : la scène, ou
     * un aperçu (le cercle remplit alors le cadre).
     */
    placement(frame, circle) {
        if (!this.diameter) {
            return {};
        }

        const ratio = circle / this.diameter;
        const width = this.width * this.scale * ratio;
        const height = this.height * this.scale * ratio;

        return {
            width: `${width}px`,
            height: `${height}px`,
            left: `${(frame - width) / 2 + this.offset.x * ratio}px`,
            top: `${(frame - height) / 2 + this.offset.y * ratio}px`,
        };
    },

    async save() {
        if (this.saving) {
            return;
        }

        this.saving = true;

        const canvas = document.createElement("canvas");
        canvas.width = OUTPUT_SIZE;
        canvas.height = OUTPUT_SIZE;

        const context = canvas.getContext("2d");
        const rect = sourceRect(this.width, this.height, this.scale, this.offset, this.diameter);
        context.imageSmoothingQuality = "high";
        context.drawImage(this.image, rect.x, rect.y, rect.size, rect.size, 0, 0, OUTPUT_SIZE, OUTPUT_SIZE);

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/webp", 0.92));

        if (blob) {
            const transfer = new DataTransfer();
            const name = blob.type === "image/webp" ? "photo.webp" : "photo.png";
            transfer.items.add(new File([blob], name, { type: blob.type }));
            this.$refs.input.files = transfer.files;
        }

        this.$root.requestSubmit();
    },
});
