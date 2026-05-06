@blaze(fold: true)

@props([
    'container' => null,
])

@php
$classes = Flux::classes('[grid-area:main]')
    ->add('p-4')
    ->add($container ? 'mx-auto w-full [:where(&)]:max-w-6xl' : '')
    ;
@endphp

<div {{ $attributes->class($classes) }} data-flux-main>
    {{ $slot }}
</div>
