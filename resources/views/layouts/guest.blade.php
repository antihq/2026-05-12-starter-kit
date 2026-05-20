<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="bg-white dark:bg-zinc-900 antialiased text-zinc-950 dark:text-white">
        <header class="px-6 lg:px-10 [grid-area:header] max-w-6xl mx-auto w-full">
            <nav class="flex flex-wrap items-center gap-x-4 py-6 lg:py-10 gap-y-2 border-b border-zinc-950/5 dark:border-white/10">
                <div>
                    <a href="{{ route('home') }}" class="text-base/6 sm:text-sm/6 text-zinc-500" wire:navigate>
                        {{ Str::of(config('app.name'))->explode('-', 4)->last() }}
                        <sup>{{ Str::of(config('app.name'))->explode('-', 4)->take(3)->join('-') }}</sup>
                    </a>
                </div>
                <div aria-hidden="true" class="-ml-4 flex-1"></div>
                <div class="flex gap-x-3">
                    @guest
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>Sign in</a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="text-base/6 sm:text-sm/6 hover:underline text-blue-600 visited:text-purple-600 lowercase" wire:navigate>Create account</a>
                        @endif
                    @endguest

                    @auth
                        <span class="text-base/6 sm:text-sm/6">logged in as {{ Auth::user()->email }}
                            <span class="text-zinc-500">[</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-600 active:bg-yellow-100">logout</button>
                            </form>
                            <span class="text-zinc-500">]</span>
                        </span>
                    @endauth
                </div>
            </nav>
        </header>

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
