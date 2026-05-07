<x-layouts::guest :title="__('Log in')">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">{{ __('Log in to your account') }}</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-4 space-y-5">
                @csrf

                <flux:field>
                    <flux:label>{{ __('Email address') }}</flux:label>
                    <flux:input
                        name="email"
                        :value="old('email')"
                        type="email"
                        size="sm"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="max-w-lg"
                    />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Password') }}</flux:label>
                    <flux:input
                        name="password"
                        type="password"
                        size="sm"
                        required
                        autocomplete="current-password"
                        viewable
                        class="max-w-lg"
                    />
                    <flux:error name="password" />
                    @if (Route::has('password.request'))
                        <p class="text-sm mt-2">
                            <flux:link :href="route('password.request')" wire:navigate>{{ __('Forgot your password?') }}</flux:link>
                        </p>
                    @endif
                </flux:field>

                <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

                <flux:button variant="primary" type="submit" size="sm" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </form>

        </div>
    </section>
</x-layouts::guest>
