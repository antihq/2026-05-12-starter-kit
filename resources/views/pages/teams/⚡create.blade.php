<?php

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamPermission;
use App\Rules\TeamName;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Team')] class extends Component {
    public string $name = '';

    public array $existingTeams = [];

    public function mount(): void
    {
        $this->existingTeams = Auth::user()
            ->teams()
            ->withCount('members')
            ->get()
            ->map(fn ($team) => [
                'name' => $team->name,
                'role' => Auth::user()->teamRole($team)?->label(),
                'is_personal' => $team->is_personal,
                'member_count' => $team->members_count,
            ])
            ->values()
            ->all();
    }

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

        Flux::toast(variant: 'success', text: __('Team created.'));

        $this->redirectRoute('teams.edit', ['team' => $team->slug], navigate: true);
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">{{ __('Create a new team') }}</flux:heading>
        <p class="mt-1 text-sm max-w-prose">
            {{ __('Teams are shared workspaces with role-based permissions. Each team has its own members, invitations, and settings.') }}
        </p>

        @if(count($existingTeams) > 0)
            <flux:heading class="mt-6" level="2">{{ __('Your teams') }}</flux:heading>

            <flux:table class="mt-2">
                <flux:table.columns>
                    <flux:table.column>{{ __('Team') }}</flux:table.column>
                    <flux:table.column>{{ __('Type') }}</flux:table.column>
                    <flux:table.column>{{ __('Members') }}</flux:table.column>
                    <flux:table.column>{{ __('Your Role') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach($existingTeams as $team)
                        <flux:table.row>
                            <flux:table.cell variant="strong">{{ $team['name'] }}</flux:table.cell>
                            <flux:table.cell>
                                @if($team['is_personal'])
                                    <flux:badge color="zinc" size="sm" inset="top bottom">{{ __('Personal') }}</flux:badge>
                                @else
                                    {{ __('Team') }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell class="tabular-nums">{{ $team['member_count'] }}</flux:table.cell>
                            <flux:table.cell>{{ $team['role'] }}</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif

        <form wire:submit="createTeam" class="mt-6 space-y-6">
            <div class="max-w-md">
                <flux:input wire:model.live.debounce.300ms="name" :label="__('Team name')" type="text" size="sm" required autofocus data-test="create-team-name" />

                <div class="mt-2 flex items-center justify-between px-1">
                    @if($this->name !== '')
                        <p class="text-sm">
                            <x-code>{{ $this->slugPreview }}</x-code>
                        </p>
                    @else
                        <p class="text-sm">
                            {{ __('Slug preview will appear here') }}
                        </p>
                    @endif

                    <p class="tabular-nums text-sm {{ $this->nameLength > 255 ? 'text-red-500' : '' }}">
                        {{ $this->nameLength }}/255
                    </p>
                </div>

                @error('name')
                    <flux:text size="sm" class="mt-1 px-1 text-red-500">
                        <span class="font-medium">{{ __('Validation error:') }}</span> {{ $message }}
                    </flux:text>
                @enderror
            </div>

            <flux:button variant="primary" type="submit" data-test="create-team-submit" size="sm">
                {{ __('Create team') }}
            </flux:button>
        </form>

        <flux:heading class="mt-6" level="2">
            {{ __('What happens on creation') }}
        </flux:heading>

        <x-description.list class="mt-2">
            <x-description.term>{{ __('Team record') }}</x-description.term>
            <x-description.details>
                {{ __('A team record is created with the name and auto-generated slug shown above.') }}
            </x-description.details>

            <x-description.term>{{ __('Role assigned') }}</x-description.term>
            <x-description.details>
                <div class="max-w-prose">
                    {{ __('You are assigned the Owner role with all permissions:') }}
                    <x-code>{{ implode(', ', $this->ownerPermissions) }}</x-code>
                </div>
            </x-description.details>

            <x-description.term>{{ __('Active team') }}</x-description.term>
            <x-description.details>{{ __('This team becomes your active team across the application.') }}</x-description.details>

            <x-description.term>{{ __('Redirect') }}</x-description.term>
            <x-description.details>{{ __('You are redirected to team settings where you can invite members.') }}</x-description.details>
        </x-description.list>

        <flux:button class="mt-6" icon="arrow-left" :href="route('teams.index')" wire:navigate data-test="create-team-back" size="sm">
            {{ __('Back to teams') }}
        </flux:button>
    </div>
</section>
