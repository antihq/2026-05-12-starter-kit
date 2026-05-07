<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Livewire\Actions\Logout;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Account')] class extends Component
{
    use PasswordValidationRules;
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $originalEmail = '';

    public string $password = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->originalEmail = $user->email;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->originalEmail = $this->email;

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Flux::toast(text: __('A new verification link has been sent to your email address.'));
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function emailChanged(): bool
    {
        return $this->email !== $this->originalEmail;
    }

    #[Computed]
    public function emailVerifiedStatus(): string
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return __('Verified :date', ['date' => $user->email_verified_at->format('M j, Y')]);
        }

        return __('Not verified');
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    #[Computed]
    public function activeSessionsCount(): int
    {
        return Auth::user()->activeSessionsCount();
    }

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Account') }}</flux:heading>
    <flux:separator class="mt-2" />
    <x-description.list>
        <x-description.term>{{ __('Email') }}</x-description.term>
        <x-description.details>{{ $this->emailVerifiedStatus }}</x-description.details>

        <x-description.term>{{ __('Active sessions') }}</x-description.term>
        <x-description.details>{{ $this->activeSessionsCount }}</x-description.details>
    </x-description.list>

    <flux:heading class="mt-10">{{ __('Update profile') }}</flux:heading>
    <form wire:submit="updateProfileInformation" class="mt-4 space-y-5">
        <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
            <flux:input wire:model="name" type="text" size="sm" required autofocus autocomplete="name" class="max-w-lg" />
            <flux:error name="name" />
            <flux:description>{{ __('255 characters maximum.') }}</flux:description>
        </flux:field>

        <flux:field>
                    <flux:label>{{ __('Email') }}</flux:label>
            <flux:input wire:model="email" type="email" size="sm" required autocomplete="email" class="max-w-lg" />
            <flux:error name="email" />
            <flux:description>{{ __('Must be unique across all accounts.') }}</flux:description>

            @if ($this->emailChanged && Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail())
                <p class="text-sm text-amber-600 mt-2">
                    Changing your email address will require re-verification.
                </p>
            @endif

            @if ($this->hasUnverifiedEmail)
                <flux:text class="mt-2">
                    {{ __('Your email address is unverified.') }}
                    <flux:link class="cursor-pointer" wire:click.prevent="resendVerificationNotification">
                        {{ __('Resend verification email.') }}
                    </flux:link>
                </flux:text>
            @endif
        </flux:field>

        <flux:button size="sm" variant="primary" type="submit" data-test="update-profile-button">
            {{ __('Save') }}
        </flux:button>
    </form>

    @if ($this->showDeleteUser)
        <flux:heading class="mt-10">{{ __('Delete account') }}</flux:heading>

        <form wire:submit="deleteUser" class="mt-4 space-y-5">
            <flux:field>
                    <flux:label>{{ __('Confirm password') }}</flux:label>
                <flux:input wire:model="password" type="password" size="sm" required viewable class="max-w-lg" />
                <flux:error name="password" />
            </flux:field>

            <flux:button size="sm" variant="danger" type="submit" data-test="delete-user-button">
                {{ __('Delete account') }}
            </flux:button>
        </form>
    @endif
</section>
