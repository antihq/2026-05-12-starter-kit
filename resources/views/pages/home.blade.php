<x-layouts::guest title="Welcome">
    <div class="grid gap-12 lg:grid-cols-[1fr_380px]">
        <div>
            <flux:heading size="xl" level="1">{{ config('app.name', 'Laravel Teams Starter Kit') }}</flux:heading>
            <p class="mt-2 text-sm">
                Laravel starter kit with authentication, multi-team management, role-based permissions, and user settings. Built on Livewire, Flux Pro, and Tailwind CSS.
            </p>

            <flux:heading class="mt-5">Authentication</flux:heading>
            <flux:separator class="mt-2" />
            <x-description.list>
                <x-description.term>Password reset</x-description.term>
                <x-description.details>Reset link sent by email</x-description.details>
             <x-description.term>Email verification</x-description.term>
                <x-description.details>Verification link with resend</x-description.details>
             <x-description.term>Two-factor auth</x-description.term>
                <x-description.details>Time-based one-time passwords via authenticator app, setup by QR code, 8 single-use recovery codes</x-description.details>
            </x-description.list>

            <flux:heading class="mt-2">Team Management</flux:heading>
            <flux:separator class="mt-2" />
            <x-description.list>
                <x-description.term>Multi-team</x-description.term>
                <x-description.details>Multiple teams per user, personal team created on registration</x-description.details>
             <x-description.term>Roles</x-description.term>
                <x-description.details>Owner, Admin, Member — each with a defined set of permissions</x-description.details>
             <x-description.term>Team switching</x-description.term>
                <x-description.details>Nav bar dropdown, updates URL to new team context</x-description.details>
             <x-description.term>Invitations</x-description.term>
                <x-description.details>Sent by email with role assignment, expire after 3 days</x-description.details>
             <x-description.term>Member management</x-description.term>
                <x-description.details>Add, change role, or remove members</x-description.details>
             <x-description.term>Team deletion</x-description.term>
                <x-description.details>Requires typing the team name; personal teams cannot be deleted</x-description.details>
            </x-description.list>

            <flux:heading class="mt-2">Settings</flux:heading>
            <flux:separator class="mt-2" />
            <x-description.list>
                <x-description.term>Account</x-description.term>
                <x-description.details>Update name and email, re-verify on email change, delete account with password confirmation</x-description.details>
             <x-description.term>Appearance</x-description.term>
                <x-description.details>Light, dark, or match system preference — saved per user</x-description.details>
             <x-description.term>Security</x-description.term>
                <x-description.details>Change password, enable and disable 2FA, view and regenerate recovery codes</x-description.details>
            </x-description.list>

            <flux:heading class="mt-2">Technology</flux:heading>
            <flux:separator class="mt-2" />
            <x-description.list>
                <x-description.term>Stack</x-description.term>
                <x-description.details>Laravel 13, Livewire 4, Flux Pro, Tailwind CSS 4, Vite 8</x-description.details>
            </x-description.list>

            <div class="mt-10">
                <flux:button variant="primary" :href="route('register')" size="sm" wire:navigate>Create account</flux:button>
            </div>
        </div>

        <div>
            <flux:heading level="2">Sign in</flux:heading>

            <form method="POST" action="{{ route('login.store') }}" class="mt-4 space-y-5">
                @csrf

                <flux:field>
                    <flux:label>Email address</flux:label>
                    <flux:input
                        name="email"
                        :value="old('email')"
                        type="email"
                        size="sm"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Password</flux:label>
                    <flux:input
                        name="password"
                        type="password"
                        size="sm"
                        required
                        autocomplete="current-password"
                        viewable
                    />
                    <flux:error name="password" />
                    @if (Route::has('password.request'))
                        <p class="text-sm mt-2">
                            <flux:link :href="route('password.request')" wire:navigate>Forgot your password?</flux:link>
                        </p>
                    @endif
                </flux:field>

                <flux:checkbox name="remember" label="Remember me" :checked="old('remember')" />

                <flux:button variant="primary" type="submit" size="sm" data-test="login-button">
                    Sign in
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::guest>
