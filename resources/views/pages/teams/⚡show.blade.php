<?php

use App\Enums\TeamRole;
use App\Livewire\Forms\CreateInvitationForm;
use App\Livewire\Forms\DeleteTeamForm;
use App\Livewire\Forms\UpdateTeamForm;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\TeamPermissions;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public UpdateTeamForm $teamForm;

    public DeleteTeamForm $deleteForm;

    public CreateInvitationForm $invitationForm;

    public bool $editingName = false;

    public bool $showDeleteForm = false;

    public array $members = [];

    public array $invitations = [];

    public bool $showInviteForm = false;

    public ?int $editingMemberId = null;

    public string $editingRole = '';

    public string $cancelInvitationCode = '';

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->teamForm->setTeam($team);
        $this->deleteForm->setTeam($team);
        $this->invitationForm->setTeam($team);

        $this->populateMembers();
        $this->populateInvitations();
    }

    public function startEditingName(): void
    {
        $this->editingName = true;
    }

    public function cancelEditingName(): void
    {
        $this->editingName = false;
        $this->teamForm->name = $this->team->name;
    }

    public function updateTeamName(): void
    {
        $team = $this->teamForm->save();

        $this->team = $team;
        $this->editingName = false;

        Flux::toast(variant: 'success', text: 'Team updated.');
    }

    public function showDelete(): void
    {
        $this->showDeleteForm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteForm = false;
        $this->deleteForm->confirmName = '';
    }

    public function deleteTeam(): void
    {
        $this->deleteForm->delete();

        $this->redirectRoute('teams.index', navigate: true);
    }

    public function editMember(int $userId): void
    {
        $member = collect($this->members)->first(fn ($m) => $m['id'] === $userId);

        if (! $member) {
            return;
        }

        $this->editingMemberId = $userId;
        $this->editingRole = $member['role'];
    }

    public function cancelEditMember(): void
    {
        $this->editingMemberId = null;
        $this->editingRole = '';
    }

    public function updateMemberRole(): void
    {
        Gate::authorize('updateMember', $this->team);

        $validated = validator(
            ['role' => $this->editingRole],
            ['role' => ['required', 'string', Rule::enum(TeamRole::class)]],
        )->validate();

        $this->team->memberships()
            ->where('user_id', $this->editingMemberId)
            ->firstOrFail()
            ->update(['role' => TeamRole::from($validated['role'])]);

        $this->editingMemberId = null;
        $this->editingRole = '';

        $this->populateMembers();

        Flux::toast(variant: 'success', text: 'Member role updated.');
    }

    public function removeMember(int $userId): void
    {
        Gate::authorize('removeMember', $this->team);

        $this->team->memberships()->where('user_id', $userId)->delete();

        $user = User::find($userId);

        if ($user && $user->isCurrentTeam($this->team)) {
            $user->switchTeam($user->personalTeam());
        }

        $this->populateMembers();

        Flux::toast(variant: 'success', text: 'Member removed.');
    }

    public function showInviteMemberForm(): void
    {
        $this->showInviteForm = true;
    }

    public function cancelInviteMember(): void
    {
        $this->showInviteForm = false;
        $this->invitationForm->reset('email', 'role');
    }

    public function createInvitation(): void
    {
        $this->invitationForm->save();

        $this->showInviteForm = false;
        $this->populateInvitations();

        Flux::toast(variant: 'success', text: 'Invitation sent.');
    }

    public function confirmCancelInvitation(string $code): void
    {
        $this->cancelInvitationCode = $code;
    }

    public function cancelCancelInvitation(): void
    {
        $this->cancelInvitationCode = '';
    }

    public function cancelInvitation(string $code): void
    {
        Gate::authorize('cancelInvitation', $this->team);

        $invitation = $this->team->invitations()->where('code', $code)->firstOrFail();

        abort_unless($invitation->team_id === $this->team->id, 404);

        $invitation->delete();

        $this->cancelInvitationCode = '';
        $this->populateInvitations();

        Flux::toast(variant: 'success', text: 'Invitation cancelled.');
    }

    #[Computed]
    public function permissions(): TeamPermissions
    {
        return Auth::user()->toTeamPermissions($this->team);
    }

    #[Computed]
    public function ownerName(): ?string
    {
        return $this->team->owner()?->name;
    }

    #[Computed]
    public function memberCount(): int
    {
        return $this->team->members()->count();
    }

    #[Computed]
    public function invitationCount(): int
    {
        return $this->team->invitations()->whereNull('accepted_at')->count();
    }

    #[Computed]
    public function availableRoles(): array
    {
        return TeamRole::assignable();
    }

    public function render()
    {
        return $this->view()->title($this->team->name);
    }

    private function populateMembers(): void
    {
        $this->members = $this->team->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role->label(),
            'is_owner' => $member->pivot->role === TeamRole::Owner,
            'joined_at' => $member->pivot->created_at->format('Y-m-d H:i'),
        ])->toArray();
    }

    private function populateInvitations(): void
    {
        $this->invitations = $this->team->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn ($invitation) => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'sent_at' => $invitation->created_at->format('Y-m-d H:i'),
                'expires_at' => $invitation->expires_at?->format('Y-m-d H:i'),
                'is_expired' => $invitation->isExpired(),
                'is_pending' => $invitation->isPending(),
            ])->toArray();
    }
}; ?>

<section class="w-full">
    <div class="mt-8 flex flex-col md:flex-row gap-12">
        {{-- Main content --}}
        <div class="flex-1 min-w-0 space-y-12">
            {{-- Team name (inline editable) --}}
            <div>
                @if ($editingName)
                    <form wire:submit="updateTeamName" class="space-y-6 max-w-xl">
                        <flux:field>
                            <flux:label>Team name</flux:label>
                            <flux:input wire:model="teamForm.name" type="text" required data-test="team-name-input" />
                            <flux:error name="teamForm.name" />
                        </flux:field>

                        <div class="flex">
                            <flux:spacer />
                            <div class="flex gap-3">
                                <flux:button variant="primary" type="submit" data-test="team-save-button">Save</flux:button>
                                <flux:button variant="subtle" wire:click="cancelEditingName" type="button">Cancel</flux:button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="flex items-end justify-between gap-4">
                        <flux:heading size="xl" level="1">{{ $team->name }}</flux:heading>
                        @if ($this->permissions->canUpdateTeam)
                            <flux:button variant="subtle" wire:click="startEditingName" data-test="team-edit-button">
                                Edit
                            </flux:button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Members section --}}
            <div>
                <flux:heading size="lg" level="2">Members</flux:heading>

                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column sticky class="bg-white dark:bg-zinc-900">Name</flux:table.column>
                        <flux:table.column>Email</flux:table.column>
                        <flux:table.column>Role</flux:table.column>
                        @if ($this->permissions->canUpdateMember || $this->permissions->canRemoveMember)
                            <flux:table.column>Actions</flux:table.column>
                        @endif
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($members as $member)
                            <flux:table.row :key="$member['id']" data-test="member-row">
                                <flux:table.cell variant="strong" sticky class="bg-white group-hover:bg-zinc-50 dark:bg-zinc-900 dark:group-hover:bg-zinc-800">
                                    {{ $member['name'] }}
                                </flux:table.cell>

                                <flux:table.cell>{{ $member['email'] }}</flux:table.cell>

                                <flux:table.cell>
                                    @if ($editingMemberId === $member['id'])
                                        <flux:select wire:model="editingRole" data-test="member-role-select" class="w-32">
                                            @foreach ($this->availableRoles as $role)
                                                <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @else
                                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ $member['role_label'] }}</flux:badge>
                                    @endif
                                </flux:table.cell>

                                @if ($this->permissions->canUpdateMember || $this->permissions->canRemoveMember)
                                    <flux:table.cell>
                                        @if ($editingMemberId === $member['id'])
                                            <div class="flex gap-2">
                                                <flux:button size="sm" variant="primary" wire:click="updateMemberRole" data-test="member-role-save">Save</flux:button>
                                                <flux:button size="sm" variant="subtle" wire:click="cancelEditMember">Cancel</flux:button>
                                            </div>
                                        @elseif (! $member['is_owner'])
                                            <div class="flex gap-2">
                                                @if ($this->permissions->canUpdateMember)
                                                    <flux:button size="sm" variant="subtle" wire:click="editMember({{ $member['id'] }})" data-test="member-edit-button">
                                                        Edit
                                                    </flux:button>
                                                @endif
                                                @if ($this->permissions->canRemoveMember)
                                                    <flux:button size="sm" variant="danger" wire:click="removeMember({{ $member['id'] }})" wire:confirm="Remove {{ $member['name'] }} from this team?" data-test="member-remove-button">
                                                        Remove
                                                    </flux:button>
                                                @endif
                                            </div>
                                        @endif
                                    </flux:table.cell>
                                @endif
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

            <flux:separator />

            {{-- Invitations section --}}
            <div>
                <div class="flex items-end justify-between gap-4">
                    <flux:heading size="lg" level="2">Invitations</flux:heading>
                    @if ($this->permissions->canCreateInvitation && ! $showInviteForm)
                        <flux:button variant="subtle" wire:click="showInviteMemberForm" data-test="invite-member-button">
                            Invite member
                        </flux:button>
                    @endif
                </div>

                @if ($showInviteForm)
                    <form wire:submit="createInvitation" class="mt-4 space-y-6 max-w-xl">
                        <flux:field>
                            <flux:label>Email address</flux:label>
                            <flux:input wire:model="invitationForm.email" type="email" required autofocus autocomplete="email" data-test="invite-email" />
                            <flux:error name="invitationForm.email" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Role</flux:label>
                            <flux:select wire:model="invitationForm.role" data-test="invite-role">
                                @foreach ($this->availableRoles as $role)
                                    <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:description>Admin can update team settings, send and cancel invitations. Member has no management permissions.</flux:description>
                            <flux:error name="invitationForm.role" />
                        </flux:field>

                        <div class="flex">
                            <flux:spacer />
                            <div class="flex gap-3">
                                <flux:button variant="primary" type="submit" data-test="invite-submit">Send invitation</flux:button>
                                <flux:button variant="subtle" wire:click="cancelInviteMember" type="button">Cancel</flux:button>
                            </div>
                        </div>
                    </form>
                @endif

                @if (filled($invitations))
                    <flux:table class="mt-4">
                        <flux:table.columns>
                            <flux:table.column sticky class="bg-white dark:bg-zinc-900">Email</flux:table.column>
                            <flux:table.column>Role</flux:table.column>
                            <flux:table.column>Status</flux:table.column>
                            <flux:table.column>Sent</flux:table.column>
                            @if ($this->permissions->canCancelInvitation)
                                <flux:table.column>Actions</flux:table.column>
                            @endif
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($invitations as $invitation)
                                <flux:table.row :key="$invitation['code']" data-test="invitation-row">
                                    <flux:table.cell sticky class="bg-white group-hover:bg-zinc-50 dark:bg-zinc-900 dark:group-hover:bg-zinc-800">
                                        {{ $invitation['email'] }}
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        <flux:badge color="zinc" size="sm" inset="top bottom">{{ $invitation['role_label'] }}</flux:badge>
                                    </flux:table.cell>

                                    <flux:table.cell>
                                        @if ($invitation['is_expired'])
                                            <flux:badge color="red" size="sm" inset="top bottom">Expired</flux:badge>
                                        @else
                                            <flux:badge color="amber" size="sm" inset="top bottom">Pending</flux:badge>
                                        @endif
                                    </flux:table.cell>

                                    <flux:table.cell class="tabular-nums">{{ $invitation['sent_at'] }}</flux:table.cell>

                                    @if ($this->permissions->canCancelInvitation)
                                        <flux:table.cell>
                                            @if ($invitation['is_pending'])
                                                @if ($cancelInvitationCode === $invitation['code'])
                                                    <div class="flex gap-2">
                                                        <flux:button size="sm" variant="danger" wire:click="cancelInvitation('{{ $invitation['code'] }}')" data-test="invitation-cancel-button">
                                                            Confirm
                                                        </flux:button>
                                                        <flux:button size="sm" variant="subtle" wire:click="cancelCancelInvitation">Cancel</flux:button>
                                                    </div>
                                                @else
                                                    <flux:button size="sm" variant="danger" wire:click="confirmCancelInvitation('{{ $invitation['code'] }}')">
                                                        Cancel invitation
                                                    </flux:button>
                                                @endif
                                            @endif
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:text class="mt-4 text-zinc-500">No pending invitations.</flux:text>
                @endif
            </div>
        </div>

        {{-- Right sidebar --}}
        <aside class="md:w-72 md:shrink-0 md:sticky md:top-24 md:self-start space-y-8">
            <div>
                <flux:heading size="lg" level="2">Team details</flux:heading>

                <x-description.list class="mt-4">
                    <x-description.term>Owner</x-description.term>
                    <x-description.details>{{ $this->ownerName }}</x-description.details>

                    <x-description.term>Members</x-description.term>
                    <x-description.details>{{ $this->memberCount }} {{ str()->plural('member', $this->memberCount) }}</x-description.details>

                    <x-description.term>Invitations</x-description.term>
                    <x-description.details>{{ $this->invitationCount }} {{ str()->plural('invitation', $this->invitationCount) }}</x-description.details>
                </x-description.list>
            </div>

            @if ($this->permissions->canDeleteTeam && ! $team->is_personal)
            <flux:separator />

            <div>
                <flux:heading size="lg" level="2" color="red">Delete team</flux:heading>

                @if ($showDeleteForm)
                    <form wire:submit="deleteTeam" class="mt-4 space-y-6">
                        <flux:field>
                            <flux:label>Type "{{ $team->name }}" to confirm</flux:label>
                            <flux:input wire:model="deleteForm.confirmName" type="text" required data-test="delete-team-name" />
                            <flux:error name="deleteForm.confirmName" />
                        </flux:field>

                        <div class="flex gap-3">
                            <flux:spacer />
                            <flux:button variant="danger" type="submit" data-test="delete-team-button">
                                Delete team
                            </flux:button>
                        </div>
                    </form>
                @else
                    <div class="mt-4">
                        <flux:button variant="danger" wire:click="showDelete" data-test="team-delete-button">
                            Delete team
                        </flux:button>
                    </div>
                @endif
            </div>
            @endif
        </aside>
    </div>
</section>
