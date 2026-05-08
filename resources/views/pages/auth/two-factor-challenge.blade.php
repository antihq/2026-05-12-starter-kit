<x-layouts::guest title="Two-factor authentication">
    <section class="w-full">
        <div class="mx-auto max-w-md">
            <div
                class="relative w-full h-auto"
                x-cloak
                x-data="{
                    showRecoveryInput: @js($errors->has('recovery_code')),
                    code: '',
                    recovery_code: '',
                    toggleInput() {
                        this.showRecoveryInput = !this.showRecoveryInput;

                        this.code = '';
                        this.recovery_code = '';

                        $dispatch('clear-2fa-auth-code');

                        $nextTick(() => {
                            this.showRecoveryInput
                                ? this.$refs.recovery_code?.focus()
                                : $dispatch('focus-2fa-auth-code');
                        });
                    },
                }"
            >
                <div x-show="!showRecoveryInput">
                    <flux:heading size="xl" level="1">Authentication code</flux:heading>
                    <p class="mt-2 text-sm">Your account requires two-factor verification. Enter the code from your authenticator app to proceed.</p>
                    <flux:button type="button" size="sm" class="mt-3" @click="toggleInput()">
                        Use a recovery code instead
                    </flux:button>
                </div>

                <div x-show="showRecoveryInput">
                    <flux:heading size="xl" level="1">Recovery code</flux:heading>
                    <p class="mt-2 text-sm">Your account requires two-factor verification. Enter one of your saved recovery codes to proceed.</p>
                    <flux:button type="button" size="sm" class="mt-3" @click="toggleInput()">
                        Use an authenticator code instead
                    </flux:button>
                </div>

                <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-4 space-y-5">
                    @csrf

                    <div x-show="!showRecoveryInput">
                        <flux:field>
                            <flux:label>Authenticator Code</flux:label>
                            <flux:otp
                                x-model="code"
                                length="6"
                                name="code"
                             />
                            <flux:error name="code" />
                            <flux:description>Enter the 6-digit code from your authenticator app (Google Authenticator, 1Password, etc.).</flux:description>
                        </flux:field>
                    </div>

                    <div x-show="showRecoveryInput">
                        <flux:field>
                            <flux:label>Recovery code</flux:label>
                            <flux:input
                                type="text"
                                name="recovery_code"
                                x-ref="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                                size="sm"
                                class="max-w-lg"
                            />
                            <flux:error name="recovery_code" />
                        </flux:field>
                    </div>

                    <flux:button variant="primary" type="submit" size="sm">
                        Verify
                    </flux:button>
                </form>

                <flux:button class="mt-10" icon="arrow-left" :href="route('login')" size="sm">
                    Back to sign in
                </flux:button>
            </div>
        </div>
    </section>
</x-layouts::guest>
