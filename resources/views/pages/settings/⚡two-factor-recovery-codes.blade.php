<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Recovery codes')] class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        if (! auth()->user()->hasEnabledTwoFactorAuthentication()) {
            $this->redirectRoute('security.edit', navigate: true);

            return;
        }

        $this->loadRecoveryCodes();
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">Recovery codes</flux:heading>
        <p class="mt-2 text-sm max-w-prose">
            If you lose access to your authenticator device, you can use a recovery code to sign in. Each code can only be used once. Store them in a secure password manager.
        </p>

        <div class="mt-6 space-y-5">
            @error('recoveryCodes')
                <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
            @enderror

            @if (filled($recoveryCodes))
                <div
                    class="grid grid-cols-2 gap-x-8 gap-y-1 font-mono text-sm"
                    role="list"
                    aria-label="Recovery codes"
                >
                    @foreach($recoveryCodes as $code)
                        <div
                            role="listitem"
                            class="select-text"
                            wire:loading.class="opacity-50 animate-pulse"
                        >
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                <p class="text-sm">
                    Each code can be used once to sign in and will be removed after use. If you run out of codes, regenerate a new set below.
                </p>

                <flux:button
                    size="sm"
                    wire:click="regenerateRecoveryCodes"
                >
                    Regenerate codes
                </flux:button>
            @else
                <flux:callout variant="warning" icon="exclamation-triangle" heading="No recovery codes">
                    No recovery codes were found. Set up two-factor authentication to generate a set.
                </flux:callout>
            @endif
        </div>

        <flux:heading class="mt-10" level="2">
            What happens when you regenerate
        </flux:heading>

        <flux:separator class="mt-2" />

        <x-description.list>
            <x-description.term>Current codes invalidated</x-description.term>
            <x-description.details>All existing recovery codes will stop working immediately. Any codes you have saved elsewhere will no longer grant access.</x-description.details>

            <x-description.term>New codes generated</x-description.term>
            <x-description.details>A fresh set of 8 recovery codes will be created. You will need to save the new codes in your password manager.</x-description.details>

            <x-description.term>No 2FA interruption</x-description.term>
            <x-description.details>Regenerating codes does not disable two-factor authentication. Your authenticator app will continue to work as normal.</x-description.details>
        </x-description.list>

        <flux:button class="mt-6" icon="arrow-left" :href="route('security.edit')" wire:navigate size="sm">
            Back to security
        </flux:button>
    </div>
</section>
