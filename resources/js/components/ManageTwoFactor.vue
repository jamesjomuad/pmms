<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import AlertError from '@/components/AlertError.vue';
import Heading from '@/components/Heading.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

const showDisableModal = ref<boolean>(false);

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-6">
        <Heading
            variant="small"
            title="Two-factor authentication"
            description="Manage your two-factor authentication settings"
        />

        <div
            v-if="!twoFactorEnabled"
            class="flex flex-col items-start justify-start space-y-4"
        >
            <p class="text-sm text-muted-foreground">
                When you enable two-factor authentication, you will be prompted
                for a secure pin during login. This pin can be retrieved from a
                TOTP-supported application on your phone.
            </p>

            <div>
                <Button v-if="hasSetupData" @click="showSetupModal = true">
                    <ShieldCheck />Continue setup
                </Button>
                <Form
                    v-else
                    v-bind="enable.form()"
                    @success="showSetupModal = true"
                    #default="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Enable 2FA
                    </Button>
                </Form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-muted-foreground">
                You will be prompted for a secure, random pin during login,
                which you can retrieve from the TOTP-supported application on
                your phone.
            </p>

            <div>
                <Button
                    variant="destructive"
                    type="button"
                    @click="showDisableModal = true"
                >
                    Disable 2FA
                </Button>
            </div>

            <TwoFactorRecoveryCodes />
        </div>

        <Dialog
            :open="showDisableModal"
            @update:open="showDisableModal = $event"
        >
            <DialogContent>
                <Form
                    v-slot="{ errors, processing }"
                    v-bind="disable.form()"
                    class="flex flex-col gap-4"
                    @success="showDisableModal = false"
                >
                    <DialogHeader>
                        <DialogTitle>
                            Disable two-factor authentication?
                        </DialogTitle>
                        <DialogDescription>
                            Your recovery codes will become invalid and you will
                            lose the extra layer of security on your account.
                            This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    <AlertError
                        v-if="Object.keys(errors).length > 0"
                        :errors="Object.values(errors).map(String)"
                        title="Unable to disable two-factor authentication"
                    />

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary"> Cancel </Button>
                        </DialogClose>

                        <Button
                            variant="destructive"
                            type="submit"
                            :disabled="processing"
                        >
                            Disable 2FA
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
