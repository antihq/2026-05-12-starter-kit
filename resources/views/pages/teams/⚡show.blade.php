<?php

use App\Enums\TeamRole;
use App\Livewire\Forms\CreateInvitationForm;
use App\Livewire\Forms\DeleteTeamForm;
use App\Livewire\Forms\UpdateTeamForm;
use App\Models\Team;
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

    public array $members = [];

    public array $invitations = [];

    public ?int $editingMemberId = null;

    public string $editingRole = '';

    public function mount(Team $team): void
    {
        $this->team = $team;
        $this->teamForm->setTeam($team);
        $this->deleteForm->setTeam($team);
        $this->invitationForm->setTeam($team);

        $this->populateMembers();
        $this->populateInvitations();
    }

    public function updateTeamName(): void
    {
        $team = $this->teamForm->save();

        $this->team = $team;

        Flux::toast(variant: 'success', text: 'Team updated.');
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

    public function createInvitation(): void
    {
        $this->invitationForm->save();

        $this->populateInvitations();

        Flux::toast(variant: 'success', text: 'Invitation sent.');
    }

    public function cancelInvitation(string $code): void
    {
        Gate::authorize('cancelInvitation', $this->team);

        $invitation = $this->team->invitations()->where('code', $code)->firstOrFail();

        abort_unless($invitation->team_id === $this->team->id, 404);

        $invitation->delete();

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
                'role_label' => $invitation->role->label(),
                'sent_at' => $invitation->created_at->format('M j, Y'),
                'expires_at' => $invitation->expires_at?->format('M j, Y'),
                'time_remaining' => $invitation->expires_at ? ($invitation->isExpired()
                    ? 'Expired ' . $invitation->expires_at->diffForHumans()
                    : 'Expires ' . $invitation->expires_at->diffForHumans())
                    : null,
                'is_expired' => $invitation->isExpired(),
                'is_pending' => $invitation->isPending(),
            ])->toArray();
    }
}; ?>

<section>
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-x-12 gap-y-8">
        {{-- Left column: team info + members --}}
        <div class="lg:col-span-3">
            <flux:heading level="1">team settings</flux:heading>

            <div class="mt-4">
                <p class="lowercase font-medium!">Owner</p>
                <p class="mt-1">{{ $this->ownerName }}</p>
            </div>

            <div class="mt-5">
                @if ($this->permissions->canUpdateTeam)
                    <form wire:submit="updateTeamName">
                        <div class="space-y-2">
                            <flux:label class="lowercase">Team name</flux:label>
                            <div class="flex flex-wrap gap-4">
                                <flux:input wire:model="teamForm.name" type="text" class="w-full sm:max-w-xs" required data-test="team-name-input" />
                                <flux:button type="submit" data-test="team-save-button" class="lowercase">Update name</flux:button>
                            </div>
                            <flux:error name="teamForm.name" />
                        </div>
                    </form>
                @else
                    <flux:heading size="xl" level="1">{{ $team->name }}</flux:heading>
                @endif
            </div>

            <div class="mt-5">
                <div class="flex items-center gap-2">
                    <flux:heading class="lowercase" level="2">Members</flux:heading>
                    <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->memberCount }}</span>
                </div>

                <ul role="list" class="mt-2 divide-y divide-zinc-950/5 dark:divide-white/5">
                    @foreach ($members as $member)
                        <li class="py-2" data-test="member-row">
                            <div class="flex justify-between gap-x-6">
                                <div class="min-w-0 flex-auto">
                                    <p class="font-medium">{{ $member['name'] }}</p>
                                    <div class="flex flex-wrap items-center gap-x-3 text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">
                                        <span>{{ $member['email'] }}</span>
                                        @if (! $member['is_owner'] && $editingMemberId !== $member['id'] && ($this->permissions->canUpdateMember || $this->permissions->canRemoveMember))
                                            @if ($this->permissions->canUpdateMember)
                                                <button wire:click="editMember({{ $member['id'] }})" data-test="member-edit-button" class="active:bg-yellow-100 lowercase">
                                                    Edit role
                                                </button>
                                            @endif
                                            @if ($this->permissions->canRemoveMember)
                                                <button wire:click="removeMember({{ $member['id'] }})" wire:confirm="Remove {{ $member['name'] }} from this team?" data-test="member-remove-button" class="active:bg-yellow-100 lowercase">
                                                    Remove
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <div class="shrink-0">
                                    <p class="lowercase">{{ $member['role_label'] }}</p>
                                </div>
                            </div>
                            @if ($editingMemberId === $member['id'])
                                <form wire:submit="updateMemberRole" class="mt-4 p-4 border border-zinc-950/10 dark:border-white/10 rounded-xl">
                                    <div class="flex flex-wrap gap-4">
                                        <div>
                                            <flux:label class="lowercase">Role</flux:label>
                                            <flux:select wire:model="editingRole" data-test="member-role-select" class="sm:w-fit lowercase mt-2">
                                                @foreach ($this->availableRoles as $role)
                                                    <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                        <div>
                                            <flux:spacer class="h-8 max-sm:hidden" />
                                            <flux:button type="submit" class="lowercase" data-test="member-role-save">Save</flux:button>
                                        </div>
                                        <div>
                                            <flux:spacer class="h-8 max-sm:hidden" />
                                            <flux:button type="button" class="lowercase" wire:click="cancelEditMember">Cancel</flux:button>
                                        </div>
                                    </div>

                                    <flux:error name="editingRole" />
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Right column: invitations + delete --}}
        <div class="lg:col-span-2">
            @if ($this->permissions->canCreateInvitation)
            <div>
                <flux:heading class="lowercase" level="2">Invite member</flux:heading>

                <form wire:submit="createInvitation" class="mt-4 space-y-5">
                    <div>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <flux:field class="w-full">
                                <flux:label class="lowercase">Email address</flux:label>
                                <flux:input wire:model="invitationForm.email" type="email" required autocomplete="email" data-test="invite-email" />
                            </flux:field>

                            <flux:field class="shrink-0">
                                <flux:label class="lowercase">Role</flux:label>
                                <flux:select wire:model="invitationForm.role" data-test="invite-role" class="lowercase">
                                    @foreach ($this->availableRoles as $role)
                                        <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </flux:field>
                        </div>

                        <flux:error name="invitationForm.role" />
                        <flux:error name="invitationForm.email" />

                        <flux:description class="mt-2">Admin can update team settings and manage invitations. Members have no management permissions.</flux:description>
                    </div>
                    <flux:button type="submit" data-test="invite-submit" class="lowercase">Send invitation</flux:button>
                </form>
            </div>
            @else
            <div>
                <flux:heading class="lowercase" level="2">Invite member</flux:heading>
                <p class="mt-1 text-zinc-500 dark:text-zinc-400 ">Contact a team admin to invite new members.</p>
            </div>
            @endif

            @if (filled($invitations) || $this->permissions->canCreateInvitation)
            <div class="mt-5">
                <div class="flex items-center gap-2">
                    <flux:heading class="lowercase" level="2">Pending invitations</flux:heading>
                    <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->invitationCount }}</span>
                </div>

                @if (filled($invitations))
                    <ul role="list" class="mt-2 divide-y divide-zinc-950/5 dark:divide-white/5">
                        @foreach ($invitations as $invitation)
                            <li class="py-2" data-test="invitation-row">
                                <div class="flex justify-between gap-x-6">
                                    <div class="min-w-0 flex-auto">
                                        <p class="font-medium">{{ $invitation['email'] }}</p>
                                        <div class="flex flex-wrap items-center gap-x-3 text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">
                                            <span class="lowercase">{{ $invitation['time_remaining'] }}</span>
                                            @if ($this->permissions->canCancelInvitation && $invitation['is_pending'])
                                                <button wire:click="cancelInvitation('{{ $invitation['code'] }}')" data-test="invitation-cancel-button" class="active:bg-yellow-100 lowercase">
                                                    Cancel
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="shrink-0">
                                        <p class="lowercase">{{ $invitation['role_label'] }}</p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <flux:text class="mt-2 text-zinc-500">No pending invitations.</flux:text>
                @endif
            </div>
            @endif

            @if ($this->permissions->canDeleteTeam && ! $team->is_personal)
            <div class="mt-5">
                <flux:heading class="lowercase" level="2" color="red">Delete team</flux:heading>

                <form wire:submit="deleteTeam" class="mt-4">
                    <div class="space-y-2">
                        <flux:label class="lowercase">Type "<span class="normal-case">{{ $team->name }}</span>" to confirm</flux:label>
                        <div class="flex flex-wrap gap-4">
                            <div class="sm:flex-1 w-full">
                                <flux:input wire:model="deleteForm.confirmName" type="text" required data-test="delete-team-name" />
                                <flux:error name="deleteForm.confirmName" />
                            </div>
                            <flux:button type="submit" data-test="delete-team-button">Delete team</flux:button>
                        </div>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</section>
