<x-layouts::guest title="Welcome">
    <div class="grid lg:grid-cols-[1fr_320px] gap-12 lg:gap-16">
        <div>
            <flux:heading level="1" size="xl">Anti Starter Kit</flux:heading>

            <p class="mt-4 text-base/6 sm:text-sm/6 max-w-prose">
                An opinionated starter kit made for the <a href="https://github.com/antihq" class="hover:underline text-blue-600 visited:text-purple-600">AntiHQ Laravel projects</a>. It ships with authentication, teams, and a set of conventions to get you building faster.
            </p>
        </div>

        @guest
            <div>
                <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
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
                            placeholder="email@example.com"
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
                                <a href="{{ route('password.request') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>Forgot your password?</a>
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
        @endguest
    </div>
</x-layouts::guest>
