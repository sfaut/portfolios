<?php

use App\Models\User;
use App\Support\AvatarImage;
use App\Support\UserDataExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Fichier envoyé, au contenu donné : son type se lit sur ce contenu, comme sur un vrai envoi.
 */
function avatarFile(string $content, string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'avatar');
    file_put_contents($path, $content);

    return new UploadedFile($path, $name, null, null, true);
}

/**
 * Image de 40 × 20 pixels, moitié gauche rouge, moitié droite bleue, encodée au format donné.
 */
function halvesImage(string $format): string
{
    $image = imagecreatetruecolor(40, 20);
    imagefilledrectangle($image, 0, 0, 19, 19, imagecolorallocate($image, 255, 0, 0));
    imagefilledrectangle($image, 20, 0, 39, 19, imagecolorallocate($image, 0, 0, 255));

    ob_start();
    match ($format) {
        'jpeg' => imagejpeg($image, null, 95),
        'png' => imagepng($image),
        'gif' => imagegif($image),
        'webp' => imagewebp($image, null, 95),
        'avif' => imageavif($image, null, 95),
    };

    return ob_get_clean();
}

/**
 * En-tête PNG seul, aux dimensions annoncées : il se lit sans que l'image ne se décode.
 */
function pngHeader(int $width, int $height): string
{
    $ihdr = 'IHDR'.pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0);

    return "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
}

/**
 * Glisse dans un JPEG un bloc EXIF : orientation et référence de latitude GPS.
 */
function withExif(string $jpeg, int $orientation): string
{
    $ifd0 = pack('v', 2)
        .pack('vvVvv', 0x0112, 3, 1, $orientation, 0)
        .pack('vvVV', 0x8825, 4, 1, 38)
        .pack('V', 0);
    $gps = pack('v', 1).pack('vvV', 0x0001, 2, 2)."N\0\0\0".pack('V', 0);
    $app1 = "Exif\0\0".'II'.pack('vV', 42, 8).$ifd0.$gps;

    return substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($app1) + 2).$app1.substr($jpeg, 2);
}

/**
 * Couleur dominante d'un pixel de la photo enregistrée : red, blue ou other.
 */
function dominantColor(string $webp, int $x, int $y): string
{
    $color = imagecolorsforindex($image = imagecreatefromstring($webp), imagecolorat($image, $x, $y));

    return match (true) {
        $color['red'] > 200 && $color['blue'] < 60 => 'red',
        $color['blue'] > 200 && $color['red'] < 60 => 'blue',
        default => 'other',
    };
}

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    $this->actingAs($this->user)->withSession(['auth.password_confirmed_at' => time()]);
});

test('a photo becomes a 512 pixel square in WebP, cropped at the centre', function (string $format) {
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage($format), "photo.{$format}")])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'avatar-updated');

    $path = $this->user->refresh()->avatar_path;
    expect($path)->toMatch('#^avatars/'.$this->user->id.'-[A-Za-z0-9]{40}\.webp$#');

    $webp = Storage::disk('local')->get($path);
    expect(array_slice(getimagesizefromstring($webp), 0, 3))->toBe([512, 512, IMAGETYPE_WEBP])
        ->and(substr($webp, 12, 4))->toBe('VP8L')
        ->and(dominantColor($webp, 100, 256))->toBe('red')
        ->and(dominantColor($webp, 412, 256))->toBe('blue');
})->with(array_values(array_filter(
    ['jpeg', 'png', 'gif', 'webp', 'avif'],
    fn (string $format): bool => isset(AvatarImage::supportedTypes()["image/{$format}"]),
)));

test('a JPEG is turned as its EXIF says, then loses all its metadata', function () {
    $jpeg = withExif(halvesImage('jpeg'), orientation: 6);
    $file = avatarFile($jpeg, 'photo.jpg');
    expect(exif_read_data($file->getRealPath()))->toHaveKey('GPSLatitudeRef', 'N');

    $this->put(route('profile.avatar.update'), ['avatar' => $file])->assertSessionHasNoErrors();

    $webp = Storage::disk('local')->get($this->user->refresh()->avatar_path);
    // Turned a quarter clockwise: red on top, blue below.
    expect(dominantColor($webp, 256, 100))->toBe('red')
        ->and(dominantColor($webp, 256, 412))->toBe('blue')
        ->and($webp)->not->toContain('Exif')->not->toContain('EXIF');
});

test('code hidden after an image is not kept', function () {
    $polyglot = halvesImage('gif').'<?php system($_GET["c"]); ?>';

    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile($polyglot, 'photo.gif')])->assertSessionHasNoErrors();

    expect(Storage::disk('local')->get($this->user->refresh()->avatar_path))->not->toContain('<?php');
});

test('the transparency of a photo is kept', function () {
    $image = imagecreatetruecolor(20, 20);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    ob_start();
    imagepng($image);

    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(ob_get_clean(), 'photo.png')])->assertSessionHasNoErrors();

    $result = imagecreatefromstring(Storage::disk('local')->get($this->user->refresh()->avatar_path));
    expect(imagecolorsforindex($result, imagecolorat($result, 10, 10))['alpha'])->toBe(127);
});

test('a file that is not a photo it can read is refused, and nothing is stored', function (?Closure $file, string $message) {
    $this->put(route('profile.avatar.update'), $file === null ? [] : ['avatar' => $file()])
        ->assertSessionHasErrors(['avatar' => $message]);

    expect($this->user->refresh()->avatar_path)->toBe('')
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    'no file' => [null, 'Choisissez une photo.'],
    'text named as a PNG' => [fn () => avatarFile('Bonjour', 'photo.png'), 'La photo doit être une image JPEG, PNG, GIF, WebP ou AVIF.'],
    'SVG' => [fn () => avatarFile('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'photo.svg'), 'La photo doit être une image JPEG, PNG, GIF, WebP ou AVIF.'],
    'PDF' => [fn () => avatarFile("%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\n", 'photo.pdf'), 'La photo doit être une image JPEG, PNG, GIF, WebP ou AVIF.'],
    'heavier than 8 MB' => [fn () => avatarFile(str_repeat("\0", 8193 * 1024), 'photo.png'), 'La photo doit peser au plus 8 Mo.'],
    'wider than 6000 pixels' => [fn () => avatarFile(pngHeader(6001, 10), 'photo.png'), 'La photo doit mesurer au plus 6000 pixels de côté.'],
    'more than 24 million pixels' => [fn () => avatarFile(pngHeader(5000, 5000), 'photo.png'), 'La photo doit compter au plus 24 millions de pixels.'],
    'unreadable' => [fn () => avatarFile(pngHeader(10, 10), 'photo.png'), 'Cette image ne peut pas être lue.'],
]);

test('a new photo replaces the previous file', function () {
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);
    $previous_path = $this->user->refresh()->avatar_path;

    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'b.png')]);

    expect($this->user->refresh()->avatar_path)->not->toBe($previous_path)
        ->and(Storage::disk('local')->allFiles())->toBe([$this->user->avatar_path]);
});

test('removing the photo deletes its file and brings back the initials', function () {
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);

    $this->delete(route('profile.avatar.destroy'))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'avatar-deleted');

    expect($this->user->refresh()->avatar_path)->toBe('')
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('deleting the account deletes the photo and its orphans, and keeps the files of another user', function () {
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);
    Storage::disk('local')->put("avatars/{$this->user->id}-orphelin.webp", 'x');
    Storage::disk('local')->put("avatars/{$this->user->id}1-autre.webp", 'x');

    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

    expect(Storage::disk('local')->allFiles())->toBe(["avatars/{$this->user->id}1-autre.webp"]);
});

test('replacing or removing the photo deletes the orphans of the user only', function () {
    $disk = Storage::disk('local');
    $orphan = "avatars/{$this->user->id}-orphelin.webp";
    $other = "avatars/{$this->user->id}1-autre.webp";
    $disk->put($orphan, 'x');
    $disk->put($other, 'x');

    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);

    expect($disk->allFiles())->toEqualCanonicalizing([$this->user->refresh()->avatar_path, $other]);

    $disk->put($orphan, 'x');
    $this->delete(route('profile.avatar.destroy'));

    expect($disk->allFiles())->toBe([$other]);
});

test('the photo is served to its owner only, private and cached for a year', function () {
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);
    $path = $this->user->refresh()->avatar_path;

    $response = $this->get($this->user->avatarUrl());

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/webp')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->streamedContent())->toBe(Storage::disk('local')->get($path))
        ->and(explode(', ', $response->headers->get('Cache-Control')))
        ->toContain('private')->toContain('max-age=31536000')->toContain('immutable');

    $this->actingAs(User::factory()->create())->get($this->user->avatarUrl())->assertNotFound();
});

test('without a photo, its address answers 404', function () {
    $this->get(route('profile.avatar'))->assertNotFound();
});

test('a guest reaches no photo', function () {
    auth()->logout();

    $this->get(route('profile.avatar'))->assertRedirect(route('login'));
});

test('sending a photo asks to confirm the password, then a verified address', function () {
    $this->flushSession();
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')])
        ->assertRedirect(route('password.confirm'));

    $this->actingAs(User::factory()->unverified()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')])
        ->assertRedirect(route('verification.notice'));

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('the profile opens the photo dialog by importing a photo or by taking one with the camera', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertSeeInOrder([
        'x-data="avatarEditor"',
        'x-bind:hidden="false"', 'Importer une photo',
        "x-bind:hidden=\"camera === 'unsupported'\"", "x-bind:disabled=\"camera !== 'available'\"", 'Prendre une photo',
        'id="avatar-camera-https" hidden', 'connexion sécurisée (HTTPS)',
        'id="avatar-dialog"', 'Déclencher', '<span class="sr-only">Dans</span> 3 s', 'Enregistrer la photo',
    ], escape: false);

    preg_match_all('/<button[^>]*\shidden="hidden"[^>]*>/', $response->getContent(), $hidden_buttons);
    expect($hidden_buttons[0])->toHaveCount(2);
});

test('without JavaScript, the profile sends the photo through a single file field', function () {
    $content = $this->get(route('profile.edit'))->getContent();

    preg_match('/<noscript>(.*?)<\/noscript>/s', $content, $noscript);
    expect($noscript[1])
        ->toContain('type="file" name="avatar" accept="image/jpeg,image/png')
        ->toContain('Envoyer la photo');

    $outside = str_replace($noscript[0], '', $content);
    expect($outside)->toContain('x-bind:name="\'avatar\'"')
        ->not->toMatch('/\sname="avatar"/');
});

test('the profile offers to remove the photo once there is one', function () {
    $this->get(route('profile.edit'))->assertDontSee('Retirer la photo');

    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);

    $this->get(route('profile.edit'))
        ->assertSee('Photo enregistrée.')
        ->assertSeeInOrder(['src="'.$this->user->refresh()->avatarUrl().'"', 'Retirer la photo'], escape: false);
});

test('an unverified address cannot send a photo from the profile', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('profile.edit'))
        ->assertSee('Vérifiez votre adresse pour ajouter une photo.')
        ->assertDontSee('name="avatar"', escape: false);
});

test('the data export holds the photo', function () {
    $this->put(route('profile.avatar.update'), ['avatar' => avatarFile(halvesImage('png'), 'a.png')]);
    $path = tempnam(sys_get_temp_dir(), 'export');

    (new UserDataExport($this->user->refresh()))->writeTo($path);

    $zip = new ZipArchive;
    $zip->open($path);
    expect($zip->getFromName('photo.webp'))->toBe(Storage::disk('local')->get($this->user->avatar_path));
    $zip->close();
    unlink($path);
});
