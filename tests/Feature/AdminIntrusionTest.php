<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tests d'intrusion de la zone d'administration : chaque attaque d'un utilisateur malveillant doit
 * échouer, sans révéler la zone.
 */
beforeEach(function () {
    $this->withSession(['auth.password_confirmed_at' => time()]);
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

test('an ordinary user gets a 404 whatever the method', function (string $method) {
    $response = $this->actingAs($this->user)->call($method, '/admin/server');

    $response->assertNotFound();
})->with(['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);

test('an ordinary user gets a 404 whatever the address', function (string $uri) {
    $response = $this->actingAs($this->user)->get($uri);

    $response->assertNotFound();
})->with([
    '/admin/server/',
    '/ADMIN/server',
    '/admin/./server',
    '/admin/%73erver',
    '/admin/server?x=1',
    '/admin',
    '/admin/',
    '/admin/users',
    '/admin/server/../server',
]);

test('an ordinary user gets a 404 in JSON and in a script request', function (array $headers) {
    $response = $this->actingAs($this->user)->get('/admin/server', $headers);

    $response->assertNotFound()->assertDontSee('admin');
})->with([
    'json' => [['Accept' => 'application/json']],
    'script' => [['X-Requested-With' => 'XMLHttpRequest']],
]);

test('an unverified ordinary user gets a 404, not the verification page', function () {
    $response = $this->actingAs(User::factory()->unverified()->create())->get('/admin/server');

    $response->assertNotFound();
});

test('the 404 of the administration is the 404 of an unknown address', function () {
    $this->actingAs($this->user);

    $administration = $this->get('/admin/server');
    $unknown = $this->get('/no-such-page');

    expect($administration->status())->toBe($unknown->status())
        ->and($administration->getContent())->toBe($unknown->getContent())
        ->and($administration->headers->has('X-Robots-Tag'))->toBeFalse();
});

test('the administrator gets a 404 on any other method or address of the area', function (string $method, string $uri) {
    $response = $this->actingAs($this->admin)->call($method, $uri);

    $response->assertNotFound();
})->with([
    ['POST', '/admin/server'],
    ['DELETE', '/admin/server'],
    ['GET', '/admin/users'],
]);

test('sending is_admin in a form promotes nobody', function (string $method, string $uri, array $data) {
    $this->actingAs($this->user)->call($method, $uri, [...$data, 'is_admin' => '1']);

    expect($this->user->refresh()->is_admin)->toBeFalse();
    $this->actingAs($this->user->refresh())->get('/admin/server')->assertNotFound();
})->with([
    'profile' => fn () => ['PATCH', '/profile', ['last_name' => 'Nom', 'first_name' => 'Prénom', 'email' => 'user@example.com']],
    'password' => fn () => ['PUT', '/password', ['current_password' => 'password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123']],
    'portfolio' => fn () => ['POST', '/portfolio', ['name' => 'Portfolio', 'description' => '']],
]);

test('registering with is_admin gives no access', function () {
    auth()->logout();
    $this->post('/register', [
        'last_name' => 'Nom',
        'first_name' => 'Prénom',
        'email' => 'intruder@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'is_admin' => '1',
    ]);

    $intruder = User::where('email', 'intruder@example.com')->sole();
    $intruder->markEmailAsVerified();

    expect($intruder->is_admin)->toBeFalse();
    $this->actingAs($intruder)->get('/admin/server')->assertNotFound();
});

test('an unverified administrator is sent to the verification', function () {
    $response = $this->actingAs(User::factory()->admin()->unverified()->create())->get('/admin/server');

    $response->assertRedirect(route('verification.notice'));
});

test('an administrator without a recent password confirmation is asked for it', function () {
    $this->flushSession();

    $response = $this->actingAs($this->admin)->get('/admin/server');

    $response
        ->assertRedirect(route('password.confirm'))
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('the session of a logged out administrator gives no access', function () {
    $this->post('/login', ['email' => $this->admin->email, 'password' => 'password']);
    $this->get('/admin/server')->assertOk();

    $this->post('/logout');

    $this->get('/admin/server')->assertRedirect(route('login'));
});

test('an administrator demoted in the database loses the access at the next request', function () {
    $this->post('/login', ['email' => $this->admin->email, 'password' => 'password']);
    $this->get('/admin/server')->assertOk();

    DB::table('users')->where('id', $this->admin->id)->update(['is_admin' => false]);
    auth()->forgetGuards();

    $this->get('/admin/server')->assertNotFound();
});

test('forged parameters change nothing and break nothing', function (array $query) {
    $response = $this->actingAs($this->admin)->get(route('admin.server', $query));

    $response
        ->assertOk()
        ->assertSeeInOrder(['Application', 'PHP', 'Système', 'Base de données', 'File et mails', 'Données'])
        ->assertDontSee('<script>alert(1)</script>', escape: false);
})->with([
    'debug' => [['debug' => '1', 'APP_DEBUG' => 'true']],
    'array' => [['section' => ['php', 'system']]],
    'sql' => [['id' => "1' OR '1'='1", 'order' => 'name; DROP TABLE users']],
    'html' => [['q' => '<script>alert(1)</script>']],
]);

test('the status leaks no secret', function () {
    config([
        'database.connections.mysql.password' => 'db-secret-2f9c',
        'mail.mailers.smtp.password' => 'smtp-secret-7b1e',
        'app.admin.password' => 'admin-secret-4d0a',
    ]);
    DB::table('failed_jobs')->insert([
        'uuid' => (string) str()->uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Mail\\PortfolioCreatedMail']),
        'exception' => 'RuntimeException: exception-secret-9a3f',
        'failed_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertOk()
        ->assertDontSee('db-secret-2f9c')
        ->assertDontSee('smtp-secret-7b1e')
        ->assertDontSee('admin-secret-4d0a')
        ->assertDontSee('exception-secret-9a3f')
        ->assertDontSee((string) config('app.key'))
        ->assertDontSee('backups');
});

test('the status is neither cached nor indexed', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.server'));

    $response
        ->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
