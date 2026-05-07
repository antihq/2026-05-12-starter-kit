<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Security')] class extends Component
{
    use PasswordValidationRules;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
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

    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    #[Computed]
    public function passwordRulesDescription(): array
    {
        return [
            __('Minimum 8 characters'),
            __('at least one uppercase letter'),
            __('at least one lowercase letter'),
            __('at least one number'),
        ];
    }

    #[Computed]
    public function recoveryCodesRemaining(): int
    {
        $user = auth()->user();

        if (! $user->hasEnabledTwoFactorAuthentication() || ! $user->two_factor_recovery_codes) {
            return 0;
        }

        try {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

            return count($codes);
        } catch (Throwable) {
            return 0;
        }
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Security') }}</flux:heading>

    <flux:heading class="mt-10">{{ __('Update password') }}</flux:heading>
    <form wire:submit="updatePassword" class="mt-4 space-y-5">
        <flux:field>
            <flux:label>{{ __('Current password') }}</flux:label>
            <flux:input wire:model="current_password" type="password" size="sm" required autocomplete="current-password" viewable class="max-w-lg" />
            <flux:error name="current_password" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('New password') }}</flux:label>
            <flux:input wire:model="password" type="password" size="sm" required autocomplete="new-password" viewable class="max-w-lg" />
            <flux:error name="password" />
            <flux:description>
                {{ implode(', ', $this->passwordRulesDescription) . '.' }}
            </flux:description>
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Confirm password') }}</flux:label>
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

                @if ($twoFactorEnabled)
                    <x-description.term>{{ __('Recovery codes remaining') }}</x-description.term>
                    <x-description.details>
                        <span class="{{ $this->recoveryCodesRemaining <= 2 ? 'text-amber-600' : '' }}">
                            {{ __(':count of 8', ['count' => $this->recoveryCodesRemaining]) }}
                        </span>
                    </x-description.details>
                @endif
            </x-description.list>
        </div>

        <flux:separator variant="subtle" />

        <div class="mt-5">
            @if ($twoFactorEnabled)
                <div class="flex items-center gap-3">
                    <flux:button variant="danger" wire:click="disable" size="sm">
                        {{ __('Disable 2FA') }}
                    </flux:button>

                    <flux:button variant="outline" :href="route('recovery-codes.show')" size="sm" wire:navigate>
                        {{ __('View recovery codes') }}
                    </flux:button>
                </div>
            @else
                <flux:button variant="primary" :href="route('two-factor.setup')" size="sm" wire:navigate>
                    {{ __('Set up two-factor authentication') }}
                </flux:button>
            @endif
        </div>
    @endif
</section>
