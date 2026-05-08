<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamPermission;
use App\Rules\TeamName;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Team')] class extends Component {
    public string $name = '';

    #[Computed]
    public function slugPreview(): string
    {
        return $this->name !== '' ? Str::slug($this->name) : '';
    }

    #[Computed]
    public function nameLength(): int
    {
        return strlen($this->name);
    }

    #[Computed]
    public function ownerPermissions(): array
    {
        return collect(TeamPermission::cases())
            ->map(fn ($p) => $p->value)
            ->values()
            ->all();
    }

    public function createTeam(CreateTeam $createTeam): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', new TeamName],
        ]);

        $team = $createTeam->handle(Auth::user(), $validated['name']);

        $this->reset('name');

        Flux::toast(variant: 'success', text: 'Team created.');

        $this->redirectRoute('teams.edit', ['team' => $team->slug], navigate: true);
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">Create a new team</flux:heading>
        <p class="mt-2 text-sm max-w-prose">
            Teams are shared workspaces with role-based permissions. Each team has its own members, invitations, and settings.
        </p>

        <form wire:submit="createTeam" class="mt-6 space-y-5">
            <div class="max-w-md">
                <flux:input wire:model.live.debounce.300ms="name" label="Team name" type="text" size="sm" required autofocus data-test="create-team-name" />

                <div class="mt-2 flex items-center justify-between">
                    @if($this->name !== '')
                        <p class="text-sm mono">
                            {{ $this->slugPreview }}
                        </p>
                    @else
                        <p class="text-sm">
                            Slug preview will appear here
                        </p>
                    @endif

                    <p class="tabular-nums text-sm {{ $this->nameLength > 255 ? 'text-red-500' : '' }}">
                        {{ $this->nameLength }}/255
                    </p>
                </div>
            </div>

            <flux:button variant="primary" type="submit" data-test="create-team-submit" size="sm">
                Create team
            </flux:button>
        </form>

        <flux:heading class="mt-10" level="2">
            What happens on creation
        </flux:heading>

        <flux:separator class="mt-2" />

        <x-description.list>
            <x-description.term>Role assigned</x-description.term>
            <x-description.details>
                You are assigned the Owner role with all permissions:
                @foreach($this->ownerPermissions as $permission)
                    <x-code>{{ $permission }}</x-code>{{ $loop->last ? '' : ',' }}
                @endforeach
            </x-description.details>

            <x-description.term>Active team</x-description.term>
            <x-description.details>This team becomes your active team across the application.</x-description.details>
        </x-description.list>

        <flux:button class="mt-6" icon="arrow-left" :href="route('teams.index')" wire:navigate data-test="create-team-back" size="sm">
            Back to teams
        </flux:button>
    </div>
</section>
