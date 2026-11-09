<?php

namespace App\Http\Requests;

use App\Support\AvatarImage;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Envoi d'une photo de profil : une image JPEG, PNG, GIF, WebP (ou AVIF si GD la lit), de 8 Mo au plus.
 *
 * Le type se lit sur le contenu du fichier, jamais sur son nom ; un SVG, qui est du code, est refusé.
 * Côtés et nombre de pixels se contrôlent sur l'en-tête de l'image, avant que GD ne la décode
 * (App\Support\AvatarImage).
 */
class ProfileAvatarRequest extends FormRequest
{
    /**
     * Poids maximal du fichier, en kilo-octets.
     */
    public const int MAX_KILOBYTES = 8192;

    /**
     * Côté maximal de l'image, en pixels.
     */
    public const int MAX_SIDE = 6000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Règles de validation de la photo.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'bail',
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'mimetypes:'.implode(',', array_keys(AvatarImage::supportedTypes())),
                'dimensions:max_width='.self::MAX_SIDE.',max_height='.self::MAX_SIDE,
                function (string $attribute, UploadedFile $file, Closure $fail): void {
                    [$width, $height] = @getimagesize($file->getRealPath()) ?: [0, 0];

                    if ($width * $height > AvatarImage::MAX_PIXELS) {
                        $fail('La photo doit compter au plus '.(AvatarImage::MAX_PIXELS / 1_000_000).' millions de pixels.');
                    }
                },
            ],
        ];
    }

    /**
     * Formats acceptés, en toutes lettres : « JPEG, PNG, GIF, WebP ou AVIF », sans AVIF quand GD ne le
     * lit pas.
     */
    public static function formats(): string
    {
        return isset(AvatarImage::supportedTypes()['image/avif']) ? 'JPEG, PNG, GIF, WebP ou AVIF' : 'JPEG, PNG, GIF ou WebP';
    }

    /**
     * Messages en français, qui nomment les formats acceptés et les limites.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $formats = self::formats();

        return [
            'avatar.required' => 'Choisissez une photo.',
            'avatar.file' => 'La photo n\'a pas été reçue.',
            'avatar.uploaded' => 'La photo n\'a pas été reçue : elle est peut-être trop lourde.',
            'avatar.max' => 'La photo doit peser au plus '.(self::MAX_KILOBYTES / 1024).' Mo.',
            'avatar.mimetypes' => "La photo doit être une image {$formats}.",
            'avatar.dimensions' => 'La photo doit mesurer au plus '.self::MAX_SIDE.' pixels de côté.',
        ];
    }
}
