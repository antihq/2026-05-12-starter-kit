<x-layouts::auth title="Confirm password">
    <section class="w-full">
        <div class="max-w-md">
            <flux:heading size="xl" level="1">Confirm password</flux:heading>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('password.confirm.store') }}" class="mt-4 space-y-6">
                @csrf

                <flux:field>
                    <flux:label class="lowercase">Password</flux:label>
                    <flux:input
                        name="password"
                        type="password"
                        required
                        autocomplete="current-password"
                        viewable
                        autofocus
                    />
                    <flux:error name="password" />
                </flux:field>

                <div class="flex">
                    <flux:spacer />
                    <flux:button variant="primary" type="submit" data-test="confirm-password-button" class="lowercase">
                        Confirm
                    </flux:button>
                </div>
            </form>
        </div>
    </section>
</x-layouts::auth>
