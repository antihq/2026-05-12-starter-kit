<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Set up two-factor authentication')] class extends Component {
    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $requiresConfirmation;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public function mount(): void
    {
        if (auth()->user()->hasEnabledTwoFactorAuthentication()) {
            $this->redirectRoute('security.edit', navigate: true);

            return;
        }

        $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');

        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->redirectRoute('security.edit', navigate: true);
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">{{ __('Set up two-factor authentication') }}</flux:heading>
        <p class="mt-2 text-sm max-w-prose">
            {{ __('Two-factor authentication requires a code from your phone during sign in, making it significantly harder for anyone to access your account without both your password and your device.') }}
        </p>

        <div class="mt-6">
            @error('setupData')
                <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
            @enderror

            <flux:heading level="2">
                {{ __('Step 1 — Add your account') }}
            </flux:heading>
            <p class="text-sm max-w-prose mt-1">
                {{ __('Scan the QR code below, or enter the setup key manually in your authenticator app.') }}
            </p>

            <div class="mt-6 space-y-5">
                <div>
                    {!! $qrCodeSvg !!}
                </div>

                <flux:input
                    :value="$manualSetupKey"
                    readonly
                    variant="filled"
                    copyable
                    icon="key"
                    :label="__('Manual setup key')"
                    class="max-w-lg"
                    input:class="font-mono"
                    size="sm"
                />
            </div>

            @if ($requiresConfirmation)
                <flux:heading level="2" class="mt-6">
                    {{ __('Step 2 — Confirm setup') }}
                </flux:heading>
                <p class="text-sm max-w-prose mt-1">
                    {{ __('Enter the 6-digit code from your authenticator app to complete setup.') }}
                </p>

                <div class="mt-6 space-y-5">
                    <flux:field>
                        <flux:label>{{ __('Authentication code') }}</flux:label>
                        <flux:otp
                            name="code"
                            wire:model="code"
                            length="6"
                        />
                        <flux:error name="code" />
                    </flux:field>

                    <flux:button
                        variant="primary"
                        wire:click="confirmTwoFactor"
                        x-bind:disabled="$wire.code.length < 6"
                        size="sm"
                    >
                        {{ __('Confirm') }}
                    </flux:button>
                </div>
            @else
                <div class="flex items-center gap-3">
                    <flux:button
                        variant="primary"
                        size="sm"
                        :disabled="$errors->has('setupData')"
                        :href="route('security.edit')"
                        wire:navigate
                    >
                        {{ __('Enable') }}
                    </flux:button>
                </div>
            @endif
        </div>

        <flux:heading class="mt-10" level="2">
            {{ __('What happens after enabling') }}
        </flux:heading>

        <flux:separator class="mt-2" />

        <x-description.list>
            <x-description.term>{{ __('Sign-in') }}</x-description.term>
            <x-description.details>{{ __('You will be prompted for a 6-digit code from your authenticator app during sign-in, in addition to your password.') }}</x-description.details>

            <x-description.term>{{ __('Recovery codes') }}</x-description.term>
            <x-description.details>{{ __('You will receive recovery codes to regain access if you lose your authenticator device. Store them in a secure password manager.') }}</x-description.details>
        </x-description.list>

        <flux:button class="mt-10" icon="arrow-left" :href="route('security.edit')" wire:navigate size="sm">
            {{ __('Back to security') }}
        </flux:button>
    </div>
</section>
