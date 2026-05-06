<?php

use App\Actions\Teams\CreateTeam;
use App\Rules\TeamName;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Team')] class extends Component {
    public string $name = '';

    public function createTeam(CreateTeam $createTeam): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', new TeamName],
        ]);

        $team = $createTeam->handle(Auth::user(), $validated['name']);

        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Team created.'));

        $this->redirectRoute('teams.edit', ['team' => $team->slug], navigate: true);
    }
}; ?>

<section class="w-full">
    <flux:button variant="ghost" icon="arrow-left" :href="route('teams.index')" wire:navigate data-test="create-team-back">
        {{ __('Back to teams') }}
    </flux:button>

    <div class="mt-6 max-w-lg">
        <flux:heading size="xl">{{ __('Create a new team') }}</flux:heading>
        <flux:subheading>{{ __('Give your team a name to get started.') }}</flux:subheading>

        <form wire:submit="createTeam" class="mt-6 space-y-6">
            <flux:input wire:model="name" :label="__('Team name')" type="text" required autofocus data-test="create-team-name" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:button variant="ghost" :href="route('teams.index')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>

                <flux:button variant="primary" type="submit" data-test="create-team-submit">
                    {{ __('Create team') }}
                </flux:button>
            </div>
        </form>
    </div>
</section>
