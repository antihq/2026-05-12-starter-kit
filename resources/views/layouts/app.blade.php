<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white">
        <flux:header class="overflow-x-auto overflow-y-hidden" container>
            <flux:navbar class="-ml-2.5">
                <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    Dashboard
                </flux:navbar.item>
                <flux:navbar.item :href="route('account.edit')" wire:navigate>
                    Account
                </flux:navbar.item>
                <flux:navbar.item :href="route('security.edit')" wire:navigate>
                    Security
                </flux:navbar.item>
                <flux:navbar.item :href="route('teams.index')" :current="request()->routeIs('teams.*')" wire:navigate>
                    Teams
                </flux:navbar.item>
                <flux:navbar.item :href="route('appearance.edit')" wire:navigate>
                    Appearance
                </flux:navbar.item>
            </flux:navbar>

            <div class="sm:hidden">
                <flux:separator orientation="vertical" class="h-4 ml-2 mr-4" />
            </div>

            <flux:spacer class="max-sm:hidden" />

            <div class="flex items-center gap-2 max-sm:pr-4">
                <livewire:team-switcher />

                <div>
                    <flux:separator orientation="vertical" class="h-4 ml-2" />
                </div>

                <form method="POST" action="{{ route('logout') }}" class="-mr-2.5 ">
                    @csrf
                    <flux:navbar.item type="submit">
                        Sign out
                    </flux:navbar.item>
                </form>
            </div>
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
