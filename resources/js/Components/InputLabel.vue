<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="label p-0">
        <span class="label-text text-slate-700" v-if="value">{{ value }}</span>
        <span class="label-text text-slate-700" v-else><slot /></span>
    </label>
</template>
