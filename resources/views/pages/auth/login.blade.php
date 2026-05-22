<x-layouts::auth title="Sign in">
    <section class="w-full">
        <div class="max-w-md">
            <flux:heading size="xl" level="1">Sign in to your account</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-6">
                @csrf

                <flux:field>
                    <flux:label class="lowercase">Email address</flux:label>
                    <flux:input
                        name="email"
                        :value="old('email')"
                        type="email"
                        required
                        autofocus
                        autocomplete="email"
                    />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label class="lowercase">Password</flux:label>
                    <flux:input
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        viewable
                    />
                    <flux:error name="password" />
                    @if (Route::has('password.request'))
                        <p class="mt-3">
                            <a href="{{ route('password.request') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>Reset password</a>
                        </p>
                    @endif
                </flux:field>

                <flux:checkbox name="remember" label="Remember me" :checked="old('remember')" />

                <div class="flex">
                    <flux:spacer />
                    <flux:button variant="primary" type="submit" data-test="login-button" class="lowercase">
                        Sign in
                    </flux:button>
                </div>
            </form>

        </div>
    </section>
</x-layouts::auth>
