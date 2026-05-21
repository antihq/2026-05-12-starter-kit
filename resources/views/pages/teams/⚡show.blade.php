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
    <div class="max-w-2xl">
        {{-- Left column: team info + members --}}
        <div class="lg:col-span-3">
            <flux:heading level="1">team settings</flux:heading>

            <div class="mt-2">
                <form wire:submit="updateTeamName" class="max-w-sm">
                    <flux:field>
                        <flux:label class="lowercase">Owner</flux:label>
                        <flux:input :value="$this->ownerName" type="text" required variant="filled" readonly />
                        <flux:error name="teamForm.name" />
                    </flux:field>

                    <flux:field class="mt-2">
                        <flux:label class="lowercase">Team name</flux:label>
                        <flux:input wire:model="teamForm.name" type="text" required data-test="team-name-input" :variant="!$this->permissions->canUpdateTeam ? 'filled' : null" :readonly="!$this->permissions->canUpdateTeam" />
                        <flux:error name="teamForm.name" />
                    </flux:field>

                    <div class="mt-4">
                        <flux:button type="submit" variant="primary" color="lime" data-test="team-save-button" class="lowercase" :disabled="!$this->permissions->canUpdateTeam">Update name</flux:button>
                    </div>
                </form>
            </div>

            <div class="mt-8">
                <div class="flex items-center gap-2">
                    <flux:heading class="lowercase" level="2">Members</flux:heading>
                    <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->memberCount }}</span>
                </div>

                <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/5">
                    @foreach ($members as $member)
                        <li class="py-2" data-test="member-row">
                            <div class="flex flex-wrap gap-x-3 justify-between">
                                <div class="flex gap-x-1.5 items-center">
                                    <div>
                                        <span class="font-semibold">{{ $member['name'] }}</span>
                                        <flux:badge color="fuchsia">{{ $member['role'] }}</flux:badge>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-x-3 justify-between">
                                <div>
                                    <span>{{ $member['email'] }}</span>
                                </div>
                                <div>
                                    @if (! $member['is_owner'] && $editingMemberId !== $member['id'] && ($this->permissions->canUpdateMember || $this->permissions->canRemoveMember))
                                        @if ($this->permissions->canUpdateMember)
                                            <flux:badge as="button" wire:click="editMember({{ $member['id'] }})" data-test="member-edit-button" class="lowercase">Edit role</flux:badge>
                                        @endif
                                        @if ($this->permissions->canRemoveMember)
                                            <flux:badge as="button" wire:click="removeMember({{ $member['id'] }})" wire:confirm="Remove {{ $member['name'] }} from this team?" data-test="member-remove-button" class="lowercase">
                                                Remove
                                            </flux:badge>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            @if ($editingMemberId === $member['id'])
                                <div class="mt-1 p-3 pt-2 border border-zinc-950/10 dark:border-white/10 ">
                                    <form wire:submit="updateMemberRole" class="max-w-sm">
                                        <flux:radio.group wire:model="editingRole" label="Role" class="lowercase">
                                            @foreach ($this->availableRoles as $role)
                                                <flux:radio value="{{ $role['value'] }}" label="{{ $role['label'] }}" description="{{ $role['description'] }}" />
                                            @endforeach
                                        </flux:radio.group>
                                        <flux:error name="editingRole" />
                                        <div class="mt-3">
                                            <flux:button type="submit" variant="primary" color="lime" class="lowercase" data-test="member-role-save">Save</flux:button>
                                            <flux:button type="button" variant="ghost" class="lowercase" wire:click="cancelEditMember">Cancel</flux:button>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Right column: invitations + delete --}}
        <div class="lg:col-span-2 mt-4">
            @if ($this->permissions->canCreateInvitation)
            <div>
                <flux:heading class="lowercase" level="2">Invite member</flux:heading>

                <form wire:submit="createInvitation" class="mt-3">
                    <flux:field class="max-w-sm">
                        <flux:label class="lowercase">Email address</flux:label>
                        <flux:input wire:model="invitationForm.email" type="email" required autocomplete="email" data-test="invite-email" />
                        <flux:error name="invitationForm.role" />
                    </flux:field>

                    <flux:field class="mt-2">
                        <flux:label class="lowercase">Role</flux:label>
                        <flux:radio.group wire:model="invitationForm.role" class="lowercase">
                            @foreach ($this->availableRoles as $role)
                                <flux:radio value="{{ $role['value'] }}" label="{{ $role['label'] }}" description="{{ $role['description'] }}" />
                            @endforeach
                        </flux:radio.group>
                    </flux:field>

                    <div class="flex mt-4">
                        <flux:button type="submit" variant="primary" color="lime" data-test="invite-submit" class="lowercase">Send invitation</flux:button>
                    </div>
                </form>
            </div>
            @else
            <div>
                <flux:heading class="lowercase" level="2">Invite member</flux:heading>
                <p class="mt-1 text-zinc-500 dark:text-zinc-400 ">Contact a team admin to invite new members.</p>
            </div>
            @endif

            @if (filled($invitations) || $this->permissions->canCreateInvitation)
            <div class="mt-8">
                <div class="flex items-center gap-2">
                    <flux:heading class="lowercase" level="2">Pending invitations</flux:heading>
                    <span class="text-zinc-500 dark:text-zinc-400 text-sm/5 sm:text-xs/5">{{ $this->invitationCount }}</span>
                </div>

                @if (filled($invitations))
                    <ul role="list" class="divide-y divide-zinc-950/5 dark:divide-white/5">
                        @foreach ($invitations as $invitation)
                            <li class="py-2" data-test="invitation-row">
                                <p>
                                    <span class="font-semibold">{{ $invitation['email'] }}</span>
                                </p>
                                <div class="flex flex-wrap items-center gap-x-3 justify-between">
                                    <div>
                                        <span class="lowercase">{{ $invitation['role_label'] }}</span>
                                        <span class="lowercase text-sm/5 sm:text-xs/5 ">{{ $invitation['time_remaining'] }}</span>
                                    </div>
                                    @if ($this->permissions->canCancelInvitation && $invitation['is_pending'])
                                        <flux:badge as="button" wire:click="cancelInvitation('{{ $invitation['code'] }}')" data-test="invitation-cancel-button" class="lowercase">
                                            Cancel
                                        </flux:badge>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
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
