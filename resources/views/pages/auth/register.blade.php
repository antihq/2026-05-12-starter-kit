<x-layouts::guest title="Create account">
    <section class="w-full">
        <div class="max-w-md">
            <flux:heading size="xl" level="1">Create an account</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-6">
                @csrf

                <flux:field>
                    <flux:label class="lowercase">Name</flux:label>
                    <flux:input
                        name="name"
                        :value="old('name')"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label class="lowercase">Email address</flux:label>
                    <flux:input
                        name="email"
                        :value="old('email')"
                        type="email"
                        required
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
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password" />
                    <flux:description>Minimum 8 characters, at least one uppercase letter, at least one lowercase letter, at least one number.</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label class="lowercase">Confirm password</flux:label>
                    <flux:input
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password_confirmation" />
                </flux:field>

                <div class="flex">
                    <flux:spacer />
                    <flux:button type="submit" variant="primary" data-test="register-user-button" class="lowercase">
                        Create account
                    </flux:button>
                </div>
            </form>

        </div>
    </section>
</x-layouts::guest>
