<x-layouts::guest title="Create account">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">Create an account</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-4 space-y-5">
                @csrf

                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input
                        name="name"
                        :value="old('name')"
                        type="text"
                        size="sm"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Full name"
                    />
                    <flux:error name="name" />
                    <flux:description>255 characters maximum.</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>Email address</flux:label>
                    <flux:input
                        name="email"
                        :value="old('email')"
                        type="email"
                        size="sm"
                        required
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <flux:error name="email" />
                    <flux:description>Must be unique across all accounts.</flux:description>
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
                    <flux:description>Must match the password above.</flux:description>
                </flux:field>

                <flux:button type="submit" variant="primary" size="sm" data-test="register-user-button">
                    Create account
                </flux:button>
            </form>

        </div>
    </section>
</x-layouts::guest>
