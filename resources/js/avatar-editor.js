import { cameraError, centerSquare, clampOffset, clampZoom, MAX_ZOOM, minScale, sourceRect } from "./avatar-crop-rules";

/**
 * Côté de l'image envoyée, en pixels : celui de la photo normalisée par le serveur.
 */
const OUTPUT_SIZE = 512;

/**
 * Marge entre le cercle et le bord de la scène, en pixels (classe inset-5 du cercle).
 */
const MARGIN = 20;

/**
 * Durée du retardateur, en secondes (animation animate-countdown de app.css).
 */
const TIMER_SECONDS = 3;

/**
 * Atelier de la photo de profil, posé sur son formulaire (x-data="avatarEditor") : une boîte, deux
 * entrées, l'import d'une image et la prise de vue par la caméra.
 *
 * La boîte garde la même scène carrée et son cercle ; seul le contenu change selon `mode` :
 * - `camera` : la vidéo en direct, en miroir. « Prendre la photo » fige l'image aussitôt, « Dans
 *   3 s » après un compte à rebours. La photo garde le carré central de la vidéo, en vue réelle
 *   (sans miroir), puis passe au recadrage, zoomée sur le cadre que montrait le cercle. La caméra
 *   s'éteint dès la prise de vue, comme à la fermeture de la boîte.
 * - `crop` : l'image se glisse sous le cercle (pointeur, tactile, flèches), se zoome (molette,
 *   curseur, boutons, + et −) et couvre toujours le cercle.
 *
 * Une image importée ou déposée sur la scène ouvre le recadrage. « Enregistrer » dessine le carré du
 * cercle dans un canvas de 512 pixels, en WebP de qualité maximale (en PNG si le navigateur n'encode
 * pas le WebP), le met à la place du fichier dans le champ, puis envoie le formulaire : le serveur
 * le valide et le normalise comme tout autre envoi. Une image que le navigateur ne sait pas lire
 * part telle quelle, et le serveur dit pourquoi il la refuse. Fermer la boîte vide le champ.
 *
 * Le champ de fichier ne reçoit son nom (`avatar`) que par Alpine : sans JavaScript, seul le champ
 * du <noscript> part avec le formulaire.
 */
export default () => {
    let stream = null;
    let timer = null;

    return {
        mode: "crop",
        origin: "file",
        cameraAvailable: false,
        live: false,
        devices: [],
        deviceId: "",
        error: "",
        countdown: 0,
        flash: false,
        announce: "",
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

        init() {
            this.cameraAvailable = window.isSecureContext && typeof navigator.mediaDevices?.getUserMedia === "function";
        },

        destroy() {
            this.stopTimer();
            this.stopCamera();
            URL.revokeObjectURL(this.source);
        },

        showDialog() {
            if (!this.$refs.dialog.open) {
                this.$refs.dialog.showModal();
            }

            this.stage = this.$refs.stage.clientWidth;
            this.diameter = this.stage - 2 * MARGIN;
        },

        close() {
            this.stopTimer();
            this.stopCamera();

            if (!this.saving) {
                this.$refs.input.value = "";
            }
        },

        pickFile() {
            this.$refs.input.click();
        },

        choose(event) {
            const file = event.target.files[0];

            if (file) {
                this.useImage(file, "file");
            }
        },

        drop(event) {
            const file = event.dataTransfer?.files[0];

            if (!file) {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.$refs.input.files = transfer.files;
            this.useImage(file, "file");
        },

        useImage(blob, origin, zoom = 1) {
            this.stopTimer();
            this.stopCamera();
            URL.revokeObjectURL(this.source);
            this.source = URL.createObjectURL(blob);

            const image = new Image();
            image.onload = () => {
                this.image = image;
                this.width = image.naturalWidth;
                this.height = image.naturalHeight;
                this.origin = origin;
                this.error = "";
                this.mode = "crop";
                this.showDialog();
                this.recenter();
                this.setZoom(zoom);
                this.$refs.stage.focus();
            };
            image.onerror = () => {
                if (origin === "file") {
                    this.$root.requestSubmit();
                }
            };
            image.src = this.source;
        },

        async openCamera() {
            this.mode = "camera";
            this.error = "";
            this.showDialog();
            await this.startCamera(this.deviceId);
        },

        async startCamera(deviceId = "") {
            this.stopTimer();
            this.stopCamera();

            const video = deviceId === "" ? { facingMode: "user" } : { deviceId: { exact: deviceId } };

            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { ...video, width: { ideal: 1280 }, height: { ideal: 1280 } },
                    audio: false,
                });
            } catch (error) {
                this.error = cameraError(error?.name);
                return;
            }

            if (this.mode !== "camera" || !this.$refs.dialog.open) {
                this.stopCamera();
                return;
            }

            this.$refs.video.srcObject = stream;
            this.live = true;
            this.deviceId = stream.getVideoTracks()[0]?.getSettings().deviceId ?? "";

            const devices = await navigator.mediaDevices.enumerateDevices();
            this.devices = devices
                .filter((device) => device.kind === "videoinput")
                .map((device, index) => ({ id: device.deviceId, label: device.label || `Caméra ${index + 1}` }));
        },

        stopCamera() {
            stream?.getTracks().forEach((track) => track.stop());
            stream = null;
            this.live = false;

            if (this.$refs.video) {
                this.$refs.video.srcObject = null;
            }
        },

        toggleTimer() {
            if (this.countdown > 0) {
                this.stopTimer();
                this.announce = "Retardateur arrêté.";
                return;
            }

            if (!this.live) {
                return;
            }

            this.countdown = TIMER_SECONDS;
            this.announce = String(this.countdown);

            const tick = () => {
                this.countdown -= 1;

                if (this.countdown === 0) {
                    timer = null;
                    this.shoot();
                    return;
                }

                this.announce = String(this.countdown);
                timer = setTimeout(tick, 1000);
            };

            timer = setTimeout(tick, 1000);
        },

        stopTimer() {
            clearTimeout(timer);
            timer = null;
            this.countdown = 0;
        },

        shoot() {
            const video = this.$refs.video;

            if (!this.live || !video.videoWidth) {
                return;
            }

            this.stopTimer();

            const rect = centerSquare(video.videoWidth, video.videoHeight);
            const canvas = document.createElement("canvas");
            canvas.width = Math.round(rect.size);
            canvas.height = Math.round(rect.size);
            canvas.getContext("2d").drawImage(video, rect.x, rect.y, rect.size, rect.size, 0, 0, canvas.width, canvas.height);

            this.flash = true;
            setTimeout(() => (this.flash = false), 150);
            this.announce = "Photo prise.";

            canvas.toBlob((blob) => blob && this.useImage(blob, "camera", this.stage / this.diameter), "image/png");
        },

        recenter() {
            this.zoom = 1;
            this.offset = { x: 0, y: 0 };
        },

        moveTo(x, y) {
            this.offset = clampOffset({ x, y }, this.width, this.height, this.scale, this.diameter);
        },

        setZoom(zoom) {
            if (this.mode !== "crop") {
                return;
            }

            const next = clampZoom(zoom);
            const ratio = next / this.zoom;

            this.zoom = next;
            this.moveTo(this.offset.x * ratio, this.offset.y * ratio);
        },

        grab(event) {
            if (this.mode !== "crop") {
                return;
            }

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
            if (this.mode !== "crop") {
                return;
            }

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
         * Place l'image dans un cadre carré de côté `frame`, où le cercle mesure `circle` : la scène,
         * ou un aperçu (le cercle remplit alors le cadre).
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
            if (this.saving || this.mode !== "crop") {
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

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/webp", 1));

            if (blob) {
                const transfer = new DataTransfer();
                const name = blob.type === "image/webp" ? "photo.webp" : "photo.png";
                transfer.items.add(new File([blob], name, { type: blob.type }));
                this.$refs.input.files = transfer.files;
            }

            this.$root.requestSubmit();
        },
    };
};
