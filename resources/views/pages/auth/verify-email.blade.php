<x-layouts::guest :title="__('Email verification')">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">{{ __('Verify your email') }}</flux:heading>
            <p class="mt-2 text-sm max-w-prose">{{ __('A verification link has been sent to your email. Click it to complete registration.') }}</p>

            @if (session('status') == 'verification-link-sent')
                <flux:text color="green" class="mt-4 font-medium">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </flux:text>
            @endif

            <div class="mt-4 space-y-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <flux:button type="submit" variant="primary" size="sm">
                        {{ __('Resend verification email') }}
                    </flux:button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button variant="ghost" type="submit" size="sm" class="cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:button>
                </form>
            </div>
        </div>
    </section>
</x-layouts::guest>
