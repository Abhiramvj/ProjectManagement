<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: Array,
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap -mb-1 text-black">
            <template v-for="(link, key) in links" :key="key">
                <!-- Disabled link -->
                <div
                    v-if="link.url === null"
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-black border rounded"
                    v-html="link.label"
                />
                <!-- Active or normal link -->
      <Link
    v-else
    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
    :class="{ 'bg-indigo-600 text-white': link.active }"
    :href="link.url"
    v-html="link.label"
/>

            </template>
        </div>
    </div>
</template>
