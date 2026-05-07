<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Appearance')] class extends Component
{
    //
}; ?>

<section class="w-full" x-data>
    <flux:heading size="xl" level="1">{{ __('Appearance') }}</flux:heading>
    <flux:separator class="mt-2" />
    <x-description.list>
        <x-description.term>{{ __('Selected theme') }}</x-description.term>
        <x-description.details>
            <span x-text="{ light: 'Light', dark: 'Dark', system: 'System' }[$flux.appearance]"></span>
        </x-description.details>

        <x-description.term>{{ __('System preference') }}</x-description.term>
        <x-description.details>
            <span x-text="window.matchMedia('(prefers-color-scheme: dark)').matches ? 'Dark' : 'Light'"></span>
        </x-description.details>

        <x-description.term>{{ __('Resolved appearance') }}</x-description.term>
        <x-description.details>
            <span x-text="document.documentElement.classList.contains('dark') ? 'Dark' : 'Light'"></span>
        </x-description.details>
    </x-description.list>

    <flux:heading class="mt-10">{{ __('Theme') }}</flux:heading>
    <div class="mt-4 space-y-5">
        <flux:field>
            <flux:label>{{ __('Theme') }}</flux:label>
            <flux:description>{{ __('Select how the interface is rendered. System follows the operating system preference.') }}</flux:description>
            <flux:radio.group variant="segmented" x-model="$flux.appearance" class="mt-2 max-w-lg">
                <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
            </flux:radio.group>
        </flux:field>
    </div>
</section>
