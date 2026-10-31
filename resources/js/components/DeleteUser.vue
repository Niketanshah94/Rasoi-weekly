<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and everything in it."
        />
        <Dialog>
            <DialogTrigger as-child>
                <Button variant="destructive" data-test="delete-user-button">
                    Delete account
                </Button>
            </DialogTrigger>
            <DialogContent>
                <Form
                    v-bind="ProfileController.destroy.form()"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ processing }"
                >
                    <DialogHeader class="space-y-3">
                        <DialogTitle>Delete your account?</DialogTitle>
                        <DialogDescription as="div" class="space-y-3">
                            <ul class="list-disc space-y-1 ps-5">
                                <li>
                                    Your household, plans and shopping lists are
                                    deleted.
                                </li>
                                <li>
                                    Any subscription is cancelled and the
                                    AutoPay mandate revoked.
                                </li>
                                <li>
                                    We keep payment records only as long as tax
                                    law requires.
                                </li>
                            </ul>
                            <p>This can’t be undone.</p>
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary">Keep account</Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            variant="destructive"
                            :disabled="processing"
                            data-test="confirm-delete-user-button"
                        >
                            Delete account
                        </Button>
                    </DialogFooter>
                </Form>
            </DialogContent>
        </Dialog>
    </div>
</template>
