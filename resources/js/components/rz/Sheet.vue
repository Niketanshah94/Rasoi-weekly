<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';

/**
 * The prototype's bottom sheet: a modal <dialog> that closes on Escape or a tap outside.
 */
const open = defineModel<boolean>({ required: true });

defineProps<{
    title: string;
    label?: string;
    back?: boolean;
}>();

const emit = defineEmits<{ back: [] }>();
const dialog = ref<HTMLDialogElement | null>(null);

watch(
    open,
    (value) => {
        const el = dialog.value;
        if (!el) {
            return;
        }
        if (value && !el.open) {
            el.showModal();
            el.scrollTop = 0;
        } else if (!value && el.open) {
            el.close();
        }
    },
    { flush: 'post' },
);

onBeforeUnmount(() => dialog.value?.open && dialog.value.close());

function onClick(event: MouseEvent) {
    // A click on the dialog itself, outside the inner panel, is the backdrop.
    if (event.target === dialog.value) {
        open.value = false;
    }
}
</script>

<template>
    <dialog
        ref="dialog"
        class="sheet"
        :aria-label="label ?? title"
        @close="open = false"
        @click="onClick"
    >
        <div class="sheet-inner">
            <div class="sheet-head">
                <button
                    v-if="back"
                    type="button"
                    class="icon-btn"
                    aria-label="Back"
                    @click="emit('back')"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M15 5l-7 7 7 7" />
                    </svg>
                </button>
                <p class="sheet-slot">{{ title }}</p>
                <button
                    type="button"
                    class="icon-btn"
                    aria-label="Close"
                    @click="open = false"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>
            <slot v-if="open" />
        </div>
    </dialog>
</template>
