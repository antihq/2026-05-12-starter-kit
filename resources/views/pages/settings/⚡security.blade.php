<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Security settings') }}</flux:heading>

    <form wire:submit="updatePassword" class="mt-6 space-y-5">
        <flux:field>
            <flux:label badge="Required">{{ __('Current password') }}</flux:label>
            <flux:input wire:model="current_password" type="password" size="sm" required autocomplete="current-password" viewable class="max-w-lg" />
            <flux:error name="current_password" />
        </flux:field>

        <flux:field>
            <flux:label badge="Required">{{ __('New password') }}</flux:label>
            <flux:input wire:model="password" type="password" size="sm" required autocomplete="new-password" viewable class="max-w-lg" />
            <flux:error name="password" />
            <flux:description>{{ __('Minimum 8 characters.') }}</flux:description>
        </flux:field>

        <flux:field>
            <flux:label badge="Required">{{ __('Confirm password') }}</flux:label>
            <flux:input wire:model="password_confirmation" type="password" size="sm" required autocomplete="new-password" viewable class="max-w-lg" />
            <flux:error name="password_confirmation" />
            <flux:description>{{ __('Must match the new password.') }}</flux:description>
        </flux:field>

        <flux:button size="sm" variant="primary" type="submit" data-test="update-password-button">
            {{ __('Save') }}
        </flux:button>
    </form>

    @if ($canManageTwoFactor)
        <div class="mt-10">
            <flux:heading>{{ __('Two-factor authentication') }}</flux:heading>
            <flux:separator class="mt-2" />
            <x-description.list>
                <x-description.term>{{ __('Status') }}</x-description.term>
                <x-description.details>
                    @if ($twoFactorEnabled && Auth::user()->two_factor_confirmed_at)
                        {{ __('Confirmed :date', ['date' => Auth::user()->two_factor_confirmed_at->format('M j, Y')]) }}
                    @elseif ($twoFactorEnabled)
                        {{ __('Enabled') }}
                    @else
                        {{ __('Disabled') }}
                    @endif
                </x-description.details>

                <x-description.term>{{ __('Recovery codes') }}</x-description.term>
                <x-description.details>
                    {{ Auth::user()->two_factor_recovery_codes ? __('Generated') : __('Not generated') }}
                </x-description.details>
            </x-description.list>
        </div>

        <div class="mt-10" wire:cloak>
            @if ($twoFactorEnabled)
                <flux:heading>{{ __('Manage two-factor authentication') }}</flux:heading>

                <div class="mt-4 space-y-4">
                    <flux:text>
                        {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                    </flux:text>

                    <flux:button variant="danger" wire:click="disable">
                        {{ __('Disable 2FA') }}
                    </flux:button>

                    <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                </div>
            @else
                <flux:heading>{{ __('Enable two-factor authentication') }}</flux:heading>

                <div class="mt-4 space-y-4">
                    <p class="text-sm">
                        {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                    </p>

                    <flux:modal.trigger name="two-factor-setup-modal">
                        <flux:button variant="primary" wire:click="$dispatch('start-two-factor-setup')">
                            {{ __('Enable 2FA') }}
                        </flux:button>
                    </flux:modal.trigger>

                    <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                </div>
            @endif
        </div>
    @endif
</section>
