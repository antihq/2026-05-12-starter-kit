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

test('authenticator can be disabled with correct password', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('showDisableTwoFactorForm')
        ->set('disablePassword', 'password')
        ->call('disableTwoFactor');

    $component->assertHasNoErrors()
        ->assertSet('twoFactorEnabled', false)
        ->assertSet('showDisableForm', false);

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('authenticator disable fails with wrong password', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('showDisableTwoFactorForm')
        ->set('disablePassword', 'wrong-password')
        ->call('disableTwoFactor');

    $component->assertHasErrors(['disablePassword']);

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('authenticator disable form can be cancelled', function () {
    $user = User::factory()->withTwoFactor()->create();

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.show')
        ->call('showDisableTwoFactorForm')
        ->call('cancelDisableTwoFactor');

    $component->assertSet('showDisableForm', false);
});
