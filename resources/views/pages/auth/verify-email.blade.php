<x-layouts::guest title="Email verification">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <flux:heading size="xl" level="1">Verify your email</flux:heading>
            <flux:text class="mt-1 max-w-prose">A verification link has been sent to <strong>{{ auth()->user()->email }}</strong>. Click it to complete registration.</flux:text>

            @if (session('status') == 'verification-link-sent')
                <flux:text color="green" class="mt-4 font-medium">
                    A new link has been sent to <strong>{{ auth()->user()->email }}</strong>.
                </flux:text>
            @endif

            <div class="mt-4 space-y-3">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <flux:button type="submit" variant="primary">
                        Resend verification email
                    </flux:button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button variant="ghost" type="submit" class="cursor-pointer" data-test="logout-button">
                        Sign out
                    </flux:button>
                </form>
            </div>
        </div>
    </section>
</x-layouts::guest>
