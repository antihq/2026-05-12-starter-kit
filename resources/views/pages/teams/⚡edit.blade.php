<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Rules\TeamName;
use App\Support\TeamPermissions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    public Team $teamModel;

    public string $teamName = '';

    public array $teamData = [];

    public array $members = [];

    public array $invitations = [];

    public array $availableRoles = [];

    public function mount(Team $team): void
    {
        $this->teamModel = $team;
        $this->teamName = $team->name;

        $this->populateTeamData();
    }

    public function updateTeam(): void
    {
        Gate::authorize('update', $this->teamModel);

        $validated = $this->validate([
            'teamName' => ['required', 'string', 'max:255', new TeamName],
        ]);

        $team = DB::transaction(function () use ($validated) {
            $team = Team::whereKey($this->teamModel->id)->lockForUpdate()->firstOrFail();

            $team->update(['name' => $validated['teamName']]);

            return $team;
        });

        $this->teamModel = $team;

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Team updated.'));

        $this->redirectRoute('teams.edit', ['team' => $this->teamModel->fresh()->slug], navigate: true);
    }

    public function updateMember(int $userId, string $role): void
    {
        Gate::authorize('updateMember', $this->teamModel);

        $validated = Validator::make(['role' => $role], [
            'role' => ['required', 'string', Rule::enum(TeamRole::class)],
        ])->validate();

        $this->teamModel->memberships()
            ->where('user_id', $userId)
            ->firstOrFail()
            ->update(['role' => TeamRole::from($validated['role'])]);

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Member role updated.'));
    }

    private function populateTeamData(): void
    {
        $user = Auth::user();

        $team = $this->teamModel->fresh();

        $this->teamData = [
            'id' => $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'is_personal' => $team->is_personal,
        ];

        $this->members = $team->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role?->label(),
        ])->toArray();

        $this->invitations = $team->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn ($invitation) => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'created_at' => $invitation->created_at->toISOString(),
            ])->toArray();

        $this->availableRoles = TeamRole::assignable();
    }

    public function render()
    {
        $teamName = $this->teamData['name'] ?? $this->teamModel->name;

        $title = $this->permissions->canUpdateTeam
            ? __('Edit :name', ['name' => $teamName])
            : __('View :name', ['name' => $teamName]);

        return $this->view()->title($title);
    }

    public function getPermissionsProperty(): TeamPermissions
    {
        return Auth::user()->toTeamPermissions($this->teamModel);
    }
}; ?>

<section class="w-full">
    <flux:button variant="ghost" icon="arrow-left" :href="route('teams.index')" wire:navigate data-test="edit-team-back">
        {{ __('Back to teams') }}
    </flux:button>

    <flux:heading size="xl" level="1" class="mt-6">{{ $teamData['name'] ?? $teamModel->name }}</flux:heading>

    @if ($this->permissions->canUpdateTeam)
        <form wire:submit="updateTeam" class="mt-6 space-y-5">
            <flux:field>
                <flux:label badge="Required">{{ __('Team name') }}</flux:label>
                <flux:input wire:model="teamName" type="text" size="sm" required data-test="team-name-input" class="max-w-lg" />
                <flux:error name="teamName" />
                <flux:description>{{ __('255 characters maximum.') }}</flux:description>
            </flux:field>

            <flux:button size="sm" variant="primary" type="submit" data-test="team-save-button">
                {{ __('Save') }}
            </flux:button>
        </form>
    @endif

    <div class="mt-10">
        <flux:heading>{{ __('Team') }}</flux:heading>
        <flux:separator class="mt-2" />
        <x-description.list>
            <x-description.term>{{ __('Team ID') }}</x-description.term>
            <x-description.details>{{ $teamData['id'] }}</x-description.details>

            <x-description.term>{{ __('Slug') }}</x-description.term>
            <x-description.details>{{ $teamData['slug'] }}</x-description.details>

            <x-description.term>{{ __('Type') }}</x-description.term>
            <x-description.details>{{ $teamData['is_personal'] ? __('Personal') : __('Team') }}</x-description.details>

            <x-description.term>{{ __('Owner') }}</x-description.term>
            <x-description.details>{{ collect($members)->firstWhere('role', 'owner')['name'] ?? '—' }}</x-description.details>

            <x-description.term>{{ __('Members') }}</x-description.term>
            <x-description.details>{{ count($members) }}</x-description.details>

            <x-description.term>{{ __('Created') }}</x-description.term>
            <x-description.details>{{ $teamModel->created_at->format('M j, Y') }}</x-description.details>
        </x-description.list>
    </div>

    <div class="mt-10">
        <div class="flex items-center justify-between">
            <flux:heading>{{ __('Team members') }}</flux:heading>

            @if ($this->permissions->canCreateInvitation)
                <flux:modal.trigger name="invite-member">
                    <flux:button variant="primary" size="sm" icon="user-plus" data-test="invite-member-button">
                        {{ __('Invite member') }}
                    </flux:button>
                </flux:modal.trigger>
            @endif
        </div>
        <flux:separator class="mt-2" />

        <div class="space-y-3 mt-4">
            @foreach ($members as $member)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="member-row">
                    <div class="flex items-center gap-4">
                        <flux:avatar :name="$member['name']" :initials="strtoupper(substr($member['name'], 0, 1))" />
                        <div>
                            <div class="font-medium">{{ $member['name'] }}</div>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $member['email'] }}</flux:text>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($member['role'] !== 'owner' && $this->permissions->canUpdateMember)
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="outline" size="sm" icon:trailing="chevron-down" data-test="member-role-trigger">
                                    {{ $member['role_label'] }}
                                </flux:button>
                                <flux:menu>
                                    @foreach ($availableRoles as $role)
                                        <flux:menu.item
                                            as="button"
                                            type="button"
                                            wire:click="updateMember({{ $member['id'] }}, '{{ $role['value'] }}')"
                                            data-test="member-role-option"
                                        >
                                            {{ $role['label'] }}
                                        </flux:menu.item>
                                    @endforeach
                                </flux:menu>
                            </flux:dropdown>
                        @else
                            <flux:badge color="zinc">{{ $member['role_label'] }}</flux:badge>
                        @endif

                        @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                            <flux:modal.trigger name="remove-member-{{ $member['id'] }}">
                                <flux:tooltip :content="__('Remove member')">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="x-mark"
                                        data-test="member-remove-button"
                                    />
                                </flux:tooltip>
                            </flux:modal.trigger>
                        @endif
                    </div>
                </div>

                @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                    <livewire:pages::teams.remove-member-modal
                        :team="$teamModel"
                        :member-id="$member['id']"
                        :member-name="$member['name']"
                        :modal-name="'remove-member-'.$member['id']"
                        :key="'remove-member-modal-'.$member['id']"
                    />
                @endif
            @endforeach
        </div>
    </div>

    @if (count($invitations) > 0)
        <div class="mt-10">
            <flux:heading>{{ __('Pending invitations') }}</flux:heading>
            <flux:separator class="mt-2" />

            <div class="space-y-3 mt-4">
                @foreach ($invitations as $invitation)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="invitation-row">
                        <div class="flex items-center gap-4">
                            <div class="flex size-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon name="envelope" class="text-zinc-500" />
                            </div>
                            <div>
                                <div class="font-medium">{{ $invitation['email'] }}</div>
                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $invitation['role_label'] }}</flux:text>
                            </div>
                        </div>

                        @if ($this->permissions->canCancelInvitation)
                            <flux:modal.trigger name="cancel-invitation-{{ $invitation['code'] }}">
                                <flux:tooltip :content="__('Cancel invitation')">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="x-mark"
                                        data-test="invitation-cancel-button"
                                    />
                                </flux:tooltip>
                            </flux:modal.trigger>
                        @endif
                    </div>
                    @if ($this->permissions->canCancelInvitation)
                        <livewire:pages::teams.cancel-invitation-modal
                            :team="$teamModel"
                            :invitation-code="$invitation['code']"
                            :invitation-email="$invitation['email']"
                            :modal-name="'cancel-invitation-'.$invitation['code']"
                            :key="'cancel-invitation-modal-'.$invitation['code']"
                        />
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->permissions->canDeleteTeam && ! $teamData['is_personal'])
        <div class="mt-10">
            <flux:heading>{{ __('Delete team') }}</flux:heading>
            <flux:separator class="mt-2" />

            <div class="space-y-4 mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-200/10 dark:bg-red-900/20 dark:text-red-100">
                <div>
                    <p class="font-medium">{{ __('Warning') }}</p>
                    <p class="text-sm">{{ __('Please proceed with caution, this cannot be undone.') }}</p>
                </div>

                <flux:modal.trigger name="delete-team">
                    <flux:button variant="danger" size="sm" data-test="delete-team-button">
                        {{ __('Delete team') }}
                    </flux:button>
                </flux:modal.trigger>
            </div>
        </div>
    @endif

    @if ($this->permissions->canCreateInvitation)
        <livewire:pages::teams.invite-member-modal :team="$teamModel" />
    @endif

    @if ($this->permissions->canDeleteTeam && ! $teamData['is_personal'])
        <livewire:pages::teams.delete-team-modal :team="$teamModel" />
    @endif
</section>
