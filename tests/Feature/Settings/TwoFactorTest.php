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

test('settings page shows enable authenticator button when two factor disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show');

    $component->assertSet('twoFactorEnabled', false)
        ->assertSee('Enable authenticator');
});

test('settings page hides two factor section when feature is disabled', function () {
    config(['fortify.features' => []]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.show'))
        ->assertOk()
        ->assertDontSeeHtml('Enable authenticator');
});

test('two factor disabled when confirmation abandoned between requests', function () {
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => null,
    ])->save();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show');

    $component->assertSet('twoFactorEnabled', false);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'two_factor_secret' => null,
        'two_factor_recovery_codes' => null,
    ]);
});

test('settings page shows disable form and recovery codes when two factor enabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show');

    $component->assertSet('twoFactorEnabled', true)
        ->assertSee('Disable authenticator')
        ->assertSee('Recovery codes');
});

test('settings page shows recovery codes remaining when two factor enabled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show');

    $component->assertSet('recoveryCodesRemaining', 1);
});

test('settings page does not show recovery codes when two factor disabled', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.show')
        ->assertSet('twoFactorEnabled', false)
        ->assertSet('recoveryCodes', [])
        ->assertDontSee('Regenerate codes')
        ->assertDontSee('Disable authenticator');
});
