<x-layouts::guest :title="__('Two-factor authentication')">
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
                    <flux:heading size="xl" level="1">{{ __('Authentication code') }}</flux:heading>
                </div>

                <div x-show="showRecoveryInput">
                    <flux:heading size="xl" level="1">{{ __('Recovery code') }}</flux:heading>
                </div>

                <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-4 space-y-5">
                    @csrf

                    <div x-show="!showRecoveryInput">
                        <flux:field>
                            <flux:label>{{ __('OTP Code') }}</flux:label>
                            <div class="flex items-center justify-center">
                                <flux:otp
                                    x-model="code"
                                    length="6"
                                    name="code"
                                    class="mx-auto"
                                 />
                            </div>
                            <flux:error name="code" />
                            <flux:description>{{ __('6 digits from your authenticator app.') }}</flux:description>
                        </flux:field>
                    </div>

                    <div x-show="showRecoveryInput">
                        <flux:field>
                            <flux:label>{{ __('Recovery code') }}</flux:label>
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
                        {{ __('Continue') }}
                    </flux:button>

                    <div class="space-x-0.5 text-sm leading-5">
                        <span class="opacity-50">{{ __('or you can') }}</span>
                        <div class="inline font-medium underline cursor-pointer opacity-80">
                            <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                            <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                        </div>
                    </div>
                </form>

                <flux:button class="mt-10" icon="arrow-left" :href="route('login')" size="sm">
                    {{ __('Cancel') }}
                </flux:button>
            </div>
        </div>
    </section>
</x-layouts::guest>
