<?php

use App\Support\UserTeam;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams')] class extends Component {

    public string $sortField = 'name';

    public string $sortDirection = 'asc';

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getTeamsProperty()
    {
        return Auth::user()
            ->teams()
            ->withCount('members')
            ->orderBy($this->sortField, $this->sortDirection)
            ->get()
            ->map(fn ($team) => with(Auth::user()->teamRole($team), fn ($role) => new UserTeam(
                id: $team->id,
                name: $team->name,
                slug: $team->slug,
                isPersonal: $team->is_personal,
                role: $role?->value,
                roleLabel: $role?->label(),
                isCurrent: Auth::user()->isCurrentTeam($team),
                memberCount: $team->members_count,
            )));
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Teams') }}</flux:heading>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column
                sortable
                :sorted="$sortField === 'name'"
                :direction="$sortField === 'name' ? $sortDirection : null"
                wire:click="sortBy('name')"
            >
                {{ __('Name') }}
            </flux:table.column>
            <flux:table.column>{{ __('Type') }}</flux:table.column>
            <flux:table.column
                sortable
                :sorted="$sortField === 'members_count'"
                :direction="$sortField === 'members_count' ? $sortDirection : null"
                wire:click="sortBy('members_count')"
            >
                {{ __('Members') }}
            </flux:table.column>
            <flux:table.column>{{ __('Your Role') }}</flux:table.column>
            <flux:table.column>{{ __('Current') }}</flux:table.column>
            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->teams as $team)
                <flux:table.row :key="$team->slug" data-test="team-row">
                    <flux:table.cell variant="strong">
                        {{ $team->name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($team->isPersonal)
                            <flux:badge color="zinc" size="sm" inset="top bottom">{{ __('Personal') }}</flux:badge>
                        @else
                            {{ __('Team') }}
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($team->memberCount ?? 0) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $team->roleLabel }}
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($team->isCurrent)
                            <flux:badge color="green" size="sm" inset="top bottom">{{ __('Active') }}</flux:badge>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell align="end">
                        <flux:button
                            size="sm"
                            :href="route('teams.edit', $team->slug)"
                            wire:navigate
                            :data-test="$team->role === 'member' ? 'team-view-button' : 'team-edit-button'"
                            inset="top bottom"
                        >
                            {{ $team->role === 'member' ? __('View') : __('Edit') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:separator variant="subtle" />

    <flux:button variant="primary" :href="route('teams.create')" size="sm" wire:navigate data-test="teams-new-team-button" class="mt-5">
        {{ __('New team') }}
    </flux:button>
</section>
