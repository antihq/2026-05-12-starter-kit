<?php

namespace App\Livewire\Forms;

use App\Enums\TeamRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UpdateMemberRoleForm extends Form
{
    public ?int $memberId = null;

    public string $role = '';

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::enum(TeamRole::class)],
        ];
    }

    public function setMember(int $memberId, string $role): void
    {
        $this->memberId = $memberId;
        $this->role = $role;
    }

    public function save(): void
    {
        Gate::authorize('updateMember', $this->component->team);

        $validated = $this->validate();

        $this->component->team->memberships()
            ->where('user_id', $this->memberId)
            ->firstOrFail()
            ->update(['role' => TeamRole::from($validated['role'])]);

        $this->reset('memberId', 'role');
    }
}
