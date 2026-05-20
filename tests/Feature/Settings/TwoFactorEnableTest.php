<?php

use App\Models\User;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);
});

test('two factor can be enabled and shows qr code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('enableTwoFactor');

    $component->assertSet('showQrCode', true)
        ->assertSee('Step 1')
        ->assertSee('Manual setup key')
        ->assertSee('Step 2')
        ->assertSee('Confirm');

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor confirmation fails with invalid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('enableTwoFactor')
        ->set('code', '000000')
        ->call('confirmTwoFactor');

    $component->assertHasErrors(['code']);
});

test('two factor confirmation succeeds with valid code', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('enableTwoFactor');

    $user->refresh();
    $secret = decrypt($user->two_factor_secret);

    $totp = (new Google2FA);
    $validCode = $totp->getCurrentOtp($secret);

    $component
        ->set('code', $validCode)
        ->call('confirmTwoFactor');

    $component->assertHasNoErrors()
        ->assertSet('twoFactorEnabled', true)
        ->assertSet('showQrCode', false);

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor setup can be cancelled', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('enableTwoFactor')
        ->call('cancelTwoFactorSetup');

    $component->assertSet('showQrCode', false);

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('two factor enable without confirmation shows enable button', function () {
    Features::twoFactorAuthentication([
        'confirm' => false,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('enableTwoFactor');

    $component->assertSee('Enable')
        ->assertDontSee('Step 2');
});
