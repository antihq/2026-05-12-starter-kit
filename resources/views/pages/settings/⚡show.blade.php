<?php

use App\Livewire\Forms\DeleteAccountForm;
use App\Livewire\Forms\UpdatePasswordForm;
use App\Livewire\Forms\UpdateProfileForm;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.account'), Title('Settings')] class extends Component
{
    public UpdateProfileForm $profileForm;

    public UpdatePasswordForm $passwordForm;

    public DeleteAccountForm $deleteForm;

    public bool $twoFactorEnabled = false;

    public bool $canManageTwoFactor = false;

    public bool $showQrCode = false;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    public string $disablePassword = '';

    #[Locked]
    public array $recoveryCodes = [];

    public function mount(): void
    {
        $this->profileForm->setUser(Auth::user());

        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            $user = Auth::user();

            if (Fortify::confirmsTwoFactorAuthentication() && is_null($user->two_factor_confirmed_at)) {
                app(DisableTwoFactorAuthentication::class)($user);
            }

            $this->twoFactorEnabled = $user->hasEnabledTwoFactorAuthentication();

            if ($this->twoFactorEnabled) {
                $this->loadRecoveryCodes();
            }
        }
    }

    public function updateProfile(): void
    {
        $this->profileForm->save();

        Flux::toast(variant: 'success', text: 'Profile updated.');
    }

    public function updatePassword(): void
    {
        try {
            $this->passwordForm->save();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->passwordForm->reset();
            throw $e;
        }

        Flux::toast(variant: 'success', text: 'Password updated.');
    }

    public function deleteAccount(): void
    {
        $this->deleteForm->delete(app(\App\Livewire\Actions\Logout::class));

        $this->redirect('/', navigate: true);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: 'A new verification link has been sent to your email address.');
    }

    public function enableTwoFactor(): void
    {
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return;
        }

        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication($user);

        $this->loadSetupData($user);
        $this->showQrCode = true;
    }

    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validateOnly('code');

        $confirmTwoFactorAuthentication(Auth::user(), $this->code);

        $this->twoFactorEnabled = true;
        $this->showQrCode = false;
        $this->code = '';

        Flux::toast(variant: 'success', text: 'Two-factor authentication enabled.');
    }

    public function cancelTwoFactorSetup(): void
    {
        app(DisableTwoFactorAuthentication::class)(Auth::user());

        $this->showQrCode = false;
        $this->qrCodeSvg = '';
        $this->manualSetupKey = '';
        $this->code = '';
    }

    public function disableTwoFactor(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->validate([
            'disablePassword' => ['required', 'string', 'current_password'],
        ]);

        $disableTwoFactorAuthentication(Auth::user());

        $this->twoFactorEnabled = false;
        $this->disablePassword = '';
        $this->recoveryCodes = [];

        Flux::toast(variant: 'success', text: 'Two-factor authentication disabled.');
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(Auth::user());

        $this->loadRecoveryCodes();
    }

    #[Computed]
    public function emailVerificationEnabled(): bool
    {
        return Auth::user() instanceof MustVerifyEmail;
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function emailVerifiedStatus(): string
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail) {
            return $user->hasVerifiedEmail()
                ? 'Verified on ' . $user->email_verified_at->format('M j, Y')
                : 'Not verified';
        }

        return '—';
    }

    #[Computed]
    public function registeredAt(): string
    {
        return Auth::user()->created_at->format('M j, Y');
    }

    #[Computed]
    public function activeSessionsCount(): int
    {
        return Auth::user()->activeSessionsCount();
    }

    #[Computed]
    public function twoFactorStatus(): string
    {
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_confirmed_at) {
            return 'Enabled on ' . $user->two_factor_confirmed_at->format('M j, Y');
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return 'Enabled';
        }

        return 'Disabled';
    }

    #[Computed]
    public function recoveryCodesRemaining(): int
    {
        $user = Auth::user();

        if (! $user->hasEnabledTwoFactorAuthentication() || ! $user->two_factor_recovery_codes) {
            return 0;
        }

        try {
            $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);

            return count($codes);
        } catch (\Throwable) {
            return 0;
        }
    }

    #[Computed]
    public function passwordRulesDescription(): array
    {
        return [
            'Minimum 8 characters',
            'at least one uppercase letter',
            'at least one lowercase letter',
            'at least one number',
        ];
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    #[Computed]
    public function requiresTwoFactorConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    #[Computed]
    public function teamCount(): int
    {
        return Auth::user()->teams()->count();
    }

    #[Computed]
    public function currentTeamName(): ?string
    {
        return Auth::user()->currentTeam?->name;
    }

    #[Computed]
    public function currentTeamSlug(): ?string
    {
        return Auth::user()->currentTeam?->slug;
    }

    private function loadSetupData($user): void
    {
        $user = $user->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new \Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (\Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');
            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    private function loadRecoveryCodes(): void
    {
        $user = Auth::user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (\Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes.');
                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<section>
    <flux:heading level="1">settings</flux:heading>

    <div class="flex flex-col md:flex-row gap-12">
        {{-- Main content --}}
        <div class="flex-1 min-w-0">
            <div class="mt-4">
                <flux:heading class="lowercase" level="2">Account</flux:heading>

                <form wire:submit="updateProfile" class="mt-4 space-y-5 max-w-xl">
                    <flux:field>
                        <flux:label class="lowercase">Name</flux:label>
                        <flux:input wire:model="profileForm.name" type="text" required autofocus autocomplete="name" />
                        <flux:error name="profileForm.name" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="lowercase">Email</flux:label>
                        <flux:input wire:model="profileForm.email" type="email" required autocomplete="email" />
                        <flux:error name="profileForm.email" />
                        <flux:description>Each account requires a unique email address.</flux:description>

                        @if ($this->hasUnverifiedEmail)
                            <flux:button wire:click="resendVerificationNotification" variant="subtle" class="mt-2">
                                Resend verification email
                            </flux:button>
                        @endif

                        @if ($this->emailVerificationEnabled && Auth::user()->hasVerifiedEmail() && $profileForm->email !== $profileForm->originalEmail)
                            <flux:text color="amber" class="mt-2">
                                Your email will be marked as unverified.
                            </flux:text>
                        @endif
                    </flux:field>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button type="submit" data-test="update-profile-button" class="lowercase">
                            Update profile
                        </flux:button>
                    </div>
                </form>
            </div>

            <div class="mt-5">
                <flux:heading class="lowercase" level="2">Password</flux:heading>

                <form wire:submit="updatePassword" class="mt-4 space-y-5 max-w-xl">
                    <flux:field>
                        <flux:label class="lowercase">Current password</flux:label>
                        <flux:input wire:model="passwordForm.current_password" type="password" required autocomplete="current-password" viewable />
                        <flux:error name="passwordForm.current_password" />
                    </flux:field>

                    <flux:field>
                        <flux:label class="lowercase">New password</flux:label>
                        <flux:input wire:model="passwordForm.password" type="password" required autocomplete="new-password" viewable />
                        <flux:error name="passwordForm.password" />
                        <flux:description>
                            {{ implode(', ', $this->passwordRulesDescription) . '.' }}
                        </flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label class="lowercase">Confirm password</flux:label>
                        <flux:input wire:model="passwordForm.password_confirmation" type="password" required autocomplete="new-password" viewable />
                        <flux:error name="passwordForm.password_confirmation" />
                    </flux:field>

                    <div class="flex">
                        <flux:spacer />
                        <flux:button type="submit" data-test="update-password-button" class="lowercase">
                            Update password
                        </flux:button>
                    </div>
                </form>
            </div>

            @if ($canManageTwoFactor)
            <div class="mt-5">
                <flux:heading class="lowercase" level="2">Two-factor authentication</flux:heading>

                @if ($twoFactorEnabled && ! $showQrCode)
                    <x-description.list class="mt-2">
                        <x-description.term class="lowercase">Status</x-description.term>
                        <x-description.details>{{ $this->twoFactorStatus }}</x-description.details>

                        <x-description.term class="lowercase">Recovery codes remaining</x-description.term>
                        <x-description.details>
                            <span class="{{ $this->recoveryCodesRemaining <= 2 ? 'text-amber-600' : '' }}">
                                {{ $this->recoveryCodesRemaining . ' of 8 codes' }}
                            </span>
                        </x-description.details>
                    </x-description.list>

                    <div class="mt-6 space-y-4">
                        @error('recoveryCodes')
                            <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                        @enderror

                        @if (filled($recoveryCodes))
                            <flux:text>Each code can only be used once.</flux:text>
                            <div class="grid grid-cols-2 gap-x-8 gap-y-1 font-mono text-sm" role="list" aria-label="Recovery codes">
                                @foreach($recoveryCodes as $recoveryCode)
                                    <div role="listitem" class="select-text" wire:loading.class="opacity-50 animate-pulse">
                                        {{ $recoveryCode }}
                                    </div>
                                @endforeach
                            </div>

                            <flux:button variant="danger" wire:click="regenerateRecoveryCodes" class="lowercase">
                                Regenerate codes
                            </flux:button>
                        @else
                            <flux:callout variant="warning" icon="exclamation-triangle" heading="No recovery codes">
                                No recovery codes were found.
                            </flux:callout>
                        @endif
                    </div>

                    <flux:heading class="lowercase mt-5" level="3">Disable two-factor authentication</flux:heading>

                    <flux:text class="mt-2">This will also delete your recovery codes.</flux:text>

                    <form wire:submit="disableTwoFactor" class="mt-4 space-y-5 max-w-xl">
                        <flux:field>
                            <flux:label class="lowercase">Password</flux:label>
                            <flux:input wire:model="disablePassword" type="password" required viewable />
                            <flux:error name="disablePassword" />
                        </flux:field>

                        <div class="flex">
                            <flux:spacer />
                            <flux:button variant="danger" type="submit" data-test="disable-two-factor-button">
                                Disable authenticator
                            </flux:button>
                        </div>
                    </form>
                @elseif ($showQrCode)
                    <div class="mt-4">
                        @error('setupData')
                            <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
                        @enderror

                        <flux:heading level="3" class="lowercase">
                            Step 1 — Add your account
                        </flux:heading>
                        <flux:text class="mt-1">Scan the QR code below, or enter the setup key manually in your authenticator app.</flux:text>

                        <div class="mt-6 space-y-8">
                            <div>
                                {!! $qrCodeSvg !!}
                            </div>

                            <flux:input
                                :value="$manualSetupKey"
                                readonly
                                variant="filled"
                                copyable
                                icon="key"
                                label="Manual setup key"
                                input:class="font-mono"
                            />
                        </div>

                        @if ($this->requiresTwoFactorConfirmation)
                            <flux:heading level="3" class="mt-8 lowercase">
                                Step 2 — Confirm setup
                            </flux:heading>
                            <flux:text class="mt-1">Enter the 6-digit code from your authenticator app to complete setup.</flux:text>

                            <div class="mt-6 space-y-8">
                                <flux:field>
                                    <flux:label class="lowercase">Authentication code</flux:label>
                                    <flux:otp name="code" wire:model="code" length="6" />
                                    <flux:error name="code" />
                                </flux:field>

                                <div class="flex gap-3">
                                    <flux:button wire:click="confirmTwoFactor" x-bind:disabled="$wire.code.length < 6" class="lowercase">
                                        Confirm
                                    </flux:button>
                                    <flux:button variant="subtle" wire:click="cancelTwoFactorSetup" type="button">
                                        Cancel
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <div class="mt-8 flex gap-3">
                                <flux:button wire:click="confirmTwoFactor" :disabled="$errors->has('setupData')" class="lowercase">
                                    Enable
                                </flux:button>
                                <flux:button variant="subtle" wire:click="cancelTwoFactorSetup" type="button">
                                    Cancel
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @else
                    <flux:text class="mt-2">Add an extra layer of security to your account by enabling two-factor authentication.</flux:text>

                    <div class="mt-4">
                        <flux:button wire:click="enableTwoFactor" class="lowercase">
                            Enable authenticator
                        </flux:button>
                    </div>
                @endif
            </div>
            @endif

            <div class="mt-5">
                <flux:heading class="lowercase" level="2" color="red">Delete account</flux:heading>

                @if ($this->showDeleteUser)
                    <flux:text class="mt-2">Once you delete your account, all of its resources and data will be permanently deleted.</flux:text>

                    <form wire:submit="deleteAccount" class="mt-4 space-y-5 max-w-xl">
                        <flux:field>
                            <flux:label class="lowercase">Password</flux:label>
                            <flux:input wire:model="deleteForm.password" type="password" required viewable />
                            <flux:error name="deleteForm.password" />
                        </flux:field>

                        <div class="flex">
                            <flux:spacer />
                            <flux:button variant="danger" type="submit" data-test="delete-user-button">
                                Delete account
                            </flux:button>
                        </div>
                    </form>
                @else
                    <flux:text color="amber" class="mt-2">
                        Email verification required to delete your account.
                    </flux:text>
                @endif
            </div>
        </div>

        {{-- Right sidebar --}}
        <aside class="md:w-72 md:shrink-0 md:sticky md:top-24 md:self-start space-y-8">
            <div>
                <flux:heading class="lowercase" level="2">Account details</flux:heading>

                <x-description.list class="mt-2">
                    @if ($this->emailVerificationEnabled)
                        <x-description.term class="lowercase">Email status</x-description.term>
                        <x-description.details>{{ $this->emailVerifiedStatus }}</x-description.details>
                    @endif

                    <x-description.term class="lowercase">Registered</x-description.term>
                    <x-description.details>{{ $this->registeredAt }}</x-description.details>

                    <x-description.term class="lowercase">Active sessions</x-description.term>
                    <x-description.details>{{ $this->activeSessionsCount }} active</x-description.details>

                    <x-description.term class="lowercase">Teams</x-description.term>
                    <x-description.details>
                        <flux:link :accent="false" :href="route('teams.switch')" wire:navigate>
                            {{ $this->teamCount }} {{ str()->plural('team', $this->teamCount) }}
                        </flux:link>
                    </x-description.details>

                    @if ($this->currentTeamName)
                        <x-description.term class="lowercase">Current team</x-description.term>
                        <x-description.details>
                            <flux:link :accent="false" :href="route('teams.show', $this->currentTeamSlug)" wire:navigate>
                                {{ $this->currentTeamName }}
                            </flux:link>
                        </x-description.details>
                    @endif
                </x-description.list>
            </div>

            <div x-data>
                <flux:heading class="lowercase" level="2">Appearance</flux:heading>

                <flux:field class="mt-2">
                    <flux:label class="lowercase">Theme</flux:label>
                    <flux:description>System follows the operating system preference.</flux:description>
                    <flux:radio.group variant="segmented" x-model="$flux.appearance" class="mt-2">
                        <flux:radio value="light" icon="sun">Light</flux:radio>
                        <flux:radio value="dark" icon="moon">Dark</flux:radio>
                        <flux:radio value="system" icon="computer-desktop">System</flux:radio>
                    </flux:radio.group>
                </flux:field>

                <x-description.list class="mt-2">
                    <x-description.term class="lowercase">System preference</x-description.term>
                    <x-description.details>
                        <span x-text="window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light'"></span>
                    </x-description.details>

                    <x-description.term class="lowercase">Resolved</x-description.term>
                    <x-description.details>
                        <span x-text="document.documentElement.classList.contains('dark') ? 'Dark' : 'Light'"></span>
                    </x-description.details>
                </x-description.list>
            </div>
        </aside>
    </div>
</section>
