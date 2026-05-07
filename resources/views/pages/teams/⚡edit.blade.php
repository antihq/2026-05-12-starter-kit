<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
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

    public string $deleteTeamName = '';

    public array $members = [];

    public array $invitations = [];

    public array $availableRoles = [];

    public array $memberRoles = [];

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

    public function updatedMemberRoles($value, $key): void
    {
        $this->updateMember((int) $key, $value);
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

    public function removeMember(int $userId): void
    {
        Gate::authorize('removeMember', $this->teamModel);

        $user = User::findOrFail($userId);

        $this->teamModel->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($user->isCurrentTeam($this->teamModel)) {
            $user->switchTeam($user->personalTeam());
        }

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Member removed.'));
    }

    public function cancelInvitation(string $code): void
    {
        Gate::authorize('cancelInvitation', $this->teamModel);

        $invitation = $this->teamModel->invitations()->where('code', $code)->firstOrFail();

        $invitation->delete();

        $this->populateTeamData();

        Flux::toast(variant: 'success', text: __('Invitation cancelled.'));
    }

    public function deleteTeam(): void
    {
        Gate::authorize('delete', $this->teamModel);

        $validated = $this->validate([
            'deleteTeamName' => ['required', 'string'],
        ]);

        if ($validated['deleteTeamName'] !== $this->teamModel->name) {
            $this->addError('deleteTeamName', __('The team name does not match.'));

            return;
        }

        $user = Auth::user();

        $fallbackTeam = $user->isCurrentTeam($this->teamModel)
            ? $user->fallbackTeam($this->teamModel)
            : null;

        DB::transaction(function () use ($user) {
            User::where('current_team_id', $this->teamModel->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchTeam($affectedUser->personalTeam()));

            $this->teamModel->invitations()->delete();
            $this->teamModel->memberships()->delete();
            $this->teamModel->delete();
        });

        if ($fallbackTeam) {
            $user->switchTeam($fallbackTeam);
        }

        $this->redirectRoute('teams.index', navigate: true);
    }

    private function populateTeamData(): void
    {
        $team = $this->teamModel->fresh();

        $this->teamName = $team->name;

        $this->members = $team->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
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
            ])->toArray();

        $this->availableRoles = TeamRole::assignable();

        $this->memberRoles = $team->members()->get()->mapWithKeys(fn ($m) => [
            $m->id => $m->pivot->role->value,
        ])->toArray();
    }

    public function render()
    {
        $title = $this->permissions->canUpdateTeam
            ? __('Team settings — :name', ['name' => $this->teamModel->name])
            : __('View team — :name', ['name' => $this->teamModel->name]);

        return $this->view()->title($title);
    }

    public function getPermissionsProperty(): TeamPermissions
    {
        return Auth::user()->toTeamPermissions($this->teamModel);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Team settings') }}</flux:heading>
    <flux:separator class="mt-2" />
    <x-description.list>
        <x-description.term>{{ __('Slug') }}</x-description.term>
        <x-description.details>{{ $teamModel->slug }}</x-description.details>

        <x-description.term>{{ __('Type') }}</x-description.term>
        <x-description.details>{{ $teamModel->is_personal ? __('Personal') : __('Team') }}</x-description.details>

        <x-description.term>{{ __('Owner') }}</x-description.term>
        <x-description.details>{{ collect($members)->firstWhere('role', 'owner')['name'] ?? '—' }}</x-description.details>

        <x-description.term>{{ __('Members') }}</x-description.term>
        <x-description.details>{{ count($members) }}</x-description.details>

        <x-description.term>{{ __('Pending invitations') }}</x-description.term>
        <x-description.details>{{ count($invitations) }}</x-description.details>
    </x-description.list>

    @if ($this->permissions->canUpdateTeam)
        <flux:heading class="mt-10">{{ __('Update team') }}</flux:heading>
        <form wire:submit="updateTeam" class="mt-4 space-y-5">
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

    <flux:heading class="mt-10">{{ __('Members') }}</flux:heading>

    <div class="mt-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Email') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($members as $member)
                    <flux:table.row :key="$member['id']" data-test="member-row">
                        <flux:table.cell variant="strong">{{ $member['name'] }}</flux:table.cell>

                        <flux:table.cell>{{ $member['email'] }}</flux:table.cell>

                        <flux:table.cell>
                            @if ($member['role'] !== 'owner' && $this->permissions->canUpdateMember)
                                <flux:select
                                    wire:model.live="memberRoles.{{ $member['id'] }}"
                                    size="sm"
                                    class="w-fit"
                                    data-test="member-role-select"
                                >
                                    @foreach ($availableRoles as $role)
                                        <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:badge color="zinc" size="sm" inset="top bottom">{{ $member['role_label'] }}</flux:badge>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                                <flux:button
                                    size="sm"
                                    wire:click="removeMember({{ $member['id'] }})"
                                    wire:confirm="Are you sure you want to remove {{ $member['name'] }} from this team?"
                                    data-test="member-remove-button"
                                >
                                    {{ __('Remove') }}
                                </flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    @if ($this->permissions->canCreateInvitation || count($invitations) > 0)
        <div class="mt-10">
            <flux:heading>{{ __('Invitations') }}</flux:heading>

            @if (count($invitations) > 0)
                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Email') }}</flux:table.column>
                        <flux:table.column>{{ __('Role') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>
                 <flux:table.rows>
                        @foreach ($invitations as $invitation)
                            <flux:table.row :key="$invitation['code']" data-test="invitation-row">
                                <flux:table.cell>{{ $invitation['email'] }}</flux:table.cell>
                             <flux:table.cell>
                                    <flux:badge color="zinc" size="sm" inset="top bottom">{{ $invitation['role_label'] }}</flux:badge>
                                </flux:table.cell>
                             <flux:table.cell align="end">
                                    @if ($this->permissions->canCancelInvitation)
                                        <flux:button
                                            size="sm"
                                            wire:click="cancelInvitation('{{ $invitation['code'] }}')"
                                            wire:confirm="Are you sure you want to cancel the invitation for {{ $invitation['email'] }}?"
                                            data-test="invitation-cancel-button"
                                        >
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>

                <flux:separator variant="subtle" />
            @endif

            @if ($this->permissions->canCreateInvitation)
                <flux:button class="mt-5" variant="primary" size="sm" :href="route('teams.invite', $teamModel)" wire:navigate data-test="invite-member-button">
                    {{ __('Invite member') }}
                </flux:button>
            @endif
        </div>
    @endif

    @if ($this->permissions->canDeleteTeam && ! $teamModel->is_personal)
        <flux:heading class="mt-10">{{ __('Delete team') }}</flux:heading>

        <form wire:submit="deleteTeam" class="mt-4 space-y-5">
            <flux:field>
                <flux:label badge="Required">{{ __('Type ":name" to confirm', ['name' => $teamModel->name]) }}</flux:label>
                <flux:input wire:model="deleteTeamName" type="text" size="sm" required class="max-w-lg" data-test="delete-team-name" />
                <flux:error name="deleteTeamName" />
            </flux:field>

            <flux:button size="sm" variant="danger" type="submit" data-test="delete-team-button">
                {{ __('Delete team') }}
            </flux:button>
        </form>
    @endif

    <flux:button class="mt-10" icon="arrow-left" :href="route('teams.index')" wire:navigate size="sm">
        {{ __('Back to teams') }}
    </flux:button>
</section>
