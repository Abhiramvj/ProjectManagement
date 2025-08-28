<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

// These classes are purpose-built for the Orgnice vertical sidebar
const classes = computed(() =>
    props.active
        ? 'group flex items-center rounded-md px-3 py-2.5 text-sm font-semibold bg-slate-100 text-slate-900'
        : 'group flex items-center rounded-md px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900',
);
</script>

<template>
    <Link
        :href="href"
        :class="classes"
        :aria-current="active ? 'page' : undefined"
    >
        <slot />
    </Link>
</template>
