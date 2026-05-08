<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white">
        <flux:header class="overflow-x-auto overflow-y-hidden" container>
            <flux:navbar class="-ml-2.5">
                <flux:navbar.item :href="route('home')" wire:navigate>
                    {{ config('app.name', 'Laravel') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="-mr-2.5">
                @guest
                    @if (Route::has('login'))
                        <flux:navbar.item :href="route('login')" wire:navigate>
                            Sign in
                        </flux:navbar.item>
                    @endif

                    @if (Route::has('register'))
                        <flux:navbar.item :href="route('register')" wire:navigate>
                            Create account
                        </flux:navbar.item>
                    @endif
                @endguest

                @auth
                    <flux:navbar.item :href="route('dashboard')" wire:navigate>
                        Dashboard
                    </flux:navbar.item>
                @endauth
            </flux:navbar>
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
