<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  href: String,
  active: Boolean,
});

const classes = computed(() =>
  props.active
    ? 'block px-4 py-2 rounded-md text-sm font-medium bg-indigo-100 text-indigo-700'
    : 'block px-4 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-100'
);
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>
