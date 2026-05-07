<x-layouts::guest :title="__('Forgot password')">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">{{ __('Forgot password') }}</flux:heading>
            <p class="mt-2 text-sm max-w-prose">{{ __('A password reset link will be sent to your email address.') }}</p>

            @if (session('status'))
                <flux:text color="green" class="mt-4 font-medium">{{ session('status') }}</flux:text>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-4 space-y-5">
                @csrf

                <flux:field>
                    <flux:label>{{ __('Email address') }}</flux:label>
                    <flux:input
                        name="email"
                        type="email"
                        size="sm"
                        required
                        autofocus
                        placeholder="email@example.com"
                        class="max-w-lg"
                    />
                    <flux:error name="email" />
                </flux:field>

                <flux:button variant="primary" type="submit" size="sm" data-test="email-password-reset-link-button">
                    {{ __('Email password reset link') }}
                </flux:button>
            </form>

            <flux:button class="mt-10" icon="arrow-left" :href="route('login')" wire:navigate size="sm">
                {{ __('Back to log in') }}
            </flux:button>
        </div>
    </section>
</x-layouts::guest>
