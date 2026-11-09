<?php

namespace App\Support;

use GdImage;
use InvalidArgumentException;

/**
 * Photo de profil normalisée : un carré de 512 pixels en WebP, sans aucune métadonnée.
 *
 * L'image reçue est décodée par GD, puis réencodée : seuls ses pixels passent dans le fichier produit.
 * EXIF (dont la position GPS), XMP, profil ICC et commentaires disparaissent, comme tout contenu
 * glissé à la suite de l'image (fichier polyglotte, GIF suivi de PHP). Un JPEG est d'abord tourné
 * selon son orientation EXIF, un GIF animé réduit à sa première image ; une image qui n'est pas carrée
 * est recadrée au centre (envoi sans JavaScript : le navigateur recadre sinon lui-même). La
 * transparence est gardée.
 *
 * La taille en pixels est contrôlée sur l'en-tête, avant tout décodage : une image piégée, petite sur
 * le disque mais immense une fois décodée, n'atteint pas GD.
 */
final class AvatarImage
{
    /**
     * Côté du carré produit, en pixels.
     */
    public const int SIZE = 512;

    /**
     * Qualité de l'encodage WebP, de 0 à 100 : la plus haute.
     */
    public const int QUALITY = 100;

    /**
     * Nombre de pixels au plus de l'image reçue.
     */
    public const int MAX_PIXELS = 24_000_000;

    /**
     * Types d'image que GD décode ici, par type MIME.
     *
     * @return array<string, int> Type MIME => constante IMAGETYPE_*.
     */
    public static function supportedTypes(): array
    {
        $types = [
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png' => IMAGETYPE_PNG,
            'image/gif' => IMAGETYPE_GIF,
            'image/webp' => IMAGETYPE_WEBP,
        ];

        if (imagetypes() & IMG_AVIF) {
            $types['image/avif'] = IMAGETYPE_AVIF;
        }

        return $types;
    }

    /**
     * Normalise l'image du fichier donné.
     *
     * @param  string  $path  Chemin du fichier reçu.
     * @return string Contenu du fichier WebP produit.
     *
     * @throws InvalidArgumentException
     *                                  Quand le fichier n'est pas une image d'un type pris en charge, est trop grand en
     *                                  pixels ou ne se décode pas.
     */
    public static function normalize(string $path): string
    {
        $info = @getimagesize($path);

        if ($info === false || ! in_array($info[2], self::supportedTypes(), true)) {
            throw new InvalidArgumentException('Type d\'image non pris en charge.');
        }

        [$width, $height, $type] = $info;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            throw new InvalidArgumentException('Image trop grande.');
        }

        self::raiseMemoryLimit($width * $height);

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_AVIF => @imagecreatefromavif($path),
        };

        if ($image === false) {
            throw new InvalidArgumentException('Image illisible.');
        }

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        if ($type === IMAGETYPE_JPEG) {
            $image = self::orient($image, $path);
        }

        return self::encode(self::square($image));
    }

    /**
     * Tourne ou retourne un JPEG selon son orientation EXIF, pour qu'il s'affiche comme sur l'appareil.
     *
     * @param  GdImage  $image  Image décodée, sans orientation appliquée.
     * @param  string  $path  Chemin du fichier, dont l'EXIF donne l'orientation.
     */
    private static function orient(GdImage $image, string $path): GdImage
    {
        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, in_array($orientation, [4, 5], true) ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 6, 7 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }

    /**
     * Recadre au centre le plus grand carré de l'image, puis le ramène à SIZE pixels de côté, en gardant
     * la transparence.
     */
    private static function square(GdImage $image): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $side = min($width, $height);

        $square = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($square, false);
        imagesavealpha($square, true);
        imagefill($square, 0, 0, imagecolorallocatealpha($square, 0, 0, 0, 127));

        imagecopyresampled(
            $square, $image,
            0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2),
            self::SIZE, self::SIZE, $side, $side,
        );

        return $square;
    }

    /**
     * Encode l'image en WebP.
     *
     * @throws InvalidArgumentException
     *                                  Quand l'encodage échoue.
     */
    private static function encode(GdImage $image): string
    {
        ob_start();
        $encoded = imagewebp($image, null, self::QUALITY);
        $webp = (string) ob_get_clean();

        if (! $encoded || $webp === '') {
            throw new InvalidArgumentException('Encodage WebP impossible.');
        }

        return $webp;
    }

    /**
     * Relève au besoin la limite de mémoire de la requête, pour décoder l'image et la tourner : GD
     * garde 4 octets par pixel, et une rotation en fait une copie.
     *
     * @param  int  $pixels  Nombre de pixels de l'image.
     */
    private static function raiseMemoryLimit(int $pixels): void
    {
        $limit = (string) ini_get('memory_limit');

        if ($limit === '-1') {
            return;
        }

        $needed = memory_get_usage() + $pixels * 10 + 32 * 1024 * 1024;
        $unit = strtolower(substr($limit, -1));
        $current = (int) $limit * match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        if ($current < $needed) {
            ini_set('memory_limit', (string) $needed);
        }
    }
}
