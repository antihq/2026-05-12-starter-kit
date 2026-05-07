<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white">
        <flux:header>
            <flux:navbar class="-ml-2.5" scrollable>
                <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('profile.edit')" wire:navigate>
                    {{ __('Profile') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('security.edit')" wire:navigate>
                    {{ __('Security') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.index')" :current="request()->routeIs('teams.*')" wire:navigate>
                    {{ __('Teams') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('appearance.edit')" wire:navigate>
                    {{ __('Appearance') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <div class="flex items-center gap-2">
                <livewire:team-switcher />

                <div>
                    <flux:separator orientation="vertical" class="h-4 ml-2" />
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:navbar.item type="submit">
                        {{ __('Log out') }}
                    </flux:navbar.item>
                </form>
            </div>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
