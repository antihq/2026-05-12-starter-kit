<?php

use App\Support\UserTeam;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Teams')] class extends Component {
    use WithPagination;

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
            ->map(fn ($team) => new UserTeam(
                id: $team->id,
                name: $team->name,
                slug: $team->slug,
                isPersonal: $team->is_personal,
                role: Auth::user()->teamRole($team)?->value,
                roleLabel: Auth::user()->teamRole($team)?->label(),
                isCurrent: Auth::user()->isCurrentTeam($team),
                memberCount: $team->members_count,
            ));
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Teams') }}</flux:heading>
            <flux:subheading>{{ __('Manage your teams and team memberships') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('teams.create')" wire:navigate data-test="teams-new-team-button">
            {{ __('New team') }}
        </flux:button>
    </div>

    <div class="mt-6">
        <flux:table>
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
                @forelse ($this->teams as $team)
                    <flux:table.row :key="$team->slug" data-test="team-row">
                        <flux:table.cell variant="strong">
                            <span class="font-medium">{{ $team->name }}</span>
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
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6">
                            <flux:text class="py-4 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('You don\'t belong to any teams yet.') }}
                            </flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</section>
