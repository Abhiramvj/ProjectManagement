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
        ? 'nav-link aria-[current=page]:bg-slate-100 aria-[current=page]:text-slate-900 group'
        : 'nav-link group'
);
</script>

<template>
    <Link :href="href" :class="classes" :aria-current="active ? 'page' : undefined">
        <slot />
    </Link>
</template>