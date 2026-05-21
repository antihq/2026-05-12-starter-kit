<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white text-base/6 sm:text-sm/6">
        <header class="px-4 [grid-area:header] max-w-6xl mx-auto w-full">
            <nav class="flex flex-wrap items-center gap-x-4 py-5">
                <div class="text-base/6 sm:text-sm/6 text-zinc-500">
                    <a href="{{ route('home') }}" wire:navigate>
                        {{ Str::of(config('app.name'))->explode('-', 4)->last() }}
                        <sup>{{ Str::of(config('app.name'))->explode('-', 4)->take(3)->join('-') }}</sup>
                    </a>
                    (<a href="{{ route('dashboard') }}" class="text-blue-600 visited:text-purple-600 hover:underline" wire:navigate>{{ Auth::user()->currentTeam->name }}</a>)
                </div>

                <div class="flex gap-x-3">
                    <a href="{{ route('dashboard') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>dashboard</a>
                    <a href="{{ route('teams.show', Auth::user()->currentTeam->slug) }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>team</a>
                    <a href="{{ route('settings.show') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>settings</a>
                </div>

                <div aria-hidden="true" class="-ml-4 flex-1"></div>
                <div class="flex gap-x-3">
                    <span class="text-base/6 sm:text-sm/6">logged in as {{ Auth::user()->email }}
                        <span class="text-zinc-500">[</span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-600 active:bg-yellow-100 lowercase">logout</button>
                        </form>
                        <span class="text-zinc-500">]</span>
                    </span>
                </div>
            </nav>
        </header>

        <main class="p-4 pt-0 w-full max-w-6xl mx-auto">
            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group position="bottom center">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
