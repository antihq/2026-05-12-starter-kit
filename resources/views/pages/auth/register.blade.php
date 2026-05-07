<x-layouts::guest :title="__('Register')">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">{{ __('Create an account') }}</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-4 space-y-5">
                @csrf

                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input
                        name="name"
                        :value="old('name')"
                        type="text"
                        size="sm"
                        required
                        autofocus
                        autocomplete="name"
                        :placeholder="__('Full name')"
                        class="max-w-lg"
                    />
                    <flux:error name="name" />
                    <flux:description>{{ __('255 characters maximum.') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Email address') }}</flux:label>
                    <flux:input
                        name="email"
                        :value="old('email')"
                        type="email"
                        size="sm"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="max-w-lg"
                    />
                    <flux:error name="email" />
                    <flux:description>{{ __('Must be unique across all accounts.') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Password') }}</flux:label>
                    <flux:input
                        name="password"
                        type="password"
                        size="sm"
                        required
                        autocomplete="new-password"
                        viewable
                        class="max-w-lg"
                    />
                    <flux:error name="password" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Confirm password') }}</flux:label>
                    <flux:input
                        name="password_confirmation"
                        type="password"
                        size="sm"
                        required
                        autocomplete="new-password"
                        viewable
                        class="max-w-lg"
                    />
                    <flux:error name="password_confirmation" />
                    <flux:description>{{ __('Must match the password above.') }}</flux:description>
                </flux:field>

                <flux:button type="submit" variant="primary" size="sm" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </form>

        </div>
    </section>
</x-layouts::guest>
