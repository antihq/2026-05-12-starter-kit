<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('two factor setup page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.setup'))
        ->assertOk()
        ->assertSee('Set up two-factor authentication')
        ->assertSee('Step 1')
        ->assertSee('Manual setup key')
        ->assertSee('Step 2')
        ->assertSee('Confirm');
});

test('two factor setup page requires password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('two-factor.setup'))
        ->assertRedirect(route('password.confirm'));
});

test('two factor setup page redirects if two factor already enabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.setup'))
        ->assertRedirect(route('security.edit'));
});

test('two factor setup enables two factor and shows qr code on mount', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.two-factor-setup');

    $component->assertSet('requiresConfirmation', true)
        ->assertSet('qrCodeSvg', fn ($svg) => str_contains($svg, '<svg'))
        ->assertSet('manualSetupKey', fn ($key) => filled($key));

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor confirmation fails with invalid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.two-factor-setup')
        ->set('code', '000000')
        ->call('confirmTwoFactor');

    $component->assertHasErrors(['code']);
});

test('two factor confirmation succeeds with valid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.two-factor-setup');

    $user->refresh();
    $secret = decrypt($user->two_factor_secret);

    $totp = (new PragmaRX\Google2FA\Google2FA);
    $validCode = $totp->getCurrentOtp($secret);

    $component = Livewire::test('pages::settings.two-factor-setup')
        ->set('code', $validCode)
        ->call('confirmTwoFactor');

    $component->assertHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor setup page without confirmation shows enable button', function () {
    Features::twoFactorAuthentication([
        'confirm' => false,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.setup'))
        ->assertOk()
        ->assertSee('Enable')
        ->assertDontSee('Step 2');
});
