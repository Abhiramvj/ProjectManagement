<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="btn btn-outline btn-sm normal-case"
    >
        <slot />
    </button>
</template>
