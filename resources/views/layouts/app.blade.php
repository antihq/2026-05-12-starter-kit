<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white text-base/6 sm:text-sm/6">
        <header>
            <nav class="flex items-end flex-wrap py-5">
                <div class="lg:w-64 lg:justify-end px-4 flex gap-x-1 flex-wrap">
                    <a href="{{ route('home') }}" class="text-zinc-500 dark:text-zinc-400" wire:navigate>
                        {{ Str::of(config('app.name'))->explode('-', 4)->last() }}
                        <sup>{{ Str::of(config('app.name'))->explode('-', 4)->take(3)->join('-') }}</sup>
                    </a>
                    <span class="text-zinc-500 dark:text-zinc-400">(<a href="{{ route('dashboard') }}" class="text-sky-700 visited:text-purple-700 hover:underline" wire:navigate>{{ Auth::user()->currentTeam->name }}</a>)</span>
                </div>

                <div class="flex-1 flex-wrap flex px-4">
                    <div class="flex gap-x-3">
                        <a href="{{ route('dashboard') }}" class="text-base/6 sm:text-sm/6 hover:underline text-sky-700 visited:text-purple-700 lowercase" wire:navigate>dashboard</a>
                        <a href="{{ route('teams.show', Auth::user()->currentTeam->slug) }}" class="text-base/6 sm:text-sm/6 hover:underline text-sky-700 visited:text-purple-700 lowercase" wire:navigate>team settings</a>
                        <a href="{{ route('settings.show') }}" class="text-base/6 sm:text-sm/6 hover:underline text-sky-700 visited:text-purple-700 lowercase" wire:navigate>profile</a>
                        <a href="{{ route('teams.index') }}" class="text-base/6 sm:text-sm/6 hover:underline text-sky-700 visited:text-purple-700 lowercase" wire:navigate>teams</a>
                    </div>

                    <div aria-hidden="true" class="flex-1"></div>

                    <div class="flex gap-x-1.5">
                        logged in as {{ Auth::user()->email }}
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <flux:badge as="button" type="submit" class="lowercase">logout</flux:badge>
                        </form>
                    </div>
                </div>
            </nav>
        </header>

        <main class="lg:pl-64">
            <div class="p-4 pt-0">
                <div class="w-full max-w-6xl">
                    {{ $slot }}
                </div>
            </div>
        </main>

        @persist('toast')
            <flux:toast.group position="bottom center">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
