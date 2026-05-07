<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\UniqueTeamInvitation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invite team member')] class extends Component {
    public Team $team;

    public string $inviteEmail = '';

    public string $inviteRole = 'member';

    public function mount(Team $team): void
    {
        $this->team = $team;
    }

    public function createInvitation(): void
    {
        Gate::authorize('inviteMember', $this->team);

        $validated = $this->validate([
            'inviteEmail' => ['required', 'string', 'email', 'max:255', new UniqueTeamInvitation($this->team)],
            'inviteRole' => ['required', 'string', Rule::enum(TeamRole::class)],
        ]);

        $invitation = $this->team->invitations()->create([
            'email' => $validated['inviteEmail'],
            'role' => TeamRole::from($validated['inviteRole']),
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        $this->reset('inviteEmail', 'inviteRole');

        Flux::toast(variant: 'success', text: __('Invitation sent.'));

        $this->redirectRoute('teams.edit', ['team' => $this->team->slug], navigate: true);
    }

    public function getAvailableRolesProperty(): array
    {
        return TeamRole::assignable();
    }

    public function render()
    {
        return $this->view()->title(__('Invite team member — :team', ['team' => $this->team->name]));
    }
}; ?>

<section class="w-full">
    <div>
        <flux:heading size="xl" level="1">{{ __('Invite team member') }}</flux:heading>
        <p class="mt-2 text-sm max-w-prose">
            {{ __('Send an invitation to join :team. The invitation will expire in 3 days.', ['team' => $this->team->name]) }}
        </p>

        <form wire:submit="createInvitation" class="mt-6 space-y-5">
            <flux:field>
                <flux:label>{{ __('Email address') }}</flux:label>
                <flux:input wire:model="inviteEmail" type="email" size="sm" required autofocus autocomplete="email" class="max-w-lg" data-test="invite-email" />
                <flux:error name="inviteEmail" />
                <flux:description>{{ __('Must be unique. Existing team members cannot be invited.') }}</flux:description>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Role') }}</flux:label>
                <flux:select wire:model="inviteRole" size="sm" class="max-w-lg" data-test="invite-role">
                    @foreach ($this->availableRoles as $role)
                        <flux:select.option value="{{ $role['value'] }}">{{ $role['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="inviteRole" />
            </flux:field>

            <flux:button size="sm" variant="primary" type="submit" data-test="invite-submit">
                {{ __('Send invitation') }}
            </flux:button>
        </form>

        <flux:heading class="mt-10" level="2">
            {{ __('What happens when you invite') }}
        </flux:heading>

        <flux:separator class="mt-2" />

        <x-description.list>
            <x-description.term>{{ __('Invitation sent') }}</x-description.term>
            <x-description.details>{{ __('An email is sent to the address above with a link to accept the invitation.') }}</x-description.details>

            <x-description.term>{{ __('Expires') }}</x-description.term>
            <x-description.details>{{ __('The invitation expires in 3 days. After that, a new invitation must be sent.') }}</x-description.details>

            <x-description.term>{{ __('Role change') }}</x-description.term>
            <x-description.details>{{ __('The role can be changed after the member accepts the invitation from the team settings page.') }}</x-description.details>

            <x-description.term>{{ __('Cancel') }}</x-description.term>
            <x-description.details>{{ __('Pending invitations can be cancelled from the team settings page.') }}</x-description.details>
        </x-description.list>

        <flux:button class="mt-10" icon="arrow-left" :href="route('teams.edit', $team)" wire:navigate size="sm">
            {{ __('Back to team settings') }}
        </flux:button>
    </div>
</section>
