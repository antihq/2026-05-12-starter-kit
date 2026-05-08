<x-layouts::guest title="Reset password">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">Reset password</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="mt-4 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ request()->route('token') }}">

                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input
                        name="email"
                        value="{{ request('email') }}"
                        type="email"
                        size="sm"
                        required
                        autocomplete="email"
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
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password" />
                </flux:field>

                <flux:field>
                    <flux:label>Confirm password</flux:label>
                    <flux:input
                        name="password_confirmation"
                        type="password"
                        size="sm"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password_confirmation" />
                    <flux:description>Must match the new password above.</flux:description>
                </flux:field>

                <flux:button type="submit" variant="primary" size="sm" data-test="reset-password-button">
                    Reset password
                </flux:button>
            </form>

            <flux:button class="mt-10" icon="arrow-left" :href="route('login')" wire:navigate size="sm">
                Back to sign in
            </flux:button>
        </div>
    </section>
</x-layouts::guest>
